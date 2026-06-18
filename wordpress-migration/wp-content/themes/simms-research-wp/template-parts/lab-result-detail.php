<?php
/**
 * Per-product detail section with the full test table — Shopify
 * `.lab-results__detail` + `lab-results-row` port. Hidden by default; the
 * lab-results-page custom element reveals it on the #tests/{handle} hash.
 *
 * @var array $args { @type int $product_id }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = absint( $args['product_id'] ?? 0 );
$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;

if ( ! $product instanceof WC_Product || ! function_exists( 'simms_get_product_coa_batches' ) ) {
	return;
}

$batches = simms_get_product_coa_batches( $product_id );

if ( empty( $batches ) ) {
	return;
}

$handle = (string) get_post_field( 'post_name', $product_id );
$title  = $product->get_name();

/**
 * Derive the net-content delta as a percentage when it isn't stored, matching
 * the Liquid's auto-calc. When a delta is already stored (e.g. "1.11mg") it is
 * shown as-is.
 */
$resolve_delta = static function ( string $delta, string $net, string $labeled ): string {
	if ( '' !== trim( $delta ) || '' === trim( $net ) || '' === trim( $labeled ) ) {
		return $delta;
	}

	$labeled_n = (float) preg_replace( '/[^0-9.\-]/', '', $labeled );
	$net_n     = (float) preg_replace( '/[^0-9.\-]/', '', $net );

	if ( $labeled_n <= 0 ) {
		return $delta;
	}

	$pct = (int) round( ( $net_n - $labeled_n ) * 100 / $labeled_n );

	return ( $pct > 0 ? '+' : '' ) . $pct . '%';
};
?>
<section
	class="lab-results__detail"
	data-lab-detail
	data-product-handle="<?php echo esc_attr( $handle ); ?>"
	aria-label="<?php echo esc_attr( $title . ' test results' ); ?>"
	hidden
>
	<div class="lab-results__detail-bar">
		<button type="button" class="lab-results__back" data-lab-back><span aria-hidden="true">←</span> Back to all tests</button>
		<button type="button" class="lab-results__close" data-lab-back aria-label="Close detail view">Close</button>
	</div>

	<h2 class="lab-results__detail-title">
		<?php echo esc_html( $title ); ?>
		<span class="lab-results__detail-title-divider" aria-hidden="true">—</span>
		<span class="lab-results__detail-title-suffix">Test Results</span>
	</h2>

	<div class="lab-results__table-wrap">
		<table class="lab-results__table">
			<thead>
				<tr>
					<th scope="col">Batch</th>
					<th scope="col">Purity</th>
					<th scope="col">Labeled Content</th>
					<th scope="col">Vials Tested</th>
					<th scope="col">Net Content</th>
					<th scope="col">Endotoxins</th>
					<th scope="col">Confirmation Method</th>
					<th scope="col">Test Date</th>
					<th scope="col"><span class="visually-hidden">COA</span></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $batches as $batch ) : ?>
					<?php
					$bid        = (string) simms_meta( $batch->ID, '_simms_batch_id' );
					$purity     = simms_meta( $batch->ID, '_simms_purity' ) ?: simms_meta( $batch->ID, '_simms_avg_purity' );
					$vials      = (string) simms_meta( $batch->ID, '_simms_vials_tested' );
					$labeled    = (string) simms_meta( $batch->ID, '_simms_labeled_content' );
					$net        = (string) simms_meta( $batch->ID, '_simms_net_content', $labeled );
					$delta      = $resolve_delta( (string) simms_meta( $batch->ID, '_simms_net_content_delta' ), $net, $labeled );
					$endo       = (string) simms_meta( $batch->ID, '_simms_endotoxins' );
					$confirm    = (string) simms_meta( $batch->ID, '_simms_test_type' );
					$tested_at  = (string) simms_meta( $batch->ID, '_simms_tested_at' );
					$is_current = (bool) simms_meta( $batch->ID, '_simms_is_current' );
					$coa_url    = simms_get_coa_batch_url( $batch->ID );
					$delta_neg  = str_contains( $delta, '-' );
					$endo_pass  = '' !== $endo && str_contains( strtolower( $endo ), 'pass' );
					?>
					<tr>
						<td data-label="Batch">
							<span class="lab-results__cell-value">
								<?php echo $bid ? '#' . esc_html( $bid ) : '—'; ?>
								<?php if ( $is_current ) : ?><span class="lab-results__pill">Latest</span><?php endif; ?>
							</span>
						</td>
						<td class="lab-results__td--purity" data-label="Purity"><span class="lab-results__cell-value"><?php echo '' !== (string) $purity ? simms_format_purity( $purity ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></td>
						<td data-label="Labeled Content"><span class="lab-results__cell-value"><?php echo esc_html( $labeled ?: '—' ); ?></span></td>
						<td data-label="Vials Tested"><span class="lab-results__cell-value"><?php echo esc_html( $vials ?: '—' ); ?></span></td>
						<td data-label="Net Content">
							<span class="lab-results__cell-value">
								<?php if ( '' !== $net ) : ?>
									<?php echo esc_html( $net ); ?>
									<?php if ( '' !== $delta ) : ?>
										<span class="lab-results__delta<?php echo $delta_neg ? ' lab-results__delta--negative' : ''; ?>">(<?php echo esc_html( $delta ); ?>)</span>
									<?php endif; ?>
								<?php else : ?>—<?php endif; ?>
							</span>
						</td>
						<td class="<?php echo $endo_pass ? 'lab-results__td--pass' : ''; ?>" data-label="Endotoxins"><span class="lab-results__cell-value"><?php echo esc_html( $endo ?: '—' ); ?></span></td>
						<td data-label="Confirmation Method"><span class="lab-results__cell-value"><?php echo esc_html( $confirm ?: '—' ); ?></span></td>
						<td data-label="Test Date"><span class="lab-results__cell-value"><?php echo $tested_at ? esc_html( date_i18n( 'M j, Y', (int) strtotime( $tested_at ) ) ) : '—'; ?></span></td>
						<td data-label="COA">
							<span class="lab-results__cell-value">
								<?php if ( $coa_url ) : ?>
									<button type="button" class="lab-results__td-coa" data-lab-coa data-coa-url="<?php echo esc_url( $coa_url ); ?>" data-coa-title="<?php echo esc_attr( $title . ' — #' . $bid ); ?>">View COA</button>
								<?php else : ?>—<?php endif; ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</section>
