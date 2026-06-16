<?php
/**
 * Blog article template ported from Shopify article.json/main-blog-post.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<?php while ( have_posts() ) : ?>
	<?php the_post(); ?>
	<article <?php post_class( 'blog-article color-scheme-1' ); ?>>
		<header class="blog-article__header">
			<h1><?php the_title(); ?></h1>
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="blog-article__image">
				<?php the_post_thumbnail( 'large' ); ?>
			</div>
		<?php endif; ?>

		<div class="blog-article__content">
			<?php the_content(); ?>
		</div>
	</article>
<?php endwhile; ?>
<?php
get_footer();
