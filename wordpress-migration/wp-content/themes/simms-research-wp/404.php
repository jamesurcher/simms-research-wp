<?php
/**
 * Not found template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="simms-section">
	<div class="simms-rail">
		<p class="simms-eyebrow"><?php esc_html_e( '404', 'simms-research' ); ?></p>
		<h1><?php esc_html_e( 'Page not found', 'simms-research' ); ?></h1>
		<p><?php esc_html_e( 'The page you requested could not be found.', 'simms-research' ); ?></p>
		<a class="simms-button simms-button--dark" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return home', 'simms-research' ); ?></a>
	</div>
</section>
<?php
get_footer();

