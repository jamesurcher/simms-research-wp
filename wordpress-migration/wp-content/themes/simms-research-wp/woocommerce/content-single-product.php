<?php
/**
 * Single product content — Simms PDP layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( get_the_ID() );
}

if ( ! $product ) {
	return;
}

$product_id = $product->get_id();

$normalize_key = static function ( mixed $value ): string {
	return strtolower( preg_replace( '/[^a-z0-9]+/i', '', (string) $value ) );
};

$format_percent = static function ( mixed $value ): string {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	return str_contains( $value, '%' ) ? $value : $value . '%';
};

$format_money_text = static function ( mixed $price ): string {
	return html_entity_decode( wp_strip_all_tags( wc_price( $price ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
};

$format_test_date = static function ( mixed $date ): string {
	$timestamp = strtotime( (string) $date );

	return $timestamp ? date_i18n( 'M j, Y', $timestamp ) : trim( (string) $date );
};

$parse_measure = static function ( mixed $value ): ?float {
	if ( preg_match( '/-?[0-9]+(?:\.[0-9]+)?/', (string) $value, $matches ) ) {
		return (float) $matches[0];
	}

	return null;
};

$format_net_delta = static function ( mixed $labeled, mixed $net, mixed $fallback ) use ( $parse_measure ): array {
	$labeled_value = $parse_measure( $labeled );
	$net_value     = $parse_measure( $net );

	if ( $labeled_value && $net_value ) {
		$percent = (int) round( ( ( $net_value - $labeled_value ) / $labeled_value ) * 100 );

		if ( 0 !== $percent ) {
			return array(
				'label'    => '(' . ( $percent > 0 ? '+' : '' ) . $percent . '%)',
				'negative' => $percent < 0,
			);
		}
	}

	$fallback = trim( (string) $fallback );

	if ( '' === $fallback ) {
		return array(
			'label'    => '',
			'negative' => false,
		);
	}

	if ( ! str_contains( $fallback, '(' ) ) {
		$fallback = '(' . ( str_starts_with( $fallback, '-' ) ? '' : '+' ) . $fallback . ')';
	}

	return array(
		'label'    => $fallback,
		'negative' => str_contains( $fallback, '-' ),
	);
};

$attribute_value_label = static function ( string $attribute_key, mixed $value ): string {
	$value = trim( rawurldecode( (string) $value ) );

	if ( '' === $value ) {
		return '';
	}

	$taxonomy = preg_replace( '/^attribute_/', '', $attribute_key );

	if ( taxonomy_exists( $taxonomy ) ) {
		$term = get_term_by( 'slug', $value, $taxonomy );

		if ( $term && ! is_wp_error( $term ) ) {
			return $term->name;
		}
	}

	return wc_attribute_label( $value );
};

$business_date = static function ( DateTimeImmutable $start, int $business_days ): DateTimeImmutable {
	$date  = $start;
	$count = 0;

	while ( $count < $business_days ) {
		$date = $date->modify( '+1 day' );

		if ( (int) $date->format( 'N' ) < 6 ) {
			$count++;
		}
	}

	return $date;
};

$now             = current_datetime();
$today           = $now instanceof DateTimeImmutable ? $now : DateTimeImmutable::createFromMutable( $now );
$delivery_start  = $business_date( $today, 3 );
$delivery_end    = $business_date( $today, 6 );
$delivery_window = $delivery_start->format( 'D, M j' ) . ' &ndash; ' . $delivery_end->format( 'D, M j' );

$dosage_summary = simms_product_dosage_summary( $product );
$purity_summary = simms_product_purity_summary( $product );
$spec_line      = implode( ' &middot; ', array_filter( array( str_replace( '-', '&ndash;', esc_html( $dosage_summary ) ), esc_html( $purity_summary ) ) ) );

$variation_options  = array();
$selected_variation = null;
$selected_attrs     = array();

if ( $product->is_type( 'variable' ) ) {
	foreach ( $product->get_available_variations() as $variation_data ) {
		$variation_id      = absint( $variation_data['variation_id'] ?? 0 );
		$variation_product = $variation_id ? wc_get_product( $variation_id ) : null;

		if ( ! $variation_product instanceof WC_Product_Variation ) {
			continue;
		}

		$attributes           = array_filter( (array) ( $variation_data['attributes'] ?? array() ) );
		$display_attribute    = '';
		$display_attribute_id = '';

		foreach ( $attributes as $key => $value ) {
			if ( str_contains( strtolower( (string) $key ), 'dosage' ) ) {
				$display_attribute_id = (string) $key;
				$display_attribute    = $attribute_value_label( (string) $key, $value );
				break;
			}
		}

		if ( '' === $display_attribute && ! empty( $attributes ) ) {
			$display_attribute_id = (string) array_key_first( $attributes );
			$display_attribute    = $attribute_value_label( $display_attribute_id, $attributes[ $display_attribute_id ] );
		}

		if ( '' === $display_attribute ) {
			$display_attribute = $variation_product->get_name();
		}

		$is_available = (bool) ( $variation_data['is_purchasable'] ?? true ) && (bool) ( $variation_data['is_in_stock'] ?? true );
		$price        = (float) ( $variation_data['display_price'] ?? $variation_product->get_price() );

		$option = array(
			'id'            => $variation_id,
			'label'         => $display_attribute,
			'button_label'  => strtoupper( preg_replace( '/\s+/', '', $display_attribute ) ),
			'key'           => $normalize_key( $display_attribute ),
			'attributes'    => $attributes,
			'available'     => $is_available,
			'price'         => $price,
			'price_html'    => wc_price( $price ),
			'price_text'    => $format_money_text( $price ),
			'attribute_id'  => $display_attribute_id,
			'variation_obj' => $variation_product,
		);

		$variation_options[] = $option;

		if ( null === $selected_variation && $is_available ) {
			$selected_variation = $option;
			$selected_attrs     = $attributes;
		}
	}

	if ( null === $selected_variation && ! empty( $variation_options ) ) {
		$selected_variation = $variation_options[0];
		$selected_attrs     = $selected_variation['attributes'];
	}
}

$selected_price_html = $selected_variation ? $selected_variation['price_html'] : $product->get_price_html();
$selected_price_text = $selected_variation ? $selected_variation['price_text'] : html_entity_decode( wp_strip_all_tags( $product->get_price_html() ), ENT_QUOTES, get_bloginfo( 'charset' ) );
$selected_label      = $selected_variation['label'] ?? $dosage_summary;
$selected_key        = $normalize_key( $selected_label );
$selected_product_id = $selected_variation['id'] ?? $product_id;
$cart_quantity       = 0;

if ( function_exists( 'WC' ) && WC()->cart ) {
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$cart_product_id   = absint( $cart_item['product_id'] ?? 0 );
		$cart_variation_id = absint( $cart_item['variation_id'] ?? 0 );

		if ( $selected_variation && $cart_variation_id === (int) $selected_variation['id'] ) {
			$cart_quantity += absint( $cart_item['quantity'] ?? 0 );
		} elseif ( ! $selected_variation && $cart_product_id === $product_id ) {
			$cart_quantity += absint( $cart_item['quantity'] ?? 0 );
		}
	}
}

$thumbnail_id  = $product->get_image_id();
$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );

$batch_cards = array();

if ( function_exists( 'simms_get_product_coa_batches' ) ) {
	$batches = simms_get_product_coa_batches( $product_id );

	foreach ( $batches as $batch ) {
		$batch_id       = $batch instanceof WP_Post ? $batch->ID : 0;
		$variant_label  = get_post_meta( $batch_id, '_simms_variant_label', true );
		$variant_key    = $normalize_key( $variant_label ?: 'default' );
		$is_current     = (bool) get_post_meta( $batch_id, '_simms_is_current', true );
		$existing       = $batch_cards[ $variant_key ] ?? null;
		$existing_is_current = $existing ? (bool) $existing['is_current'] : false;

		if ( $existing && $existing_is_current && ! $is_current ) {
			continue;
		}

		$labeled_content = get_post_meta( $batch_id, '_simms_labeled_content', true );
		$net_content     = get_post_meta( $batch_id, '_simms_net_content', true );
		$delta           = $format_net_delta( $labeled_content, $net_content, get_post_meta( $batch_id, '_simms_net_content_delta', true ) );
		$purity          = $format_percent( get_post_meta( $batch_id, '_simms_purity', true ) );
		$avg_purity      = $format_percent( get_post_meta( $batch_id, '_simms_avg_purity', true ) );
		$method          = get_post_meta( $batch_id, '_simms_test_type', true );
		$coa_file_id     = absint( get_post_meta( $batch_id, '_simms_coa_file_id', true ) );
		$coa_url         = $coa_file_id ? wp_get_attachment_url( $coa_file_id ) : '';

		if ( '' === $coa_url ) {
			$coa_url = get_post_meta( $batch_id, '_simms_coa_url', true );
		}

		$batch_cards[ $variant_key ] = array(
			'is_current'       => $is_current,
			'variant_label'    => $variant_label,
			'purity'           => $purity,
			'purity_pill'      => $purity ? $purity . ' Pure' : '',
			'avg_purity'       => $avg_purity ?: $purity,
			'labeled_content'  => $labeled_content,
			'vials_tested'     => get_post_meta( $batch_id, '_simms_vials_tested', true ),
			'net_content'      => $net_content,
			'net_delta'        => $delta['label'],
			'net_delta_class'  => $delta['negative'] ? ' batch-verification__delta--negative' : '',
			'endotoxins'       => get_post_meta( $batch_id, '_simms_endotoxins', true ),
			'method'           => $method,
			'tested_at'        => $format_test_date( get_post_meta( $batch_id, '_simms_tested_at', true ) ),
			'coa_url'          => $coa_url,
			'verification'     => $method ? $method . ' verified' : __( 'Third-party verified', 'simms-research' ),
		);
	}
}

$default_batch_key = isset( $batch_cards[ $selected_key ] ) ? $selected_key : array_key_first( $batch_cards );
$related_ids       = wc_get_related_products( $product_id, 4 );

if ( count( $related_ids ) < 4 ) {
	$fallback_products = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => 4 - count( $related_ids ),
			'exclude' => array_merge( array( $product_id ), $related_ids ),
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'ids',
		)
	);
	$related_ids       = array_merge( $related_ids, $fallback_products );
}

$pdp_css_rel  = '/assets/css/simms-pdp.css';
$pdp_js_rel   = '/assets/js/pdp.js';
$pdp_css_path = SIMMS_THEME_DIR . $pdp_css_rel;
$pdp_js_path  = SIMMS_THEME_DIR . $pdp_js_rel;
$pdp_css_ver  = file_exists( $pdp_css_path ) ? (string) filemtime( $pdp_css_path ) : SIMMS_THEME_VERSION;
$pdp_js_ver   = file_exists( $pdp_js_path ) ? (string) filemtime( $pdp_js_path ) : SIMMS_THEME_VERSION;
?>
<link rel="stylesheet" href="<?php echo esc_url( SIMMS_THEME_URI . $pdp_css_rel . '?ver=' . $pdp_css_ver ); ?>">
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'pdp color-scheme-1', $product ); ?>>
	<div class="pdp__main">
		<div class="pdp__media">
			<div class="pdp__gallery">
				<?php woocommerce_show_product_sale_flash(); ?>
				<?php woocommerce_show_product_images(); ?>
			</div>
		</div>

		<div class="pdp__details">
			<div class="pdp__summary">
				<?php if ( '' !== $spec_line ) : ?>
					<p class="pdp__eyebrow"><?php echo wp_kses_post( $spec_line ); ?></p>
				<?php endif; ?>
				<h1 class="pdp__title"><?php the_title(); ?></h1>
				<div class="pdp__price" data-pdp-price><?php echo $selected_price_html ? wp_kses_post( $selected_price_html ) : esc_html__( 'Pricing coming soon', 'simms-research' ); ?></div>
				<div class="pdp__trust-badges">
					<span><?php esc_html_e( 'COA Tested', 'simms-research' ); ?></span>
					<span><?php echo esc_html( $purity_summary ); ?></span>
					<span><?php esc_html_e( 'Research Grade', 'simms-research' ); ?></span>
				</div>
			</div>

			<div class="pdp__cart">
				<form class="cart pdp__form" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data" data-pdp-form>
					<?php if ( ! empty( $variation_options ) ) : ?>
						<fieldset class="pdp__variant-picker">
							<legend><?php esc_html_e( 'Dosage', 'simms-research' ); ?></legend>
							<div class="pdp__variant-options" role="group" aria-label="<?php esc_attr_e( 'Choose dosage', 'simms-research' ); ?>">
								<?php foreach ( $variation_options as $option ) : ?>
									<button
										type="button"
										class="pdp__variant-button<?php echo (int) $option['id'] === (int) ( $selected_variation['id'] ?? 0 ) ? ' is-active' : ''; ?>"
										data-pdp-variant
										data-variation-id="<?php echo esc_attr( (string) $option['id'] ); ?>"
										data-variant-key="<?php echo esc_attr( $option['key'] ); ?>"
										data-variant-label="<?php echo esc_attr( $option['label'] ); ?>"
										data-price="<?php echo esc_attr( $option['price_html'] ); ?>"
										data-price-text="<?php echo esc_attr( $option['price_text'] ); ?>"
										data-attributes="<?php echo esc_attr( wp_json_encode( $option['attributes'] ) ); ?>"
										aria-pressed="<?php echo (int) $option['id'] === (int) ( $selected_variation['id'] ?? 0 ) ? 'true' : 'false'; ?>"
										<?php disabled( ! $option['available'] ); ?>
									>
										<?php echo esc_html( $option['button_label'] ); ?>
									</button>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product_id ); ?>">
						<input type="hidden" name="variation_id" value="<?php echo esc_attr( (string) ( $selected_variation['id'] ?? 0 ) ); ?>" data-pdp-variation-id>
						<?php foreach ( $selected_attrs as $attr_name => $attr_value ) : ?>
							<input type="hidden" name="<?php echo esc_attr( $attr_name ); ?>" value="<?php echo esc_attr( $attr_value ); ?>" data-pdp-attribute="<?php echo esc_attr( $attr_name ); ?>">
						<?php endforeach; ?>
					<?php else : ?>
						<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product_id ); ?>">
					<?php endif; ?>

					<div class="volume-discount-table" data-pdp-bundle>
						<span class="volume-discount-table__heading"><?php esc_html_e( 'Bundle & Save', 'simms-research' ); ?></span>
						<div class="volume-discount-table__tiers">
							<button type="button" class="volume-discount-table__tier is-active" data-pdp-bundle-tier="1" aria-pressed="true">
								<span class="volume-discount-table__pct"><?php esc_html_e( '1 vial', 'simms-research' ); ?></span>
							</button>
							<button type="button" class="volume-discount-table__tier" data-pdp-bundle-tier="3" aria-pressed="false">
								<span class="volume-discount-table__badge volume-discount-table__badge--popular"><?php esc_html_e( 'Most Popular', 'simms-research' ); ?></span>
								<span class="volume-discount-table__pct"><?php esc_html_e( '3+ vials', 'simms-research' ); ?></span>
								<span class="volume-discount-table__qty"><?php esc_html_e( '5% off', 'simms-research' ); ?></span>
							</button>
							<button type="button" class="volume-discount-table__tier" data-pdp-bundle-tier="6" aria-pressed="false">
								<span class="volume-discount-table__badge volume-discount-table__badge--value"><?php esc_html_e( 'Best Value', 'simms-research' ); ?></span>
								<span class="volume-discount-table__pct"><?php esc_html_e( '6+ vials', 'simms-research' ); ?></span>
								<span class="volume-discount-table__qty"><?php esc_html_e( '10% off', 'simms-research' ); ?></span>
							</button>
						</div>
					</div>

					<label class="pdp__quantity-label" for="pdp-quantity-<?php echo esc_attr( (string) $product_id ); ?>" data-pdp-quantity-label>
						<?php echo esc_html( $cart_quantity > 0 ? sprintf( __( 'Quantity (%d in cart)', 'simms-research' ), $cart_quantity ) : __( 'Quantity', 'simms-research' ) ); ?>
					</label>

					<div class="pdp__purchase-row">
						<div class="pdp__quantity-control">
							<button type="button" data-pdp-qty-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'simms-research' ); ?>">-</button>
							<input id="pdp-quantity-<?php echo esc_attr( (string) $product_id ); ?>" class="qty" type="number" name="quantity" value="1" min="1" step="1" inputmode="numeric" data-pdp-quantity>
							<button type="button" data-pdp-qty-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'simms-research' ); ?>">+</button>
						</div>
						<button type="submit" name="add-to-cart" value="<?php echo esc_attr( (string) $product_id ); ?>" class="single_add_to_cart_button button pdp__add-button" data-pdp-submit>
							<span class="pdp__button-icon" aria-hidden="true"><?php echo simms_inline_icon( 'add-to-cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php esc_html_e( 'Add to cart', 'simms-research' ); ?>
						</button>
					</div>

					<button type="button" class="pdp__paypal-button" data-pdp-express>
						<span><?php esc_html_e( 'Pay with', 'simms-research' ); ?></span>
						<strong><?php esc_html_e( 'PayPal', 'simms-research' ); ?></strong>
					</button>
					<a class="pdp__payment-options" href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) ); ?>"><?php esc_html_e( 'More payment options', 'simms-research' ); ?></a>
				</form>
			</div>

			<div class="product-shipping-strip">
				<div class="product-shipping-strip__item product-shipping-strip__item--delivery">
					<span class="product-shipping-strip__icon" aria-hidden="true"><?php echo simms_inline_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="product-shipping-strip__estimate"><?php echo wp_kses_post( $delivery_window ); ?></span>
				</div>
				<div class="product-shipping-strip__item">
					<span class="product-shipping-strip__icon" aria-hidden="true"><?php echo simms_inline_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="product-shipping-strip__label"><?php esc_html_e( 'Shipment protection', 'simms-research' ); ?></span>
				</div>
				<div class="product-shipping-strip__item">
					<span class="product-shipping-strip__icon" aria-hidden="true"><?php echo simms_inline_icon( 'zap' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="product-shipping-strip__label"><?php esc_html_e( '2-day shipping', 'simms-research' ); ?></span>
				</div>
			</div>

			<div class="product-payment-row">
				<span class="product-payment-row__label"><?php esc_html_e( 'We accept', 'simms-research' ); ?></span>
				<ul class="product-payment-row__list" role="list" aria-label="<?php esc_attr_e( 'Accepted payment methods', 'simms-research' ); ?>">
					<li class="product-payment-row__item" aria-label="Visa">
						<svg class="product-payment-row__brand" viewBox="0 0 40 13" role="img" aria-hidden="true"><text x="20" y="11" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="12" font-weight="700" font-style="italic" fill="#1434CB" letter-spacing="0.5">VISA</text></svg>
					</li>
					<li class="product-payment-row__item" aria-label="Mastercard">
						<svg class="product-payment-row__brand" viewBox="0 0 34 22" role="img" aria-hidden="true"><circle cx="13.5" cy="11" r="9" fill="#EB001B"/><circle cx="20.5" cy="11" r="9" fill="#F79E1B"/><path d="M17 4.2a9 9 0 0 0 0 13.6 9 9 0 0 0 0-13.6z" fill="#FF5F00"/></svg>
					</li>
					<li class="product-payment-row__item" aria-label="American Express">
						<svg class="product-payment-row__brand" viewBox="0 0 40 24" role="img" aria-hidden="true"><rect width="40" height="24" rx="3" fill="#1F72CD"/><text x="20" y="15" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="8" font-weight="700" fill="#ffffff" letter-spacing="0.3">AMEX</text></svg>
					</li>
					<li class="product-payment-row__item" aria-label="Discover">
						<svg class="product-payment-row__brand" viewBox="0 0 62 13" role="img" aria-hidden="true"><text x="0" y="11" font-family="Arial, Helvetica, sans-serif" font-size="10" font-weight="700" fill="#1A1A1A">DISC</text><circle cx="44" cy="7.5" r="5.5" fill="#F76E11"/><text x="51" y="11" font-family="Arial, Helvetica, sans-serif" font-size="10" font-weight="700" fill="#1A1A1A">VER</text></svg>
					</li>
				</ul>
			</div>

			<?php get_template_part( 'template-parts/product-research-details', null, array( 'product' => $product ) ); ?>
		</div>
	</div>

	<?php if ( ! empty( $batch_cards ) ) : ?>
		<div class="pdp__post-grid">
			<div class="pdp__coa" data-pdp-coa-list>
				<?php foreach ( $batch_cards as $batch_key => $batch_card ) : ?>
					<batch-verification class="batch-verification pdp__coa-card<?php echo $batch_key === $default_batch_key ? ' is-active' : ''; ?>" data-pdp-coa-key="<?php echo esc_attr( $batch_key ); ?>"<?php echo $batch_key === $default_batch_key ? '' : ' hidden'; ?>>
						<details class="batch-verification__panel" open>
							<summary class="batch-verification__summary">
								<span class="batch-verification__summary-main">
									<span class="batch-verification__status-dot" aria-hidden="true"></span>
									<span class="batch-verification__title"><?php esc_html_e( 'Certificate of Analysis', 'simms-research' ); ?></span>
								</span>
								<?php if ( '' !== $batch_card['purity_pill'] ) : ?>
									<span class="batch-verification__purity-pill"><?php echo esc_html( $batch_card['purity_pill'] ); ?></span>
								<?php endif; ?>
								<span class="batch-verification__chevron" aria-hidden="true"></span>
							</summary>
							<div class="batch-verification__body">
								<dl class="batch-verification__metrics">
									<?php if ( '' !== $batch_card['avg_purity'] ) : ?>
										<div class="batch-verification__metric">
											<dt><?php esc_html_e( 'Avg Purity', 'simms-research' ); ?></dt>
											<dd class="batch-verification__value"><?php echo esc_html( $batch_card['avg_purity'] ); ?></dd>
										</div>
									<?php endif; ?>
									<?php if ( '' !== $batch_card['labeled_content'] ) : ?>
										<div class="batch-verification__metric">
											<dt><?php esc_html_e( 'Labeled Content', 'simms-research' ); ?></dt>
											<dd><?php echo esc_html( $batch_card['labeled_content'] ); ?></dd>
										</div>
									<?php endif; ?>
									<?php if ( '' !== (string) $batch_card['vials_tested'] ) : ?>
										<div class="batch-verification__metric">
											<dt><?php esc_html_e( 'Vials Tested', 'simms-research' ); ?></dt>
											<dd><?php echo esc_html( (string) $batch_card['vials_tested'] ); ?></dd>
										</div>
									<?php endif; ?>
									<?php if ( '' !== $batch_card['net_content'] ) : ?>
										<div class="batch-verification__metric">
											<dt><?php esc_html_e( 'Net Content', 'simms-research' ); ?></dt>
											<dd class="batch-verification__value">
												<?php echo esc_html( $batch_card['net_content'] ); ?>
												<?php if ( '' !== $batch_card['net_delta'] ) : ?>
													<span class="batch-verification__delta<?php echo esc_attr( $batch_card['net_delta_class'] ); ?>"><?php echo esc_html( $batch_card['net_delta'] ); ?></span>
												<?php endif; ?>
											</dd>
										</div>
									<?php endif; ?>
									<?php if ( '' !== $batch_card['endotoxins'] ) : ?>
										<div class="batch-verification__metric">
											<dt><?php esc_html_e( 'Endotoxins', 'simms-research' ); ?></dt>
											<dd class="<?php echo str_contains( strtolower( $batch_card['endotoxins'] ), 'pass' ) ? 'batch-verification__value--verified' : ''; ?>"><?php echo esc_html( $batch_card['endotoxins'] ); ?></dd>
										</div>
									<?php endif; ?>
									<?php if ( '' !== $batch_card['method'] ) : ?>
										<div class="batch-verification__metric">
											<dt><?php esc_html_e( 'Confirmation Method', 'simms-research' ); ?></dt>
											<dd><?php echo esc_html( $batch_card['method'] ); ?></dd>
										</div>
									<?php endif; ?>
									<?php if ( '' !== $batch_card['tested_at'] ) : ?>
										<div class="batch-verification__metric">
											<dt><?php esc_html_e( 'Last Tested', 'simms-research' ); ?></dt>
											<dd><?php echo esc_html( $batch_card['tested_at'] ); ?></dd>
										</div>
									<?php endif; ?>
								</dl>
								<?php if ( '' !== $batch_card['coa_url'] ) : ?>
									<a class="batch-verification__button" href="<?php echo esc_url( $batch_card['coa_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Full COA', 'simms-research' ); ?></a>
								<?php endif; ?>
								<div class="batch-verification__footer">
									<span aria-hidden="true"></span>
									<p><?php echo esc_html( $batch_card['verification'] ); ?></p>
								</div>
							</div>
						</details>
					</batch-verification>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<section class="pdp__benefits" aria-label="<?php esc_attr_e( 'Store benefits', 'simms-research' ); ?>">
		<div class="pdp__benefits-inner">
			<div class="pdp__benefit">
				<span class="pdp__benefit-icon" aria-hidden="true"><?php echo simms_inline_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<strong><?php esc_html_e( 'Free shipping', 'simms-research' ); ?></strong>
				<span><?php esc_html_e( 'on orders over $200', 'simms-research' ); ?></span>
			</div>
			<div class="pdp__benefit">
				<span class="pdp__benefit-icon" aria-hidden="true"><?php echo simms_inline_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<strong><?php esc_html_e( 'Damage protection', 'simms-research' ); ?></strong>
				<span><?php esc_html_e( 'covered in transit', 'simms-research' ); ?></span>
			</div>
			<div class="pdp__benefit">
				<span class="pdp__benefit-icon" aria-hidden="true"><?php echo simms_inline_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<strong><?php esc_html_e( 'Secure checkout', 'simms-research' ); ?></strong>
				<span><?php esc_html_e( '256-bit SSL', 'simms-research' ); ?></span>
			</div>
		</div>
	</section>

	<?php if ( ! empty( $related_ids ) ) : ?>
		<section class="pdp__related">
			<div class="pdp__related-inner">
				<h2><?php esc_html_e( 'Frequently researched together', 'simms-research' ); ?></h2>
				<ul class="products pdp__related-grid">
					<?php
					foreach ( array_slice( $related_ids, 0, 4 ) as $related_id ) {
						$related_product = wc_get_product( $related_id );

						if ( $related_product instanceof WC_Product && $related_product->is_visible() ) {
							get_template_part( 'template-parts/product-card', null, array( 'product' => $related_product ) );
						}
					}
					?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<div class="pdp-sticky-cart" data-pdp-sticky-cart aria-hidden="true">
		<div class="pdp-sticky-cart__image">
			<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
		</div>
		<div class="pdp-sticky-cart__info">
			<strong><?php echo esc_html( $product->get_name() ); ?></strong>
			<span data-pdp-sticky-variant><?php echo esc_html( $selected_label ); ?></span>
		</div>
		<div class="pdp-sticky-cart__price" data-pdp-sticky-price><?php echo esc_html( $selected_price_text ); ?></div>
		<button type="button" class="pdp-sticky-cart__button" data-pdp-sticky-submit>
			<span aria-hidden="true"><?php echo simms_inline_icon( 'add-to-cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php esc_html_e( 'Add to cart', 'simms-research' ); ?>
		</button>
	</div>
</div>
<script src="<?php echo esc_url( SIMMS_THEME_URI . $pdp_js_rel . '?ver=' . $pdp_js_ver ); ?>" defer></script>
