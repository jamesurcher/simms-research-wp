<?php
/**
 * Custom robots.txt output.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'robots_txt',
	function ( string $output, bool $public ): string {
		$origin = untrailingslashit( home_url() );

		$lines = array(
			'User-agent: *',
			'Disallow: /wp-admin/',
			'Allow: /wp-admin/admin-ajax.php',
			'Disallow: /cart/',
			'Disallow: /checkout/',
			'Disallow: /my-account/',
			'Disallow: /orders/',
			'Disallow: /*?*add-to-cart=*',
			'Disallow: /*?*variant=*',
			'Disallow: /*?*discount=*',
			'Disallow: /*?*preview=*',
			'Allow: /shop/',
			'Allow: /product/',
			'Allow: /lab-results/',
			'Allow: /about-us/',
			'Allow: /faq/',
			'Allow: /contact/',
			'',
			'Sitemap: ' . $origin . '/sitemap.xml',
			'Sitemap: ' . $origin . '/wp-sitemap.xml',
			'',
			'# AI Crawlers - explicitly permitted',
			'User-agent: GPTBot',
			'Allow: /',
			'',
			'User-agent: ChatGPT-User',
			'Allow: /',
			'',
			'User-agent: OAI-SearchBot',
			'Allow: /',
			'',
			'User-agent: ClaudeBot',
			'Allow: /',
			'',
			'User-agent: PerplexityBot',
			'Allow: /',
			'',
			'User-agent: Google-Extended',
			'Allow: /',
			'',
			'User-agent: CCBot',
			'Allow: /',
		);

		return implode( "\n", $lines ) . "\n";
	},
	10,
	2
);

/**
 * Keep specific product pages out of search engines.
 *
 * Uses the core wp_robots filter so the directive merges into WordPress's
 * single <meta name="robots"> tag instead of emitting a duplicate. Keyed by
 * product slug for stability across environments. These pages stay crawlable
 * in robots.txt (Allow: /product/) on purpose — crawlers must fetch the page
 * to actually see this directive.
 */
add_filter(
	'wp_robots',
	function ( array $robots ): array {
		$noindex_product_slugs = array(
			'glp-3-rt',
		);

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $robots;
		}

		$product = get_queried_object();

		if ( $product instanceof WP_Post && in_array( $product->post_name, $noindex_product_slugs, true ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'] );
		}

		return $robots;
	}
);
