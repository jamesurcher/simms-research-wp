<?php
/**
 * Search results page ported from Shopify search-header/search-results.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search_query = simms_search_query();
$performed    = '' !== $search_query;
$heading      = $performed ? __( 'Search results', 'simms-research' ) : __( 'Search', 'simms-research' );

$query_args = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'posts_per_page'      => 24,
	'ignore_sticky_posts' => true,
);

if ( '' !== $search_query ) {
	$query_args['s'] = $search_query;
}

$results_query = new WP_Query( $query_args );
$show_fallback = $performed && '' !== $search_query && ! $results_query->have_posts();

if ( $show_fallback ) {
	wp_reset_postdata();
	$results_query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 24,
			'ignore_sticky_posts' => true,
		)
	);
}

get_header();
?>
<section class="search-page color-scheme-1">
	<header class="search-page__header">
		<h1 class="search-page__title"><?php echo esc_html( $heading ); ?></h1>
		<form class="search-page__form" role="search" method="get" action="<?php echo esc_url( home_url( '/search/' ) ); ?>">
			<label class="screen-reader-text" for="SearchPageInput-wp"><?php esc_html_e( 'Search', 'simms-research' ); ?></label>
			<span class="search-page__form-icon" aria-hidden="true"><?php echo simms_inline_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<input
				type="search"
				id="SearchPageInput-wp"
				name="q"
				value="<?php echo esc_attr( $search_query ); ?>"
				placeholder="<?php esc_attr_e( 'Search', 'simms-research' ); ?>"
				autocomplete="off"
			>
			<button type="submit"><?php esc_html_e( 'Search', 'simms-research' ); ?></button>
		</form>
		<?php if ( $show_fallback ) : ?>
			<p class="search-page__no-results">
				<?php
				printf(
					/* translators: %s: search term. */
					esc_html__( 'No results found for "%s". Check the spelling or use a different word or phrase.', 'simms-research' ),
					esc_html( $search_query )
				);
				?>
			</p>
		<?php endif; ?>
	</header>

	<div class="search-page__results" id="ResultsList">
		<?php if ( $results_query->have_posts() ) : ?>
			<ul class="products">
				<?php
				while ( $results_query->have_posts() ) :
					$results_query->the_post();
					$GLOBALS['product'] = wc_get_product( get_the_ID() );
					wc_get_template_part( 'content', 'product' );
				endwhile;
				?>
			</ul>
		<?php else : ?>
			<p class="search-page__empty"><?php esc_html_e( 'No products found.', 'simms-research' ); ?></p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</section>
<?php
get_footer();
