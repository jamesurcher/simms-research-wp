<?php
/**
 * WooCommerce cart drawer contents.
 */

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

WC()->cart->calculate_totals();

$cart              = WC()->cart;
$cart_count        = $cart->get_cart_contents_count();
$cart_items        = $cart->get_cart();
$checkout_url      = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
$cart_url          = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$shipping_threshold = 200.0;
$qualifying_total  = (float) $cart->get_cart_contents_total();
$remaining         = max( 0, $shipping_threshold - $qualifying_total );
$progress          = $shipping_threshold > 0 ? min( 100, ( $qualifying_total / $shipping_threshold ) * 100 ) : 0;
$applied_coupons   = $cart->get_applied_coupons();
?>
<div class="simms-cart-drawer__header">
	<h2 id="simms-cart-drawer-title"><?php esc_html_e( 'Cart', 'simms-research' ); ?></h2>
	<span class="simms-cart-drawer__count" data-simms-cart-heading-count><?php echo esc_html( (string) $cart_count ); ?></span>
	<button class="simms-cart-drawer__close" type="button" aria-label="<?php esc_attr_e( 'Close cart', 'simms-research' ); ?>" data-simms-cart-close>
		<?php echo simms_inline_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
</div>

<div class="simms-cart-drawer__notices" data-simms-cart-notices></div>

<?php if ( empty( $cart_items ) ) : ?>
	<div class="simms-cart-drawer__empty">
		<p class="simms-cart-drawer__empty-title"><?php esc_html_e( 'Your cart is empty.', 'simms-research' ); ?></p>
		<p><?php esc_html_e( 'Add research compounds to review them here before checkout.', 'simms-research' ); ?></p>
		<a class="simms-cart-drawer__checkout" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop products', 'simms-research' ); ?></a>
	</div>
<?php else : ?>
	<div class="simms-cart-drawer__items">
		<?php foreach ( $cart_items as $cart_item_key => $cart_item ) : ?>
			<?php
			$product = $cart_item['data'] ?? null;
			if ( ! $product instanceof WC_Product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
				continue;
			}

			$parent_id         = $product->is_type( 'variation' ) ? $product->get_parent_id() : 0;
			$link_product      = $parent_id > 0 ? wc_get_product( $parent_id ) : $product;
			$product_name      = $parent_id > 0 ? get_the_title( $parent_id ) : $product->get_name();
			$product_permalink = $link_product instanceof WC_Product && $link_product->is_visible() ? $link_product->get_permalink() : '';
			$thumbnail         = $product->get_image( 'woocommerce_thumbnail' );
			$line_subtotal     = (float) $cart_item['line_subtotal'] + (float) $cart_item['line_subtotal_tax'];
			$line_total        = (float) $cart_item['line_total'] + (float) $cart_item['line_tax'];
			$has_discount      = $line_subtotal > $line_total;
			$meta_parts        = array();

			foreach ( (array) ( $cart_item['variation'] ?? array() ) as $attribute_name => $attribute_value ) {
				if ( '' === $attribute_value ) {
					continue;
				}

				$taxonomy = str_replace( 'attribute_', '', (string) $attribute_name );
				$label    = (string) $attribute_value;

				if ( taxonomy_exists( $taxonomy ) ) {
					$term = get_term_by( 'slug', $attribute_value, $taxonomy );
					if ( $term && ! is_wp_error( $term ) ) {
						$label = $term->name;
					}
				}

				$meta_parts[] = $label;
			}

			if ( empty( $meta_parts ) ) {
				$dosage = simms_product_dosage_summary( $product );
				if ( '' !== $dosage ) {
					$meta_parts[] = $dosage;
				}
			}

			$meta_parts = array_unique( array_filter( array_map( 'trim', $meta_parts ) ) );
			?>
			<article class="simms-cart-item" data-simms-cart-item="<?php echo esc_attr( $cart_item_key ); ?>">
				<a class="simms-cart-item__image" href="<?php echo esc_url( $product_permalink ?: $cart_url ); ?>" tabindex="-1">
					<?php echo wp_kses_post( $thumbnail ); ?>
				</a>
				<div class="simms-cart-item__main">
					<div class="simms-cart-item__top">
						<div>
							<h3 class="simms-cart-item__title">
								<?php if ( $product_permalink ) : ?>
									<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product_name ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $product_name ); ?>
								<?php endif; ?>
							</h3>
							<?php if ( ! empty( $meta_parts ) ) : ?>
								<p class="simms-cart-item__meta"><?php echo esc_html( implode( ' · ', $meta_parts ) ); ?></p>
							<?php endif; ?>
						</div>
						<div class="simms-cart-item__price">
							<?php echo wp_kses_post( wc_price( $line_total ) ); ?>
							<?php if ( $has_discount ) : ?>
								<del><?php echo wp_kses_post( wc_price( $line_subtotal ) ); ?></del>
							<?php endif; ?>
						</div>
					</div>

					<div class="simms-cart-item__controls">
						<div class="simms-cart-qty" aria-label="<?php esc_attr_e( 'Quantity', 'simms-research' ); ?>">
							<button type="button" data-simms-cart-qty="<?php echo esc_attr( $cart_item_key ); ?>" data-quantity="<?php echo esc_attr( max( 0, (int) $cart_item['quantity'] - 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Decrease quantity', 'simms-research' ); ?>">−</button>
							<input type="number" min="0" step="1" value="<?php echo esc_attr( (string) $cart_item['quantity'] ); ?>" inputmode="numeric" data-simms-cart-qty-input="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Item quantity', 'simms-research' ); ?>">
							<button type="button" data-simms-cart-qty="<?php echo esc_attr( $cart_item_key ); ?>" data-quantity="<?php echo esc_attr( (int) $cart_item['quantity'] + 1 ); ?>" aria-label="<?php esc_attr_e( 'Increase quantity', 'simms-research' ); ?>">+</button>
						</div>
						<button class="simms-cart-item__remove" type="button" data-simms-cart-qty="<?php echo esc_attr( $cart_item_key ); ?>" data-quantity="0" aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'simms-research' ), $product_name ) ); ?>">
							<?php echo simms_inline_icon( 'delete' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="simms-cart-drawer__summary">
		<details class="simms-cart-discount">
			<summary>
				<span><?php esc_html_e( 'Discount', 'simms-research' ); ?></span>
				<span aria-hidden="true">+</span>
			</summary>
			<form class="simms-cart-discount__form" data-simms-cart-coupon>
				<label class="screen-reader-text" for="simms-cart-coupon"><?php esc_html_e( 'Discount code', 'simms-research' ); ?></label>
				<input id="simms-cart-coupon" type="text" name="coupon_code" placeholder="<?php esc_attr_e( 'Discount code', 'simms-research' ); ?>" autocomplete="off">
				<button type="submit"><?php esc_html_e( 'Apply', 'simms-research' ); ?></button>
			</form>
			<?php if ( ! empty( $applied_coupons ) ) : ?>
				<ul class="simms-cart-discount__applied">
					<?php foreach ( $applied_coupons as $coupon_code ) : ?>
						<li>
							<span><?php echo esc_html( wc_format_coupon_code( $coupon_code ) ); ?></span>
							<button type="button" data-simms-remove-coupon="<?php echo esc_attr( $coupon_code ); ?>"><?php esc_html_e( 'Remove', 'simms-research' ); ?></button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</details>

		<div class="simms-cart-shipping">
			<div class="simms-cart-shipping__label">
				<?php if ( $remaining > 0 ) : ?>
					<span><?php echo wp_kses_post( sprintf( __( '%s away from free shipping', 'simms-research' ), wc_price( $remaining ) ) ); ?></span>
				<?php else : ?>
					<span><?php esc_html_e( 'Free shipping unlocked', 'simms-research' ); ?></span>
				<?php endif; ?>
				<span><?php echo wp_kses_post( sprintf( __( '%s threshold', 'simms-research' ), wc_price( $shipping_threshold ) ) ); ?></span>
			</div>
			<div class="simms-cart-shipping__track" aria-hidden="true">
				<span style="width: <?php echo esc_attr( (string) $progress ); ?>%;"></span>
			</div>
		</div>

		<div class="simms-cart-total">
			<span><?php esc_html_e( 'Estimated total', 'simms-research' ); ?></span>
			<strong><?php echo wp_kses_post( $cart->get_total() ); ?></strong>
		</div>
		<p class="simms-cart-drawer__tax-note"><?php esc_html_e( 'Taxes and shipping calculated at checkout.', 'simms-research' ); ?></p>
		<a class="simms-cart-drawer__checkout" href="<?php echo esc_url( $checkout_url ); ?>"><?php esc_html_e( 'Check out', 'simms-research' ); ?></a>
		<a class="simms-cart-drawer__view-cart" href="<?php echo esc_url( $cart_url ); ?>"><?php esc_html_e( 'View cart', 'simms-research' ); ?></a>
	</div>
<?php endif; ?>
