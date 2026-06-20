<?php
/**
 * Research-use terms gate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simms_should_render_tos_gate(): bool {
	if ( is_admin() || is_customize_preview() || wp_doing_ajax() || wp_is_json_request() ) {
		return false;
	}

	$legal_pages = array(
		'terms-conditions',
		'terms-and-conditions',
		'privacy-policy',
		'refund-return',
		'refund-returns',
		'refund-return-policy',
		'shipping-policy',
	);

	if ( is_page( $legal_pages ) || is_privacy_policy() ) {
		return false;
	}

	$request_path = function_exists( 'simms_static_page_request_path' ) ? simms_static_page_request_path() : '';
	if ( in_array( $request_path, $legal_pages, true ) ) {
		return false;
	}

	return true;
}

function simms_tos_gate_head_script(): void {
	if ( ! simms_should_render_tos_gate() ) {
		return;
	}
	?>
	<script>
		(function () {
			try {
				if (document.cookie.indexOf('simms_tos_accepted=1') === -1) {
					document.documentElement.classList.add('tos-gated');
				}
			} catch (e) {}
		})();
	</script>
	<?php
}
add_action( 'wp_head', 'simms_tos_gate_head_script', 0 );

function simms_render_tos_gate(): void {
	if ( ! simms_should_render_tos_gate() ) {
		return;
	}

	get_template_part( 'template-parts/tos-gate' );
}
add_action( 'wp_body_open', 'simms_render_tos_gate', 5 );

function simms_enqueue_tos_gate_assets(): void {
	if ( ! simms_should_render_tos_gate() ) {
		return;
	}

	wp_enqueue_style(
		'simms-tos-gate',
		SIMMS_THEME_URI . '/assets/css/tos-gate.css',
		array( 'simms-base' ),
		SIMMS_THEME_VERSION
	);

	wp_enqueue_script(
		'simms-tos-gate',
		SIMMS_THEME_URI . '/assets/js/tos-gate.js',
		array(),
		SIMMS_THEME_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'simms_enqueue_tos_gate_assets', 20 );
