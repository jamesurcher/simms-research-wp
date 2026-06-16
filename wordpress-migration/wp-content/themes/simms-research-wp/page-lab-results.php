<?php
/**
 * Lab Results page scaffold.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$batch_query = new WP_Query(
	array(
		'post_type'      => 'simms_coa_batch',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => '_simms_tested_at',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
	)
);

$batches      = $batch_query->posts;
$batch_count  = count( $batches );
$purity_total = 0;
$purity_count = 0;

foreach ( $batches as $batch ) {
	$purity = (float) str_replace( '%', '', (string) simms_meta( $batch->ID, '_simms_purity' ) );
	if ( $purity > 0 ) {
		$purity_total += $purity;
		$purity_count++;
	}
}

$avg_purity = $purity_count > 0 ? round( $purity_total / $purity_count, 2 ) . '%' : '—';
?>
<section class="simms-section simms-lab-page">
	<div class="simms-rail">
		<header class="simms-lab-page__hero">
			<p class="simms-eyebrow">Transparency · Independent · Batch-Level</p>
			<h1>Lab Test Results</h1>
			<p>Every batch we ship is third-party tested. Below is every result, by product, by batch.</p>
			<label class="simms-search">
				<span class="screen-reader-text">Search lab results</span>
				<span class="simms-search__icon" aria-hidden="true"><?php echo simms_inline_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<input type="search" data-lab-search placeholder="Search compound, CAS, or batch ID">
			</label>
			<dl class="simms-lab-stats">
				<div><span class="simms-lab-stats__icon" aria-hidden="true"><?php echo simms_inline_icon( 'flask-conical' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><dt>Batches tested</dt><dd><?php echo esc_html( (string) $batch_count ); ?></dd></div>
				<div><span class="simms-lab-stats__icon" aria-hidden="true"><?php echo simms_inline_icon( 'gauge' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><dt>Avg purity</dt><dd><?php echo esc_html( $avg_purity ); ?></dd></div>
				<div><span class="simms-lab-stats__icon" aria-hidden="true"><?php echo simms_inline_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><dt>Endotoxin standard</dt><dd>USP &lt;85&gt;</dd></div>
				<div><span class="simms-lab-stats__icon" aria-hidden="true"><?php echo simms_inline_icon( 'award' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><dt>Method</dt><dd>HPLC Verified</dd></div>
			</dl>
		</header>

		<div class="simms-lab-grid" data-lab-grid>
			<?php
			$products = function_exists( 'simms_get_products_with_coa_batches' ) ? simms_get_products_with_coa_batches() : array();
			foreach ( $products as $product_id ) {
				get_template_part( 'template-parts/lab-result-card', null, array( 'product_id' => $product_id ) );
			}
			?>
		</div>
	</div>
</section>
<?php
wp_reset_postdata();
get_footer();

