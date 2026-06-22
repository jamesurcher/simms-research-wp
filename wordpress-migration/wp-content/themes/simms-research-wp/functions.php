<?php
/**
 * Simms Research WP theme bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SIMMS_THEME_VERSION', '0.1.62' );
define( 'SIMMS_THEME_DIR', get_template_directory() );
define( 'SIMMS_THEME_URI', get_template_directory_uri() );

require SIMMS_THEME_DIR . '/inc/setup.php';
require SIMMS_THEME_DIR . '/inc/helpers.php';
require SIMMS_THEME_DIR . '/inc/woocommerce.php';
require SIMMS_THEME_DIR . '/inc/checkout-terms.php';
require SIMMS_THEME_DIR . '/inc/account-auth.php';
require SIMMS_THEME_DIR . '/inc/contact-form.php';
require SIMMS_THEME_DIR . '/inc/robots.php';
require SIMMS_THEME_DIR . '/inc/search.php';
require SIMMS_THEME_DIR . '/inc/collections.php';
require SIMMS_THEME_DIR . '/inc/blog.php';
require SIMMS_THEME_DIR . '/inc/static-pages.php';
require SIMMS_THEME_DIR . '/inc/tos-gate.php';
