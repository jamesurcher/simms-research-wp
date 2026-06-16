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
