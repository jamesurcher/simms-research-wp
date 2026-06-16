<?php
/**
 * Homepage scaffold.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="simms-hero">
	<div class="simms-rail simms-hero__content">
		<p class="simms-eyebrow">Premium Research-Grade Peptides</p>
		<h1>Simms Research</h1>
		<p>Where precision meets excellence. US-based compounds with 99%+ purity trusted worldwide.</p>
		<div class="simms-button-row">
			<a class="simms-button simms-button--light" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>">Shop Now</a>
			<a class="simms-button simms-button--dark" href="<?php echo esc_url( home_url( '/lab-results/' ) ); ?>">View Lab Results</a>
		</div>
	</div>
</section>

<section class="simms-section">
	<div class="simms-rail">
		<p class="simms-eyebrow">New Arrivals</p>
		<h2>Latest research peptides</h2>
		<?php
		if ( class_exists( 'WooCommerce' ) ) {
			echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' );
		}
		?>
	</div>
</section>
<?php
get_footer();
