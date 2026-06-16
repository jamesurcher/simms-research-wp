<?php
/**
 * Static page route compatibility for migrated Shopify pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simms_static_page_request_path(): string {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';

	return trim( (string) $path, '/' );
}

function simms_static_page_templates(): array {
	return array(
		'about-us'         => 'page-about-us.php',
		'apply'            => 'page-apply.php',
		'contact'          => 'page-contact.php',
		'faq'              => 'page-faq.php',
		'lab-results'      => 'page-lab-results.php',
		'llms-txt'         => 'page-llms-txt.php',
		'partners'         => 'page-partners.php',
		'privacy-policy'       => 'page-privacy-policy.php',
		'refund-return'        => 'page-refund-return.php',
		'shipping-policy'      => 'page-shipping-policy.php',
		'terms-and-conditions' => 'page-terms-conditions.php',
		'terms-conditions'     => 'page-terms-conditions.php',
	);
}

add_action(
	'template_redirect',
	function (): void {
		$path = simms_static_page_request_path();
		$policy_redirects = array(
			'policies/privacy-policy'   => 'privacy-policy',
			'policies/refund-policy'    => 'refund-return',
			'policies/shipping-policy'  => 'shipping-policy',
			'policies/terms-of-service' => 'terms-and-conditions',
		);

		if ( isset( $policy_redirects[ $path ] ) ) {
			wp_safe_redirect( home_url( '/' . $policy_redirects[ $path ] . '/' ), 301 );
			exit;
		}

		if ( ! str_starts_with( $path, 'pages/' ) ) {
			return;
		}

		$slug = trim( substr( $path, strlen( 'pages/' ) ), '/' );

		if ( ! array_key_exists( $slug, simms_static_page_templates() ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/' . $slug . '/' ), 301 );
		exit;
	}
);

add_filter(
	'template_include',
	function ( string $template ): string {
		$path      = simms_static_page_request_path();
		$templates = simms_static_page_templates();

		if ( ! array_key_exists( $path, $templates ) ) {
			return $template;
		}

		global $wp_query;

		$wp_query->is_404  = false;
		$wp_query->is_page = true;
		status_header( 200 );

		$page_template = locate_template( $templates[ $path ] );

		return $page_template ? $page_template : $template;
	},
	98
);
