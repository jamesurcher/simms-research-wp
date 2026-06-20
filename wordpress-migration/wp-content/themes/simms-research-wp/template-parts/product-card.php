<?php
/**
 * Product card — shared by shop archive and homepage grid.
 * Shared Shopify-style product card.
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product = $args['product'] ?? ( isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : wc_get_product( get_the_ID() ) );

if ( ! $product instanceof WC_Product ) {
	return;
}

$product_id = $product->get_id();
$permalink  = get_permalink( $product_id );
$dosage     = simms_product_dosage_summary( $product );
$purity     = simms_product_purity_summary( $product );
$spec_parts = array_filter( array( $dosage, $purity ) );
$price_html = simms_product_card_price_html( $product );
$button_url = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ? $product->add_to_cart_url() : $permalink;
$button     = $product->is_type( 'variable' ) ? __( 'Select options', 'simms-research' ) : __( 'Add to cart', 'simms-research' );

if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
	$button = __( 'View product', 'simms-research' );
}
?>
<li <?php wc_product_class( 'simms-product-card', $product ); ?>>
	<a class="simms-product-card__image" href="<?php echo esc_url( $permalink ); ?>">
		<?php
		// Use the ratio-preserving WooCommerce single size (3:4) rather than the
		// square 'simms-product-card' crop, which zoomed in and removed the
		// product photo's built-in breathing room.
		//
		// Perf: WooCommerce's default sizes is "(max-width: 600px) 100vw, 600px",
		// which makes retina screens request the full-size PNG (~1.5MB each) for
		// every card. Override sizes with the real grid column widths so the
		// browser picks a ~300-768px derivative, and lazy-load everything past the
		// first row to stop the first-load image waterfall.
		// Request-global counter (a static here would reset on each
		// get_template_part() include, which re-runs the file in a fresh scope).
		$simms_card_index = isset( $GLOBALS['simms_card_index'] ) ? (int) $GLOBALS['simms_card_index'] : 0;
		$GLOBALS['simms_card_index'] = $simms_card_index + 1;

		$simms_card_attr  = array(
			'sizes' => '(max-width: 749px) 70vw, (max-width: 989px) 45vw, (max-width: 1500px) 24vw, 360px',
		);
		$simms_above_fold = is_front_page() || ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) );
		if ( ! $simms_above_fold || $simms_card_index >= 4 ) {
			$simms_card_attr['loading'] = 'lazy';
		}
		echo $product->get_image( 'woocommerce_single', $simms_card_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</a>
	<?php if ( ! empty( $spec_parts ) ) : ?>
		<p class="simms-product-card__meta"><?php echo esc_html( implode( ' · ', $spec_parts ) ); ?></p>
	<?php endif; ?>
	<h2 class="simms-product-card__title">
		<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
	</h2>
	<?php if ( '' !== $price_html ) : ?>
		<p class="simms-product-card__price"><?php echo wp_kses_post( $price_html ); ?></p>
	<?php endif; ?>
	<a
		class="simms-product-card__button"
		href="<?php echo esc_url( $button_url ); ?>"
		<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
			data-simms-add-to-cart
			data-product_id="<?php echo esc_attr( $product_id ); ?>"
			data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
			data-quantity="1"
			aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to your cart', 'simms-research' ), $product->get_name() ) ); ?>"
		<?php endif; ?>
	>
		<?php echo esc_html( $button ); ?>
	</a>
</li>
