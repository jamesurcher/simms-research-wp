<?php
/**
 * Default theme fallback template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="simms-section">
	<div class="simms-rail">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article <?php post_class( 'simms-entry' ); ?>>
					<h1><?php the_title(); ?></h1>
					<div class="simms-entry__content">
						<?php the_content(); ?>
					</div>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<h1><?php esc_html_e( 'Nothing found', 'simms-research' ); ?></h1>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();

