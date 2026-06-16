<?php
/**
 * Product card.
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product = $args['product'] ?? wc_get_product( get_the_ID() );

if ( ! $product instanceof WC_Product ) {
	return;
}

$product_id = $product->get_id();
$dosage     = simms_product_spec( $product_id, 'dosage_summary' );
$purity     = simms_product_spec( $product_id, 'purity', '99%+ Purity' );
?>
<li <?php wc_product_class( 'simms-product-card', $product ); ?>>
	<a class="simms-product-card__image" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
		<?php echo $product->get_image( 'simms-product-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
	<p class="simms-product-card__meta">
		<?php if ( $dosage ) : ?>
			<span><?php echo esc_html( $dosage ); ?></span>
			<span aria-hidden="true">·</span>
		<?php endif; ?>
		<span><?php echo esc_html( $purity ); ?></span>
	</p>
	<h2 class="simms-product-card__title">
		<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
	</h2>
	<p class="simms-product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
	<?php woocommerce_template_loop_add_to_cart(); ?>
</li>
