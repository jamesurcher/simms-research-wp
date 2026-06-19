<?php
/**
 * Passwordless OTP sign-in gate.
 *
 * Overrides WooCommerce's default login/register form. Renders a single email
 * step that becomes a code step. Works without JS (native POST, state from
 * simms_account_gate_state()) and is enhanced by account-auth.js.
 *
 * @package simms-research-wp
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$simms_gate  = function_exists( 'simms_account_gate_state' )
	? simms_account_gate_state()
	: array(
		'step'  => 1,
		'email' => '',
		'error' => '',
	);
$simms_step  = (int) ( $simms_gate['step'] ?? 1 );
$simms_email = (string) ( $simms_gate['email'] ?? '' );
$simms_error = (string) ( $simms_gate['error'] ?? '' );
$simms_arrow = function_exists( 'simms_inline_icon' ) ? simms_inline_icon( 'arrow' ) : '';
?>

<div class="simms-auth" data-simms-auth data-active-step="<?php echo esc_attr( (string) $simms_step ); ?>">

	<p class="simms-auth__alert" role="alert" data-simms-auth-error<?php echo '' === $simms_error ? ' hidden' : ''; ?>><?php echo esc_html( $simms_error ); ?></p>

	<form class="simms-auth__step simms-auth__step--email" method="post" novalidate data-step="1" data-simms-auth-email-form>
		<h1 class="simms-auth__title"><?php esc_html_e( 'Sign in', 'simms-research' ); ?></h1>
		<p class="simms-auth__subtitle"><?php esc_html_e( 'Enter your email and we\'ll send you a verification code', 'simms-research' ); ?></p>

		<div class="simms-auth__field">
			<input
				type="email"
				name="email"
				class="simms-auth__input"
				placeholder="<?php esc_attr_e( 'Email', 'simms-research' ); ?>"
				autocomplete="email"
				inputmode="email"
				value="<?php echo esc_attr( $simms_email ); ?>"
				required
				data-simms-auth-email
			>
			<button type="submit" class="simms-auth__submit" aria-label="<?php esc_attr_e( 'Continue', 'simms-research' ); ?>">
				<?php echo $simms_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<?php wp_nonce_field( 'simms_account_auth', 'simms_otp_nonce' ); ?>
		<input type="hidden" name="simms_otp_action" value="request">

		<p class="simms-auth__legal">
			<?php
			printf(
				/* translators: %s: Terms of service link */
				esc_html__( 'By continuing, you agree to our %s', 'simms-research' ),
				'<a href="' . esc_url( home_url( '/terms-conditions/' ) ) . '">' . esc_html__( 'Terms of service', 'simms-research' ) . '</a>'
			);
			?>
		</p>
	</form>

	<form class="simms-auth__step simms-auth__step--code" method="post" novalidate data-step="2" data-simms-auth-code-form>
		<h1 class="simms-auth__title"><?php esc_html_e( 'Enter code', 'simms-research' ); ?></h1>
		<p class="simms-auth__subtitle">
			<?php esc_html_e( 'We sent a 6-digit code to', 'simms-research' ); ?>
			<span class="simms-auth__email-target" data-simms-auth-target><?php echo esc_html( $simms_email ); ?></span>
		</p>

		<input
			type="text"
			name="code"
			class="simms-auth__code"
			inputmode="numeric"
			autocomplete="one-time-code"
			pattern="[0-9]*"
			maxlength="6"
			placeholder="••••••"
			required
			data-simms-auth-code
		>

		<?php wp_nonce_field( 'simms_account_auth', 'simms_otp_nonce' ); ?>
		<input type="hidden" name="simms_otp_action" value="verify">
		<input type="hidden" name="email" value="<?php echo esc_attr( $simms_email ); ?>" data-simms-auth-code-email>

		<button type="submit" class="btn btn--secondary simms-auth__verify"><?php esc_html_e( 'Verify', 'simms-research' ); ?></button>

		<p class="simms-auth__resend">
			<button type="button" class="simms-auth__link" data-simms-auth-resend><?php esc_html_e( 'Resend code', 'simms-research' ); ?></button>
			<button type="button" class="simms-auth__link" data-simms-auth-change><?php esc_html_e( 'Use a different email', 'simms-research' ); ?></button>
		</p>
	</form>

</div>

<?php do_action( 'woocommerce_after_customer_login_form' );
