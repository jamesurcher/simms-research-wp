<?php
/**
 * Email verification gate (view order / pay for order).
 *
 * Theme override of WooCommerce's checkout/form-verify-email.php. Restyled to
 * match the passwordless account sign-in gate (see myaccount/form-login.php):
 * a centered column with a heading, subtitle and pill email field with an inline
 * arrow. simms-account.css is enqueued on the order-pay / order-received
 * endpoints (see inc/account-auth.php) so the .simms-auth styles are available.
 *
 * Preserves WooCommerce's verification contract: POST to $verify_url with the
 * wc_verify_email nonce (check_submission) and the email field — that is all
 * guest_should_verify_email() reads.
 *
 * @package simms-research-wp
 * @version 7.9.0
 *
 * @var bool   $failed_submission Whether the previous verify attempt failed.
 * @var string $verify_url        The URL the verification form posts to.
 */

defined( 'ABSPATH' ) || exit;

$simms_arrow = function_exists( 'simms_inline_icon' ) ? simms_inline_icon( 'arrow' ) : '';
?>

<div class="simms-auth simms-auth--verify" data-active-step="1">

	<?php if ( ! empty( $failed_submission ) ) : ?>
		<p class="simms-auth__alert" role="alert"><?php esc_html_e( 'We couldn\'t verify that email address. Please check it matches your order and try again.', 'simms-research' ); ?></p>
	<?php endif; ?>

	<form name="checkout" method="post" action="<?php echo esc_url( $verify_url ); ?>" class="simms-auth__step simms-auth__step--email" novalidate>
		<h1 class="simms-auth__title"><?php esc_html_e( 'Verify your email', 'simms-research' ); ?></h1>
		<p class="simms-auth__subtitle"><?php esc_html_e( 'Enter the email address on your order and we\'ll take you to it.', 'simms-research' ); ?></p>

		<div class="simms-auth__field">
			<input
				type="email"
				name="email"
				id="email"
				class="simms-auth__input"
				placeholder="<?php esc_attr_e( 'Email', 'simms-research' ); ?>"
				autocomplete="email"
				inputmode="email"
				required
			>
			<button type="submit" name="verify" value="1" class="simms-auth__submit" aria-label="<?php esc_attr_e( 'Verify', 'simms-research' ); ?>">
				<?php echo $simms_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<?php wp_nonce_field( 'wc_verify_email', 'check_submission' ); ?>

		<p class="simms-auth__legal">
			<?php
			printf(
				/* translators: 1: opening login link, 2: closing login link. */
				esc_html__( 'Have an account? %1$sLog in%2$s instead.', 'simms-research' ),
				'<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">',
				'</a>'
			);
			?>
		</p>
	</form>

</div>
