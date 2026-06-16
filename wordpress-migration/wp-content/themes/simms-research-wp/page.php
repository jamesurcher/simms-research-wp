<?php
/**
 * Default page template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="simms-section">
	<div class="simms-rail">
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class( 'simms-entry' ); ?>>
				<h1><?php the_title(); ?></h1>
				<div class="simms-entry__content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</section>
<?php
get_footer();

