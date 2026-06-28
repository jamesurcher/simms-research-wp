<?php
/**
 * Pay for order form.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.2.0
 */

defined( 'ABSPATH' ) || exit;

$totals              = $order->get_order_item_totals(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$billing_address     = $order->get_formatted_billing_address();
$shipping_address    = $order->get_formatted_shipping_address();
$delivery_address    = $shipping_address ?: $billing_address;
$billing_email       = $order->get_billing_email();
$billing_phone       = $order->get_billing_phone();
$shipping_phone      = method_exists( $order, 'get_shipping_phone' ) ? $order->get_shipping_phone() : '';
$delivery_phone      = $shipping_phone ?: $billing_phone;
$shipping_items      = $order->get_items( 'shipping' );
$discount_total      = (float) $order->get_discount_total() + (float) $order->get_discount_tax();
$order_total_row     = $totals['order_total'] ?? null;

/**
 * Fire the pay-page hook so payment plugins can still register their assets, but
 * discard its markup. WooCommerce PayPal Payments prints an express-button
 * container here that never paints on the order-pay endpoint, which left a hollow
 * "Express Checkout" box on the recovery page. Customers still pay with PayPal
 * (and every other gateway) via the payment-method list in the Payment section.
 */
ob_start();
do_action( 'woocommerce_pay_order_before_payment' );
ob_end_clean();
?>

<form id="order_review" class="wc-block-checkout simms-order-pay-checkout" method="post">
	<div class="wc-block-checkout__main simms-order-pay-checkout__main">
		<div class="wc-block-checkout__form simms-order-pay-checkout__form">
			<?php if ( $billing_email ) : ?>
				<section class="simms-order-pay-section">
					<h2><?php esc_html_e( 'Contact', 'simms-research' ); ?></h2>
					<div class="simms-order-pay-card simms-order-pay-card--field">
						<span><?php esc_html_e( 'Email address', 'simms-research' ); ?></span>
						<strong><?php echo esc_html( $billing_email ); ?></strong>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $delivery_address ) : ?>
				<section class="simms-order-pay-section">
					<h2><?php esc_html_e( 'Delivery', 'simms-research' ); ?></h2>
					<div class="simms-order-pay-card">
						<address>
							<?php echo wp_kses_post( $delivery_address ); ?>
							<?php if ( $delivery_phone ) : ?>
								<br><?php echo esc_html( $delivery_phone ); ?>
							<?php endif; ?>
						</address>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $billing_address ) : ?>
				<section class="simms-order-pay-section">
					<h2><?php esc_html_e( 'Billing address', 'simms-research' ); ?></h2>
					<div class="simms-order-pay-card">
						<address>
							<?php echo wp_kses_post( $billing_address ); ?>
							<?php if ( $billing_phone ) : ?>
								<br><?php echo esc_html( $billing_phone ); ?>
							<?php endif; ?>
						</address>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $shipping_items ) ) : ?>
				<section class="simms-order-pay-section">
					<h2><?php esc_html_e( 'Shipping method', 'simms-research' ); ?></h2>
					<div class="simms-order-pay-options">
						<?php foreach ( $shipping_items as $shipping_item ) : ?>
							<?php
							$shipping_label = $shipping_item->get_name();
							$shipping_price = (float) $shipping_item->get_total() + (float) $shipping_item->get_total_tax();
							?>
							<div class="simms-order-pay-option is-selected">
								<span class="simms-order-pay-option__radio" aria-hidden="true"></span>
								<span class="simms-order-pay-option__label"><?php echo esc_html( $shipping_label ); ?></span>
								<span class="simms-order-pay-option__price">
									<?php echo $shipping_price > 0 ? wp_kses_post( wc_price( $shipping_price ) ) : esc_html__( 'Free', 'simms-research' ); ?>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<section class="simms-order-pay-section simms-order-pay-section--payment">
				<h2><?php esc_html_e( 'Payment', 'simms-research' ); ?></h2>

				<div id="payment" class="woocommerce-checkout-payment simms-order-pay-payment">
					<?php if ( $order->needs_payment() ) : ?>
						<ul class="wc_payment_methods payment_methods methods">
							<?php
							if ( ! empty( $available_gateways ) ) {
								foreach ( $available_gateways as $gateway ) {
									wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
								}
							} else {
								echo '<li>';
								wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ), 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
								echo '</li>';
							}
							?>
						</ul>
					<?php endif; ?>

					<div class="form-row place-order simms-order-pay-place-order">
						<input type="hidden" name="woocommerce_pay" value="1" />

						<?php wc_get_template( 'checkout/terms.php' ); ?>

						<?php do_action( 'woocommerce_pay_order_before_submit' ); ?>

						<?php echo apply_filters( 'woocommerce_pay_order_button_html', '<button type="submit" class="button alt' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // @codingStandardsIgnoreLine ?>

						<?php do_action( 'woocommerce_pay_order_after_submit' ); ?>

						<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
					</div>
				</div>
			</section>
		</div>
	</div>

	<aside class="wc-block-checkout__sidebar wc-block-components-sidebar simms-order-pay-summary" aria-label="<?php esc_attr_e( 'Order summary', 'simms-research' ); ?>">
		<div class="simms-order-pay-summary__inner">
			<h2><?php esc_html_e( 'Order summary', 'simms-research' ); ?></h2>

			<div class="simms-order-pay-items">
				<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
					<?php
					if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
						continue;
					}

					$product          = $item->get_product();
					$display_product  = $product instanceof WC_Product && $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
					$product_name     = $display_product instanceof WC_Product ? $display_product->get_name() : $item->get_name();
					$thumbnail        = $product instanceof WC_Product ? $product->get_image( 'woocommerce_thumbnail' ) : wc_placeholder_img( 'woocommerce_thumbnail' );
					$item_meta        = wc_display_item_meta( $item, array( 'echo' => false ) );
					$fallback_dosage  = '';

					if ( '' === trim( wp_strip_all_tags( $item_meta ) ) && $product instanceof WC_Product && function_exists( 'simms_product_dosage_summary' ) ) {
						$fallback_dosage = simms_product_dosage_summary( $product );
					}
					?>
					<article class="simms-order-pay-item">
						<div class="simms-order-pay-item__image">
							<?php echo wp_kses_post( $thumbnail ); ?>
							<span class="simms-order-pay-item__qty"><?php echo esc_html( (string) $item->get_quantity() ); ?></span>
						</div>
						<div class="simms-order-pay-item__body">
							<h3><?php echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $product_name, $item, false ) ); ?></h3>
							<p class="simms-order-pay-item__unit"><?php echo wp_kses_post( wc_price( $order->get_item_total( $item ) ) ); ?></p>
							<?php if ( '' !== trim( wp_strip_all_tags( $item_meta ) ) ) : ?>
								<div class="simms-order-pay-item__meta">
									<?php echo wp_kses_post( $item_meta ); ?>
								</div>
							<?php elseif ( $fallback_dosage ) : ?>
								<p class="simms-order-pay-item__meta"><?php echo esc_html( sprintf( __( 'Size: %s', 'simms-research' ), $fallback_dosage ) ); ?></p>
							<?php endif; ?>
						</div>
						<div class="simms-order-pay-item__price">
							<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="simms-order-pay-totals">
				<?php if ( $totals ) : ?>
					<?php foreach ( $totals as $total_key => $total ) : ?>
						<?php
						if ( in_array( $total_key, array( 'order_total', 'payment_method' ), true ) ) {
							continue;
						}
						?>
						<div class="simms-order-pay-total-row simms-order-pay-total-row--<?php echo esc_attr( sanitize_html_class( $total_key ) ); ?>">
							<span><?php echo esc_html( rtrim( wp_strip_all_tags( $total['label'] ), ':' ) ); ?></span>
							<strong><?php echo wp_kses_post( $total['value'] ); ?></strong>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( $order_total_row ) : ?>
					<div class="simms-order-pay-total-row simms-order-pay-total-row--order-total">
						<span><?php esc_html_e( 'Total', 'simms-research' ); ?></span>
						<strong><?php echo wp_kses_post( $order_total_row['value'] ); ?></strong>
					</div>
				<?php endif; ?>

				<?php if ( $discount_total > 0 ) : ?>
					<div class="simms-order-pay-total-row simms-order-pay-total-row--savings">
						<span><?php esc_html_e( 'Total savings', 'simms-research' ); ?></span>
						<strong><?php echo wp_kses_post( wc_price( $discount_total ) ); ?></strong>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</aside>
</form>
