<?php
/**
 * Site-wide WooCommerce cart drawer shell.
 */

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'WC' ) ) {
	return;
}
?>
<aside
	id="simms-cart-drawer"
	class="simms-cart-drawer"
	aria-hidden="true"
	aria-label="<?php esc_attr_e( 'Cart', 'simms-research' ); ?>"
	data-simms-cart-drawer
>
	<button class="simms-cart-drawer__scrim" type="button" aria-label="<?php esc_attr_e( 'Close cart', 'simms-research' ); ?>" data-simms-cart-close></button>
	<section class="simms-cart-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="simms-cart-drawer-title">
		<div data-simms-cart-drawer-content>
			<?php get_template_part( 'template-parts/cart-drawer-content' ); ?>
		</div>
	</section>
</aside>
