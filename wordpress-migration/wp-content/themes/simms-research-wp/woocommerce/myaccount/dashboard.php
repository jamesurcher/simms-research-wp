<?php
/**
 * My Account dashboard (branded landing).
 *
 * Overrides WooCommerce's default dashboard intro. Keeps the core hooks so any
 * dashboard add-ons still render.
 *
 * @package simms-research-wp
 */

defined( 'ABSPATH' ) || exit;

$simms_cards = array(
	array(
		'url'   => wc_get_endpoint_url( 'orders' ),
		'icon'  => 'orders',
		'title' => __( 'Orders', 'simms-research' ),
		'desc'  => __( 'Track and review past orders', 'simms-research' ),
	),
	array(
		'url'   => wc_get_endpoint_url( 'edit-address' ),
		'icon'  => 'map-pin',
		'title' => __( 'Addresses', 'simms-research' ),
		'desc'  => __( 'Update shipping and billing', 'simms-research' ),
	),
	array(
		'url'   => wc_get_endpoint_url( 'edit-account' ),
		'icon'  => 'account',
		'title' => __( 'Account details', 'simms-research' ),
		'desc'  => __( 'Name and email', 'simms-research' ),
	),
);
?>

<div class="simms-account-dashboard">
	<p class="simms-account-dashboard__intro">
		<?php esc_html_e( 'Manage your orders, addresses, and account details.', 'simms-research' ); ?>
	</p>

	<div class="simms-account-cards">
		<?php foreach ( $simms_cards as $simms_card ) : ?>
			<a class="simms-account-card" href="<?php echo esc_url( $simms_card['url'] ); ?>">
				<?php $simms_icon = function_exists( 'simms_inline_icon' ) ? simms_inline_icon( $simms_card['icon'] ) : ''; ?>
				<?php if ( '' !== $simms_icon ) : ?>
					<span class="simms-account-card__icon" aria-hidden="true"><?php echo $simms_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
				<span class="simms-account-card__title"><?php echo esc_html( $simms_card['title'] ); ?></span>
				<span class="simms-account-card__desc"><?php echo esc_html( $simms_card['desc'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<p class="simms-account-dashboard__logout">
		<a href="<?php echo esc_url( wc_logout_url() ); ?>"><?php esc_html_e( 'Log out', 'simms-research' ); ?></a>
	</p>
</div>

<?php
/**
 * Preserve WooCommerce dashboard hooks for compatibility.
 */
do_action( 'woocommerce_account_dashboard' );
do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );
