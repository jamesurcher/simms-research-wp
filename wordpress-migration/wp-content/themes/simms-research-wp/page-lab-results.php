<?php
/**
 * Lab Results page — WordPress port of the Shopify `lab-results-index` section.
 *
 * Mirrors the original storefront 1:1: live-product filtering, search,
 * in-page per-product detail tables (#tests/{handle}), and the COA dialog.
 * Markup/CSS/JS are ported from simms-research/sections/lab-results-index.liquid.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$product_ids = function_exists( 'simms_get_products_with_coa_batches' ) ? simms_get_products_with_coa_batches() : array();

// Stats are computed over the live/for-sale products only, so the headline
// numbers never include batches for products that aren't on the page.
$total_batches = 0;
$purity_total  = 0.0;
$purity_count  = 0;

foreach ( $product_ids as $pid ) {
	$batches        = simms_get_product_coa_batches( $pid );
	$total_batches += count( $batches );

	foreach ( $batches as $batch ) {
		$raw = (string) ( simms_meta( $batch->ID, '_simms_purity' ) ?: simms_meta( $batch->ID, '_simms_avg_purity' ) );
		$num = (float) str_replace( '%', '', $raw );
		if ( $num > 0 ) {
			$purity_total += $num;
			$purity_count++;
		}
	}
}

$avg_purity  = $purity_count > 0 ? round( $purity_total / $purity_count, 2 ) . '%' : '—';
$has_entries = ! empty( $product_ids );
?>
<lab-results-page class="lab-results section color-scheme-1" style="--lab-results-padding-block-start:48px;--lab-results-padding-block-end:120px;">
	<div class="lab-results__inner">

		<header class="lab-results__hero">
			<p class="lab-results__eyebrow">Transparency · Independent · Batch-level</p>
			<h1 class="lab-results__heading">Lab Test Results</h1>
			<p class="lab-results__subhead">Every batch we ship is third-party tested. Below is every result, by product, by batch.</p>

			<div class="lab-results__search" data-lab-search-wrap>
				<svg class="lab-results__search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
					<circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.25"/>
					<path d="m11 11 3 3" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
				</svg>
				<input
					type="search"
					class="lab-results__search-input"
					placeholder="Search compound, CAS, or batch ID"
					aria-label="Search lab results"
					data-lab-search
					autocomplete="off"
					spellcheck="false"
				>
				<button type="button" class="lab-results__search-clear" data-lab-clear-search data-lab-search-clear hidden aria-label="Clear search">
					<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
						<line x1="3" y1="3" x2="11" y2="11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
						<line x1="11" y1="3" x2="3" y2="11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
					</svg>
				</button>
			</div>

			<dl class="lab-results__stats">
				<div class="lab-results__stat"><span class="lab-results__stat-icon" aria-hidden="true"><?php echo simms_inline_icon( 'flask-conical' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><dt>Batches tested</dt><dd><?php echo esc_html( (string) $total_batches ); ?></dd></div>
				<div class="lab-results__stat"><span class="lab-results__stat-icon" aria-hidden="true"><?php echo simms_inline_icon( 'gauge' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><dt>Avg purity</dt><dd><?php echo esc_html( $avg_purity ); ?></dd></div>
				<div class="lab-results__stat"><span class="lab-results__stat-icon" aria-hidden="true"><?php echo simms_inline_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><dt>Endotoxin standard</dt><dd>USP &lt;85&gt;</dd></div>
				<div class="lab-results__stat"><span class="lab-results__stat-icon" aria-hidden="true"><?php echo simms_inline_icon( 'award' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><dt>Method</dt><dd>HPLC Verified</dd></div>
			</dl>
		</header>

		<?php if ( $has_entries ) : ?>

			<ul class="lab-results__grid" data-lab-grid role="list">
				<?php foreach ( $product_ids as $pid ) : ?>
					<?php get_template_part( 'template-parts/lab-result-card', null, array( 'product_id' => $pid ) ); ?>
				<?php endforeach; ?>

				<li class="lab-results__no-results" data-lab-no-results hidden>
					<p>No matching lab records.</p>
					<button type="button" data-lab-clear-search>Clear search</button>
				</li>
			</ul>

			<?php foreach ( $product_ids as $pid ) : ?>
				<?php get_template_part( 'template-parts/lab-result-detail', null, array( 'product_id' => $pid ) ); ?>
			<?php endforeach; ?>

		<?php else : ?>
			<div class="lab-results__empty">
				<p>Lab results publishing soon. Every batch we ship will appear here.</p>
			</div>
		<?php endif; ?>

		<p class="lab-results__disclaimer">* Research use only. Not for human or veterinary use.</p>

		<dialog class="lab-results__dialog" data-lab-dialog aria-label="Certificate of analysis">
			<div class="lab-results__dialog-shell">
				<div class="lab-results__dialog-bar">
					<p class="lab-results__dialog-title" data-lab-dialog-title>Certificate of Analysis</p>
					<button type="button" class="lab-results__dialog-close" data-lab-dialog-close aria-label="Close">×</button>
				</div>
				<div class="lab-results__dialog-body">
					<iframe data-lab-dialog-frame src="about:blank" title="Certificate of Analysis"></iframe>
				</div>
			</div>
		</dialog>

	</div>
</lab-results-page>
<?php
get_footer();
