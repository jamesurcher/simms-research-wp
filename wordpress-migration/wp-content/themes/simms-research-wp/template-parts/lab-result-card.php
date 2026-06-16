<?php
/**
 * Lab result card for one product.
 *
 * @var array $args
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
$cas     = simms_product_spec( $product_id, 'cas' );
$search  = strtolower( $product->get_name() . ' ' . $cas );

foreach ( $batches as $batch ) {
	$search .= ' ' . simms_meta( $batch->ID, '_simms_batch_id' );
}
?>
<article class="simms-lab-card" data-lab-card data-search="<?php echo esc_attr( $search ); ?>">
	<header class="simms-lab-card__head">
		<h2><?php echo esc_html( $product->get_name() ); ?></h2>
		<p><?php echo esc_html( count( $batches ) . ' ' . _n( 'batch', 'batches', count( $batches ), 'simms-research' ) ); ?></p>
	</header>
	<ul class="simms-lab-card__rows" role="list">
		<?php foreach ( array_slice( $batches, 0, 4 ) as $batch ) : ?>
			<?php
			$batch_id  = simms_meta( $batch->ID, '_simms_batch_id' );
			$purity    = simms_meta( $batch->ID, '_simms_purity' );
			$tested_at = simms_meta( $batch->ID, '_simms_tested_at' );
			$coa_url   = simms_meta( $batch->ID, '_simms_coa_url' );
			$is_current = (bool) simms_meta( $batch->ID, '_simms_is_current' );
			?>
			<li>
				<span>
					#<?php echo esc_html( $batch_id ?: '—' ); ?>
					<?php if ( $is_current ) : ?>
						<span class="simms-pill">Latest</span>
					<?php endif; ?>
				</span>
				<span>
					<?php echo esc_html( simms_format_purity( $purity ) ); ?>
					<?php if ( $tested_at ) : ?>
						<time datetime="<?php echo esc_attr( $tested_at ); ?>"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $tested_at ) ) ); ?></time>
					<?php endif; ?>
				</span>
				<?php if ( $coa_url ) : ?>
					<a class="simms-lab-card__coa" href="<?php echo esc_url( $coa_url ); ?>" target="_blank" rel="noopener">View COA</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<a class="simms-lab-card__details" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">View full test details →</a>
</article>
