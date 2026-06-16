<?php
/**
 * Collections list page ported from Shopify list-collections.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$image_html   = '';
$image_post   = get_page_by_path( 'bpc-157', OBJECT, 'product' );
$image_product = $image_post && function_exists( 'wc_get_product' ) ? wc_get_product( $image_post->ID ) : null;

if ( ! $image_product && function_exists( 'wc_get_products' ) ) {
	$products = wc_get_products(
		array(
			'limit'  => 1,
			'status' => 'publish',
		)
	);

	$image_product = ! empty( $products ) ? $products[0] : null;
}

if ( class_exists( 'WC_Product' ) && $image_product instanceof WC_Product ) {
	$image_html = $image_product->get_image( 'simms-product-card' );
}

if ( '' === $image_html ) {
	$image_html = sprintf(
		'<img src="%s" alt="%s" width="900" height="1200">',
		esc_url( SIMMS_THEME_URI . '/assets/images/hero.png' ),
		esc_attr__( 'Simms Research collection', 'simms-research' )
	);
}

$collections = array(
	array(
		'title' => __( 'Catalog', 'simms-research' ),
		'url'   => home_url( '/collections/catalog' ),
	),
	array(
		'title' => __( 'Home page', 'simms-research' ),
		'url'   => home_url( '/collections/frontpage' ),
	),
);

get_header();
?>
<section class="collections-page color-scheme-1">
	<div class="collections-page__inner">
		<header class="collections-page__header">
			<h1><?php esc_html_e( 'Collections', 'simms-research' ); ?></h1>
		</header>

		<div class="collections-page__grid" data-testid="collections-list-grid">
			<?php foreach ( $collections as $collection ) : ?>
				<article class="collections-page__card collection-card">
					<a class="collections-page__link" href="<?php echo esc_url( $collection['url'] ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( $collection['title'] ); ?></span>
					</a>
					<div class="collections-page__image">
						<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<p class="collections-page__title"><?php echo esc_html( $collection['title'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
get_footer();
