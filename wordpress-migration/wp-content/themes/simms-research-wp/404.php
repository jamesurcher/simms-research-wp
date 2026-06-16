<?php
/**
 * Not found template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$discover_products = function_exists( 'wc_get_products' )
	? wc_get_products(
		array(
			'limit'  => 4,
			'status' => 'publish',
		)
	)
	: array();
?>
<section class="not-found-page color-scheme-1">
	<div class="not-found-page__content">
		<h1><?php esc_html_e( 'Page not found', 'simms-research' ); ?></h1>
		<p><?php esc_html_e( 'The link may be incorrect, or the page has been removed.', 'simms-research' ); ?></p>
		<a class="button not-found-page__button" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Continue shopping', 'simms-research' ); ?></a>
	</div>
</section>
<?php if ( ! empty( $discover_products ) ) : ?>
	<section class="not-found-products color-scheme-1">
		<div class="not-found-products__inner">
			<header class="not-found-products__header">
				<h2><?php esc_html_e( 'Discover something new', 'simms-research' ); ?></h2>
			</header>
			<ul class="products">
				<?php foreach ( $discover_products as $product ) : ?>
					<?php get_template_part( 'template-parts/product-card', null, array( 'product' => $product ) ); ?>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>
<?php
get_footer();
