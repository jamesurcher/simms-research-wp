<?php
/**
 * Single product scaffold.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) {
	the_post();
	wc_get_template_part( 'content', 'single-product' );
}

get_footer();

