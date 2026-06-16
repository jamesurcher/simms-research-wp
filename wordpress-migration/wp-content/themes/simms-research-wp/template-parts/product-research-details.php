<?php
/**
 * PDP research profile + technical specifications.
 * Ported from snippets/product-research-details.liquid.
 * Lead/body from the product description; spec rows from _simms_* meta.
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product = $args['product'] ?? ( $GLOBALS['product'] ?? wc_get_product( get_the_ID() ) );
if ( ! $product instanceof WC_Product ) {
	return;
}
$pid = $product->get_id();

// Research profile: first paragraph of the description is the lead, the rest is body.
$desc = trim( (string) $product->get_description() );
$lead = '';
$body = '';
if ( '' !== $desc ) {
	$paras = array();
	foreach ( array_filter( array_map( 'trim', preg_split( '#</p>#i', $desc ) ) ) as $part ) {
		if ( false === stripos( $part, '<p' ) ) {
			$part = '<p>' . $part;
		}
		$paras[] = $part . '</p>';
	}
	if ( $paras ) {
		$lead = array_shift( $paras );
		$body = implode( '', $paras );
	}
}

$specs = array_filter(
	array(
		'CAS Number'        => simms_product_spec( $pid, 'cas' ),
		'Molecular Formula' => simms_product_spec( $pid, 'formula' ),
		'Molecular Weight'  => simms_product_spec( $pid, 'molecular_weight' ),
		'Sequence'          => simms_product_spec( $pid, 'sequence' ),
		'Form'              => simms_product_spec( $pid, 'form' ),
		'Solubility'        => simms_product_spec( $pid, 'solubility' ),
		'Storage'           => simms_product_spec( $pid, 'storage' ),
	)
);

if ( '' === $lead && count( $specs ) < 2 ) {
	return;
}
?>
<div class="product-research-details">
	<?php if ( '' !== $lead ) : ?>
		<section class="product-research-details__section">
			<p class="product-research-details__eyebrow">
				<span class="product-research-details__eyebrow-icon" aria-hidden="true"><?php echo simms_inline_icon( 'microscope' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php esc_html_e( 'Research Profile', 'simms-research' ); ?>
			</p>
			<div class="product-research-details__lead rte"><?php echo wp_kses_post( $lead ); ?></div>
			<?php if ( '' !== $body ) : ?>
				<div class="product-research-details__body rte"><?php echo wp_kses_post( $body ); ?></div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( count( $specs ) >= 2 ) : ?>
		<section class="product-research-details__section">
			<p class="product-research-details__eyebrow">
				<span class="product-research-details__eyebrow-icon" aria-hidden="true"><?php echo simms_inline_icon( 'clipboard-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php esc_html_e( 'Technical Specifications', 'simms-research' ); ?>
			</p>
			<dl class="product-research-details__spec-grid">
				<?php foreach ( $specs as $label => $value ) : ?>
					<div class="product-research-details__spec">
						<dt><?php echo esc_html( $label ); ?></dt>
						<dd><?php echo nl2br( esc_html( $value ) ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</section>
	<?php endif; ?>
</div>
