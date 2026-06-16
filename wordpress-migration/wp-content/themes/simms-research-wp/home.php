<?php
/**
 * Blog index template ported from Shopify /blogs/news.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$excluded_posts = array();
$default_post   = get_page_by_path( 'hello-world', OBJECT, 'post' );

if ( $default_post instanceof WP_Post ) {
	$excluded_posts[] = $default_post->ID;
}

$posts_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'ignore_sticky_posts' => true,
		'post__not_in'        => $excluded_posts,
	)
);

get_header();
?>
<section class="blog-page color-scheme-1">
	<div class="blog-page__inner">
		<header class="blog-page__header">
			<h1><?php esc_html_e( 'News', 'simms-research' ); ?></h1>
		</header>

		<div class="blog-page__grid" data-testid="blog-posts">
			<?php if ( $posts_query->have_posts() ) : ?>
				<?php
				while ( $posts_query->have_posts() ) :
					$posts_query->the_post();
					?>
					<article <?php post_class( 'blog-page__card' ); ?>>
						<a class="blog-page__card-link" href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<span class="blog-page__card-image"><?php the_post_thumbnail( 'large' ); ?></span>
							<?php endif; ?>
							<span class="blog-page__card-title"><?php the_title(); ?></span>
							<time class="blog-page__card-date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</a>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
