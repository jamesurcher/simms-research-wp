<?php
/**
 * Plugin Name: Simms Lab Results
 * Description: Registers Simms Research product technical fields and COA batch records for WooCommerce.
 * Version: 0.1.0
 * Author: Simms Research
 * Text Domain: simms-lab-results
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SIMMS_LAB_RESULTS_VERSION', '0.1.0' );
define( 'SIMMS_LAB_RESULTS_DIR', plugin_dir_path( __FILE__ ) );

require SIMMS_LAB_RESULTS_DIR . 'includes/class-post-types.php';
require SIMMS_LAB_RESULTS_DIR . 'includes/class-meta-boxes.php';
require SIMMS_LAB_RESULTS_DIR . 'includes/class-query.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require SIMMS_LAB_RESULTS_DIR . 'includes/class-cli.php';
}

add_action(
	'plugins_loaded',
	function () {
		Simms_Lab_Results_Post_Types::init();
		Simms_Lab_Results_Meta_Boxes::init();
	}
);

register_activation_hook(
	__FILE__,
	function () {
		Simms_Lab_Results_Post_Types::register();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);
