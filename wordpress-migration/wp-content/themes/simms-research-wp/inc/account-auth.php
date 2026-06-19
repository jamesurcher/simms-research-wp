<?php
/**
 * Passwordless one-time-passcode (OTP) account gate.
 *
 * Replaces WooCommerce's default login/register on the My Account page with a
 * single flow: enter email -> receive a 6-digit code -> get access. New vs.
 * returning is invisible to the user. Customer accounts are created only AFTER
 * a code is verified, so typing a random email never spawns a junk WP user.
 *
 * Works without JavaScript (native POST round-trips handled on template_redirect)
 * and is progressively enhanced into a two-step AJAX flow by account-auth.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SIMMS_OTP_TTL          = 600; // Seconds a code stays valid (10 minutes).
const SIMMS_OTP_MAX_ATTEMPTS = 5;   // Wrong guesses before a code is burned.
const SIMMS_OTP_RESEND_WAIT  = 45;  // Seconds required between sends to one email.
const SIMMS_OTP_MAX_PER_HOUR = 5;   // Codes per email address per hour.
const SIMMS_OTP_IP_PER_HOUR  = 20;  // Codes per client IP per hour.
const SIMMS_OTP_NONCE        = 'simms_account_auth';

/* -------------------------------------------------------------------------
 * Storage keys (transients). Emails are hashed so they never appear in the
 * options table key and to stay within the option-name length limit.
 * ---------------------------------------------------------------------- */

function simms_otp_record_key( string $email ): string {
	return 'simms_otp_' . md5( strtolower( $email ) );
}

function simms_otp_email_count_key( string $email ): string {
	return 'simms_otp_ec_' . md5( strtolower( $email ) );
}

function simms_otp_ip_count_key(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

	return 'simms_otp_ip_' . md5( $ip );
}

/* -------------------------------------------------------------------------
 * Core: request a code.
 * ---------------------------------------------------------------------- */

/**
 * Validate, rate-limit, generate, store (hashed) and email a fresh code.
 *
 * @return array{ok:bool,code:string,message:string}
 */
function simms_account_request_code( string $email ): array {
	$email = sanitize_email( $email );

	if ( ! is_email( $email ) ) {
		return array(
			'ok'      => false,
			'code'    => 'invalid_email',
			'message' => __( 'Enter a valid email address.', 'simms-research' ),
		);
	}

	// Per-IP hourly cap — blunts email-bombing and enumeration.
	$ip_key   = simms_otp_ip_count_key();
	$ip_count = (int) get_transient( $ip_key );

	if ( $ip_count >= SIMMS_OTP_IP_PER_HOUR ) {
		return array(
			'ok'      => false,
			'code'    => 'ratelimited',
			'message' => __( 'Too many requests. Please try again later.', 'simms-research' ),
		);
	}

	$record = get_transient( simms_otp_record_key( $email ) );

	// Cooldown between sends to the same address.
	if ( is_array( $record ) && isset( $record['sent_at'] ) && ( time() - (int) $record['sent_at'] ) < SIMMS_OTP_RESEND_WAIT ) {
		return array(
			'ok'      => false,
			'code'    => 'cooldown',
			'message' => __( 'Please wait a moment before requesting another code.', 'simms-research' ),
		);
	}

	// Per-email hourly cap.
	$email_count = (int) get_transient( simms_otp_email_count_key( $email ) );

	if ( $email_count >= SIMMS_OTP_MAX_PER_HOUR ) {
		return array(
			'ok'      => false,
			'code'    => 'ratelimited',
			'message' => __( 'Too many codes requested for this email. Please try again later.', 'simms-research' ),
		);
	}

	$plain = (string) wp_rand( 100000, 999999 );

	set_transient(
		simms_otp_record_key( $email ),
		array(
			'hash'     => wp_hash_password( $plain ),
			'email'    => $email,
			'attempts' => 0,
			'sent_at'  => time(),
		),
		SIMMS_OTP_TTL
	);

	set_transient( simms_otp_email_count_key( $email ), $email_count + 1, HOUR_IN_SECONDS );
	set_transient( $ip_key, $ip_count + 1, HOUR_IN_SECONDS );

	if ( ! simms_account_send_code_email( $email, $plain ) ) {
		return array(
			'ok'      => false,
			'code'    => 'mail_failed',
			'message' => __( 'We could not send your code. Please try again.', 'simms-research' ),
		);
	}

	return array(
		'ok'      => true,
		'code'    => 'sent',
		'message' => __( 'We sent a code to your email.', 'simms-research' ),
	);
}

/* -------------------------------------------------------------------------
 * Core: verify a code and sign the customer in.
 * ---------------------------------------------------------------------- */

/**
 * Verify a submitted code. On success, create the customer if needed and start
 * an authenticated session.
 *
 * @return array{ok:bool,code:string,message:string,redirect:string}
 */
function simms_account_verify_code( string $email, string $code ): array {
	$email = sanitize_email( $email );
	$code  = preg_replace( '/\D/', '', (string) $code );

	$fail = array(
		'ok'       => false,
		'code'     => 'invalid_code',
		'message'  => __( 'That code is incorrect or has expired.', 'simms-research' ),
		'redirect' => '',
	);

	if ( ! is_email( $email ) || '' === $code ) {
		return $fail;
	}

	$key    = simms_otp_record_key( $email );
	$record = get_transient( $key );

	if ( ! is_array( $record ) || empty( $record['hash'] ) ) {
		return $fail;
	}

	if ( (int) $record['attempts'] >= SIMMS_OTP_MAX_ATTEMPTS ) {
		delete_transient( $key );

		return array(
			'ok'       => false,
			'code'     => 'locked',
			'message'  => __( 'Too many attempts. Request a new code.', 'simms-research' ),
			'redirect' => '',
		);
	}

	if ( ! wp_check_password( $code, $record['hash'] ) ) {
		$record['attempts'] = (int) $record['attempts'] + 1;
		set_transient( $key, $record, SIMMS_OTP_TTL );

		return $fail;
	}

	// Correct code — single-use, burn it immediately.
	delete_transient( $key );

	$user = get_user_by( 'email', $email );

	// Privilege guard: the OTP gate must never authenticate an elevated account.
	// Controlling an admin's inbox should not grant store-admin access.
	if ( $user && simms_account_is_privileged( $user ) ) {
		return array(
			'ok'       => false,
			'code'     => 'privileged',
			'message'  => __( 'This account must sign in from the admin login.', 'simms-research' ),
			'redirect' => wp_login_url(),
		);
	}

	if ( ! $user ) {
		$user_id = simms_account_create_customer( $email );

		if ( is_wp_error( $user_id ) ) {
			return array(
				'ok'       => false,
				'code'     => 'create_failed',
				'message'  => __( 'We could not create your account. Please contact support.', 'simms-research' ),
				'redirect' => '',
			);
		}

		$user = get_user_by( 'id', $user_id );
	}

	if ( ! $user instanceof WP_User ) {
		return array(
			'ok'       => false,
			'code'     => 'create_failed',
			'message'  => __( 'We could not sign you in. Please try again.', 'simms-research' ),
			'redirect' => '',
		);
	}

	simms_account_login_user( $user );

	return array(
		'ok'       => true,
		'code'     => 'authenticated',
		'message'  => __( 'Signed in.', 'simms-research' ),
		'redirect' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
	);
}

function simms_account_is_privileged( WP_User $user ): bool {
	foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'shop_manager' ) as $role ) {
		if ( in_array( $role, (array) $user->roles, true ) ) {
			return true;
		}
	}

	return user_can( $user, 'edit_posts' ) || user_can( $user, 'manage_woocommerce' );
}

function simms_account_create_customer( string $email ): int|WP_Error {
	if ( function_exists( 'wc_create_new_customer' ) ) {
		// Auto-generates username + a random password the customer never uses.
		$user_id = wc_create_new_customer( $email );
	} else {
		$user_id = wp_insert_user(
			array(
				'user_login' => $email,
				'user_email' => $email,
				'user_pass'  => wp_generate_password( 24, true ),
				'role'       => 'customer',
			)
		);
	}

	// OTP customers never use a password, so clear WooCommerce's "temporary
	// password" nag that would otherwise show on the account dashboard.
	if ( ! is_wp_error( $user_id ) ) {
		delete_user_meta( $user_id, 'default_password_nag' );
	}

	return $user_id;
}

function simms_account_login_user( WP_User $user ): void {
	wp_set_current_user( $user->ID, $user->user_login );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );
}

/* -------------------------------------------------------------------------
 * Branded code email.
 * ---------------------------------------------------------------------- */

function simms_account_send_code_email( string $email, string $code ): bool {
	$site    = get_bloginfo( 'name' );
	/* translators: %s: site name */
	$subject = sprintf( __( 'Your %s sign-in code', 'simms-research' ), $site );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	return (bool) wp_mail( $email, $subject, simms_account_code_email_html( $code ), $headers );
}

function simms_account_code_email_html( string $code ): string {
	$logo  = esc_url( SIMMS_THEME_URI . '/assets/images/simms-logo.png' );
	$code  = esc_html( $code );
	$intro = esc_html__( 'Use this code to sign in. It expires in 10 minutes.', 'simms-research' );
	$note  = esc_html__( "If you didn't request this, you can safely ignore this email.", 'simms-research' );

	return '<div style="margin:0;padding:32px 16px;background:#ffffff;font-family:Helvetica,Arial,sans-serif;color:#0a0a0a;">'
		. '<div style="max-width:420px;margin:0 auto;text-align:center;">'
		. '<img src="' . $logo . '" alt="Simms Research" width="120" style="display:block;margin:0 auto 32px;">'
		. '<p style="font-size:15px;color:#555;margin:0 0 24px;">' . $intro . '</p>'
		. '<div style="font-family:\'Courier New\',monospace;font-size:36px;font-weight:700;letter-spacing:10px;'
		. 'padding:18px 0;border:1px solid #eee;border-radius:10px;">' . $code . '</div>'
		. '<p style="font-size:13px;color:#999;margin:28px 0 0;">' . $note . '</p>'
		. '</div></div>';
}

/* -------------------------------------------------------------------------
 * AJAX endpoints (progressive enhancement).
 * ---------------------------------------------------------------------- */

function simms_account_ajax_request_code(): void {
	check_ajax_referer( SIMMS_OTP_NONCE, 'nonce' );

	$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$result = simms_account_request_code( $email );

	if ( $result['ok'] ) {
		wp_send_json_success( array( 'message' => $result['message'] ) );
	}

	wp_send_json_error(
		array(
			'message' => $result['message'],
			'reason'  => $result['code'],
		)
	);
}
add_action( 'wp_ajax_nopriv_simms_account_request_code', 'simms_account_ajax_request_code' );
add_action( 'wp_ajax_simms_account_request_code', 'simms_account_ajax_request_code' );

function simms_account_ajax_verify_code(): void {
	check_ajax_referer( SIMMS_OTP_NONCE, 'nonce' );

	$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
	$result = simms_account_verify_code( $email, $code );

	if ( $result['ok'] ) {
		wp_send_json_success( array( 'redirect' => $result['redirect'] ) );
	}

	wp_send_json_error(
		array(
			'message'  => $result['message'],
			'reason'   => $result['code'],
			'redirect' => $result['redirect'],
		)
	);
}
add_action( 'wp_ajax_nopriv_simms_account_verify_code', 'simms_account_ajax_verify_code' );
add_action( 'wp_ajax_simms_account_verify_code', 'simms_account_ajax_verify_code' );

/* -------------------------------------------------------------------------
 * No-JS fallback: handle native POST on the account page and expose the
 * resulting view state to the form-login template.
 * ---------------------------------------------------------------------- */

/**
 * Get/set the gate's render state. The POST handler sets it; the template reads
 * it to decide which step to show, prefill the email and surface errors.
 *
 * @return array{step:int,email:string,error:string,notice:string}
 */
function simms_account_gate_state( ?array $set = null ): array {
	static $state = array(
		'step'   => 1,
		'email'  => '',
		'error'  => '',
		'notice' => '',
	);

	if ( null !== $set ) {
		$state = array_merge( $state, $set );
	}

	return $state;
}

function simms_account_handle_post(): void {
	if ( is_user_logged_in() || ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}

	if ( empty( $_POST['simms_otp_action'] ) ) {
		return;
	}

	if ( ! isset( $_POST['simms_otp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['simms_otp_nonce'] ) ), SIMMS_OTP_NONCE ) ) {
		simms_account_gate_state( array( 'error' => __( 'Your session expired. Please try again.', 'simms-research' ) ) );
		return;
	}

	$action = sanitize_text_field( wp_unslash( $_POST['simms_otp_action'] ) );
	$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( 'request' === $action ) {
		$result = simms_account_request_code( $email );

		simms_account_gate_state(
			array(
				'step'   => $result['ok'] ? 2 : 1,
				'email'  => $email,
				'error'  => $result['ok'] ? '' : $result['message'],
				'notice' => $result['ok'] ? $result['message'] : '',
			)
		);

		return;
	}

	if ( 'verify' === $action ) {
		$code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$result = simms_account_verify_code( $email, $code );

		if ( $result['ok'] || '' !== $result['redirect'] ) {
			wp_safe_redirect( '' !== $result['redirect'] ? $result['redirect'] : wc_get_page_permalink( 'myaccount' ) );
			exit;
		}

		simms_account_gate_state(
			array(
				'step'  => 2,
				'email' => $email,
				'error' => $result['message'],
			)
		);
	}
}
add_action( 'template_redirect', 'simms_account_handle_post', 5 );

/* -------------------------------------------------------------------------
 * Assets: account stylesheet everywhere on the account page; the gate script
 * only when logged out.
 * ---------------------------------------------------------------------- */

add_action(
	'wp_enqueue_scripts',
	function (): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		wp_enqueue_style(
			'simms-account',
			SIMMS_THEME_URI . '/assets/css/simms-account.css',
			array( 'simms-base', 'simms-sections' ),
			SIMMS_THEME_VERSION
		);

		if ( is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script(
			'simms-account-auth',
			SIMMS_THEME_URI . '/assets/js/account-auth.js',
			array(),
			SIMMS_THEME_VERSION,
			true
		);

		wp_localize_script(
			'simms-account-auth',
			'simmsAccountAuth',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( SIMMS_OTP_NONCE ),
				'requestAction' => 'simms_account_request_code',
				'verifyAction'  => 'simms_account_verify_code',
				'resendWait'    => SIMMS_OTP_RESEND_WAIT,
			)
		);
	},
	20
);
