<?php
/**
 * Product card — shared by shop archive and homepage grid.
 * 1:1 with the Shopify product-card design: portrait image, mono spec line,
 * title, price. Links to the PDP (no on-card add-to-cart, matching source).
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
$dosage     = simms_product_spec( $product_id, 'dosage_summary' );
$purity     = simms_product_spec( $product_id, 'purity' );
$spec_parts = array_filter( array( $dosage, $purity ) );
?>
<li <?php wc_product_class( 'simms-product-card', $product ); ?>>
	<a class="simms-product-card__image" href="<?php echo esc_url( $permalink ); ?>">
		<?php echo $product->get_image( 'simms-product-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
	<?php if ( ! empty( $spec_parts ) ) : ?>
		<p class="simms-product-card__meta"><?php echo esc_html( implode( ' · ', $spec_parts ) ); ?></p>
	<?php endif; ?>
	<h2 class="simms-product-card__title">
		<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
	</h2>
	<p class="simms-product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
</li>
