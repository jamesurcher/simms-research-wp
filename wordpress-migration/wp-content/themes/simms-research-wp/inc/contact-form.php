<?php
/**
 * Contact + affiliate form handlers.
 *
 * Two delivery paths share one validate/send core:
 *  - admin-post.php  → classic POST/redirect/GET (no-JS fallback; renders the
 *                      server-side confirmation/error state from a query arg).
 *  - admin-ajax.php  → progressive-enhancement AJAX (contact-form.js) so the
 *                      confirmation renders client-side, instant and immune to
 *                      full-page edge caching that can swallow the PRG redirect.
 *
 * Production deliverability still needs SMTP or a form plugin (provider is an
 * open decision). When wp_mail() fails we return a distinct 'send' error so the
 * UI can tell the user to email us directly instead of blaming their input.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Validate + send core. Returns:
 *   array( 'ok' => bool, 'code' => 'sent'|'invalid'|'send', 'message' => string )
 * ---------------------------------------------------------------------- */

function simms_contact_process( array $contact ): array {
	$name    = sanitize_text_field( $contact['name'] ?? '' );
	$email   = sanitize_email( $contact['email'] ?? '' );
	$phone   = sanitize_text_field( $contact['phone'] ?? '' );
	$subject = sanitize_text_field( $contact['subject'] ?? '' );
	$message = sanitize_textarea_field( $contact['body'] ?? ( $contact['message'] ?? '' ) );

	if ( ! is_email( $email ) || '' === $message ) {
		return array(
			'ok'      => false,
			'code'    => 'invalid',
			'message' => __( 'Please check your email and message, then try again.', 'simms-research' ),
		);
	}

	$to      = get_option( 'admin_email' );
	$body    = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nSubject: {$subject}\n\n{$message}";
	$headers = array( 'Reply-To: ' . ( $name ? "{$name} <{$email}>" : $email ) );

	if ( ! wp_mail( $to, '[Simms Contact] ' . ( $subject ? $subject : 'New message' ), $body, $headers ) ) {
		return array(
			'ok'      => false,
			'code'    => 'send',
			'message' => __( "We couldn't send your message just now. Please email support@simmsresearch.com directly.", 'simms-research' ),
		);
	}

	return array(
		'ok'      => true,
		'code'    => 'sent',
		'message' => __( "Thanks for contacting us. We'll get back to you as soon as possible.", 'simms-research' ),
	);
}

function simms_affiliate_process( array $contact ): array {
	$first_name          = sanitize_text_field( $contact['First name'] ?? '' );
	$last_name           = sanitize_text_field( $contact['Last name'] ?? '' );
	$email               = sanitize_email( $contact['email'] ?? '' );
	$phone               = sanitize_text_field( $contact['phone'] ?? '' );
	$social_handles      = sanitize_text_field( $contact['Social handles'] ?? '' );
	$instagram_followers = sanitize_text_field( $contact['Instagram followers'] ?? '' );
	$tiktok_followers    = sanitize_text_field( $contact['TikTok followers'] ?? '' );
	$total_followers     = sanitize_text_field( $contact['Total followers'] ?? '' );
	$audience_info       = sanitize_textarea_field( $contact['body'] ?? '' );
	$referral_source     = sanitize_text_field( $contact['How did you find us'] ?? '' );
	$notes               = sanitize_textarea_field( $contact['Additional notes'] ?? '' );

	if (
		'' === $first_name ||
		'' === $last_name ||
		! is_email( $email ) ||
		'' === $phone ||
		'' === $social_handles ||
		'' === $instagram_followers ||
		'' === $tiktok_followers ||
		'' === $referral_source
	) {
		return array(
			'ok'      => false,
			'code'    => 'invalid',
			'message' => __( 'Please check the required fields and try again.', 'simms-research' ),
		);
	}

	$name    = trim( "{$first_name} {$last_name}" );
	$to      = get_option( 'admin_email' );
	$subject = '[Simms Affiliate] Affiliate Program Application';
	$body    = "Program: Affiliate Program\n"
		. "Name: {$name}\n"
		. "Email: {$email}\n"
		. "Phone: {$phone}\n"
		. "Social Handle(s): {$social_handles}\n"
		. "Instagram Followers: {$instagram_followers}\n"
		. "TikTok Followers: {$tiktok_followers}\n"
		. "Total Followers: {$total_followers}\n"
		. "How Did You Find Us: {$referral_source}\n\n"
		. "Platform / Audience Info:\n{$audience_info}\n\n"
		. "Additional Notes:\n{$notes}";
	$headers = array( 'Reply-To: ' . ( $name ? "{$name} <{$email}>" : $email ) );

	if ( ! wp_mail( $to, $subject, $body, $headers ) ) {
		return array(
			'ok'      => false,
			'code'    => 'send',
			'message' => __( "We couldn't submit your application just now. Please email support@simmsresearch.com directly.", 'simms-research' ),
		);
	}

	return array(
		'ok'      => true,
		'code'    => 'sent',
		'message' => __( 'We received your affiliate application and will review it shortly.', 'simms-research' ),
	);
}

/* -------------------------------------------------------------------------
 * Shared: pull the posted contact[] bag, unslashed.
 * ---------------------------------------------------------------------- */

function simms_contact_posted_bag(): array {
	if ( isset( $_POST['contact'] ) && is_array( $_POST['contact'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return (array) wp_unslash( $_POST['contact'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
	return array();
}

/* -------------------------------------------------------------------------
 * admin-post.php — classic POST/redirect/GET (no-JS fallback).
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_simms_contact', 'simms_handle_contact_form' );
add_action( 'admin_post_simms_contact', 'simms_handle_contact_form' );
add_action( 'admin_post_nopriv_simms_affiliate_application', 'simms_handle_affiliate_application' );
add_action( 'admin_post_simms_affiliate_application', 'simms_handle_affiliate_application' );

function simms_handle_contact_form(): void {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );

	if ( ! isset( $_POST['simms_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['simms_contact_nonce'] ) ), 'simms_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
		exit;
	}

	$result = simms_contact_process( simms_contact_posted_bag() );
	wp_safe_redirect( add_query_arg( 'contact', $result['ok'] ? 'sent' : 'error', $redirect ) );
	exit;
}

function simms_handle_affiliate_application(): void {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/apply/' );

	if ( ! isset( $_POST['simms_affiliate_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['simms_affiliate_nonce'] ) ), 'simms_affiliate_application' ) ) {
		wp_safe_redirect( add_query_arg( 'application', 'error', $redirect ) );
		exit;
	}

	$result = simms_affiliate_process( simms_contact_posted_bag() );
	wp_safe_redirect( add_query_arg( 'application', $result['ok'] ? 'sent' : 'error', $redirect ) );
	exit;
}

/* -------------------------------------------------------------------------
 * admin-ajax.php — progressive-enhancement AJAX (contact-form.js).
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_nopriv_simms_contact', 'simms_ajax_contact_form' );
add_action( 'wp_ajax_simms_contact', 'simms_ajax_contact_form' );
add_action( 'wp_ajax_nopriv_simms_affiliate_application', 'simms_ajax_affiliate_application' );
add_action( 'wp_ajax_simms_affiliate_application', 'simms_ajax_affiliate_application' );

function simms_ajax_contact_form(): void {
	if ( ! check_ajax_referer( 'simms_contact', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Please refresh the page and try again.', 'simms-research' ) ), 403 );
	}

	$result = simms_contact_process( simms_contact_posted_bag() );

	if ( $result['ok'] ) {
		wp_send_json_success( array( 'message' => $result['message'] ) );
	}
	wp_send_json_error( array( 'message' => $result['message'] ) );
}

function simms_ajax_affiliate_application(): void {
	if ( ! check_ajax_referer( 'simms_affiliate_application', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Please refresh the page and try again.', 'simms-research' ) ), 403 );
	}

	$result = simms_affiliate_process( simms_contact_posted_bag() );

	if ( $result['ok'] ) {
		wp_send_json_success( array( 'message' => $result['message'] ) );
	}
	wp_send_json_error( array( 'message' => $result['message'] ) );
}

/* -------------------------------------------------------------------------
 * Assets: enhance the contact + affiliate forms only on their pages.
 * ---------------------------------------------------------------------- */

add_action(
	'wp_enqueue_scripts',
	function (): void {
		// /contact/ and /apply/ are virtual routes (see inc/static-pages.php), so
		// match by request path; is_page() also covers any real WP page for them.
		$path         = function_exists( 'simms_static_page_request_path' ) ? simms_static_page_request_path() : '';
		$is_contact   = 'contact' === $path || is_page( 'contact' ) || is_page_template( 'page-contact.php' );
		$is_affiliate = 'apply' === $path || is_page( 'apply' ) || is_page_template( 'page-apply.php' );

		if ( ! $is_contact && ! $is_affiliate ) {
			return;
		}

		wp_enqueue_script(
			'simms-contact-form',
			SIMMS_THEME_URI . '/assets/js/contact-form.js',
			array(),
			SIMMS_THEME_VERSION,
			true
		);

		wp_localize_script(
			'simms-contact-form',
			'simmsContactForm',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'forms'   => array(
					'contact'   => array(
						'action' => 'simms_contact',
						'nonce'  => wp_create_nonce( 'simms_contact' ),
					),
					'affiliate' => array(
						'action' => 'simms_affiliate_application',
						'nonce'  => wp_create_nonce( 'simms_affiliate_application' ),
					),
				),
				'i18n'    => array(
					'sending' => __( 'Sending…', 'simms-research' ),
					'network' => __( 'Network error. Please try again.', 'simms-research' ),
				),
			)
		);
	},
	20
);
