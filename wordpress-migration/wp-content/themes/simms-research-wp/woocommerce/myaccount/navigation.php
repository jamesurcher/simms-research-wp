<?php
/**
 * My Account navigation (branded).
 *
 * Overrides WooCommerce's default. Same endpoints/hooks, with brand classes and
 * an icon per item. Icons resolve via the theme's inline-icon helper and fall
 * back to text-only when an icon file is absent.
 *
 * @package simms-research-wp
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_navigation' );

$simms_nav_icons = array(
	'dashboard'       => 'gauge',
	'orders'          => 'orders',
	'downloads'       => 'package',
	'edit-address'    => 'map-pin',
	'payment-methods' => 'lock',
	'edit-account'    => 'account',
	'customer-logout' => 'external',
);
?>

<nav class="woocommerce-MyAccount-navigation simms-account-nav" aria-label="<?php esc_attr_e( 'Account pages', 'woocommerce' ); ?>">
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<?php
			$simms_icon = isset( $simms_nav_icons[ $endpoint ] ) && function_exists( 'simms_inline_icon' )
				? simms_inline_icon( $simms_nav_icons[ $endpoint ] )
				: '';
			?>
			<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>>
					<?php if ( '' !== $simms_icon ) : ?>
						<span class="simms-account-nav__icon" aria-hidden="true"><?php echo $simms_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
					<span class="simms-account-nav__label"><?php echo esc_html( $label ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' );
