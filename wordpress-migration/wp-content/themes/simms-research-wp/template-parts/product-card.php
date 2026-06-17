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
		<?php echo $product->get_image( 'simms-product-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
			data-product_id="<?php echo esc_attr( $product_id ); ?>"
			data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to your cart', 'simms-research' ), $product->get_name() ) ); ?>"
		<?php endif; ?>
	>
		<?php echo esc_html( $button ); ?>
	</a>
</li>
