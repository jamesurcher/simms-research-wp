<?php
/**
 * Lab result card for one product — Shopify `.lab-card` port.
 *
 * All rows are rendered with a data-tested-at attribute; the lab-results-page
 * custom element sorts by date and caps the visible rows to the latest four.
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
$cas    = (string) simms_product_spec( $product_id, 'cas' );

$batch_ids = array();
foreach ( $batches as $batch ) {
	$bid = (string) simms_meta( $batch->ID, '_simms_batch_id' );
	if ( '' !== $bid ) {
		$batch_ids[] = $bid;
	}
}

$search_haystack = strtolower( trim( $title . ' ' . $cas . ' ' . implode( ' ', $batch_ids ) ) );
$count           = count( $batches );
?>
<li
	class="lab-card"
	data-lab-card
	data-product-handle="<?php echo esc_attr( $handle ); ?>"
	data-search="<?php echo esc_attr( $search_haystack ); ?>"
>
	<div class="lab-card__head">
		<h3 class="lab-card__title"><?php echo esc_html( $title ); ?></h3>
		<p class="lab-card__count"><?php echo esc_html( $count . ' ' . _n( 'batch', 'batches', $count, 'simms-research' ) ); ?></p>
	</div>

	<ul class="lab-card__rows" data-lab-card-rows role="list">
		<?php foreach ( $batches as $batch ) : ?>
			<?php
			$bid        = (string) simms_meta( $batch->ID, '_simms_batch_id' );
			$purity     = simms_meta( $batch->ID, '_simms_purity' ) ?: simms_meta( $batch->ID, '_simms_avg_purity' );
			$tested_at  = (string) simms_meta( $batch->ID, '_simms_tested_at' );
			$is_current = (bool) simms_meta( $batch->ID, '_simms_is_current' );
			$coa_url    = simms_get_coa_batch_url( $batch->ID );
			$tested_iso = $tested_at ? gmdate( 'Y-m-d', (int) strtotime( $tested_at ) ) : '';
			?>
			<li class="lab-card__row" data-tested-at="<?php echo esc_attr( $tested_iso ); ?>">
				<span class="lab-card__row-id">
					#<?php echo esc_html( $bid ?: '—' ); ?>
					<?php if ( $is_current ) : ?><span class="lab-card__pill">Latest</span><?php endif; ?>
				</span>
				<span class="lab-card__row-meta">
					<?php if ( '' !== (string) $purity ) : ?><span class="lab-card__row-purity"><?php echo simms_format_purity( $purity ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php endif; ?>
					<?php if ( $tested_at ) : ?><span class="lab-card__row-date"><?php echo esc_html( date_i18n( 'M j, Y', (int) strtotime( $tested_at ) ) ); ?></span><?php endif; ?>
				</span>
				<?php if ( $coa_url ) : ?>
					<button
						type="button"
						class="lab-card__row-coa"
						data-lab-coa
						data-coa-url="<?php echo esc_url( $coa_url ); ?>"
						data-coa-title="<?php echo esc_attr( $title . ' — #' . $bid ); ?>"
					>View COA</button>
				<?php else : ?>
					<span class="lab-card__row-coa lab-card__row-coa--missing" aria-disabled="true">—</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<a
		href="#tests/<?php echo esc_attr( $handle ); ?>"
		class="lab-card__details"
		data-lab-detail-link
		data-product-handle="<?php echo esc_attr( $handle ); ?>"
	>View full test details <span aria-hidden="true">→</span></a>
</li>
