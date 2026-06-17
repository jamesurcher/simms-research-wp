<?php
/**
 * Thankyou page.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package SimmsResearch\WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order simms-order-confirmation">
	<?php if ( $order ) : ?>
		<?php
		do_action( 'woocommerce_before_thankyou', $order->get_id() );

		$order_details_hook_priority = has_action( 'woocommerce_thankyou', 'woocommerce_order_details_table' );
		if ( false !== $order_details_hook_priority ) {
			remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', $order_details_hook_priority );
		}

		ob_start();
		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		do_action( 'woocommerce_thankyou', $order->get_id() );
		$additional_content = trim( (string) ob_get_clean() );

		if ( false !== $order_details_hook_priority ) {
			add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', $order_details_hook_priority );
		}
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>
			<section class="simms-order-confirmation__panel simms-order-confirmation__panel--failed" aria-labelledby="simms-order-failed-title">
				<span class="simms-order-confirmation__status-icon" aria-hidden="true"><?php echo simms_inline_icon( 'error' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<p class="simms-order-confirmation__eyebrow"><?php esc_html_e( 'Payment issue', 'simms-research' ); ?></p>
				<h1 id="simms-order-failed-title"><?php esc_html_e( 'We could not process this order', 'simms-research' ); ?></h1>
				<p><?php esc_html_e( 'The payment was declined by the bank or card issuer. Please try again or use a different payment method.', 'simms-research' ); ?></p>
				<div class="simms-order-confirmation__actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button simms-order-confirmation__button"><?php esc_html_e( 'Pay for order', 'woocommerce' ); ?></a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button simms-order-confirmation__button simms-order-confirmation__button--secondary"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
					<?php endif; ?>
				</div>
			</section>
		<?php else : ?>
			<?php
			$customer_name      = trim( (string) $order->get_billing_first_name() );
			$customer_name      = '' !== $customer_name ? $customer_name : __( 'there', 'simms-research' );
			$order_items        = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
			$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
			$show_downloads     = $order->has_downloadable_item() && $order->is_download_permitted();
			$show_shipping      = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
			$shipping_method    = $order->get_shipping_method();
			$billing_address    = $order->get_formatted_billing_address( esc_html__( 'N/A', 'woocommerce' ) );
			$shipping_address   = $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) );
			?>

			<section class="simms-order-confirmation__hero" aria-labelledby="simms-order-confirmed-title">
				<span class="simms-order-confirmation__status-icon" aria-hidden="true"><?php echo simms_inline_icon( 'checkmark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<p class="simms-order-confirmation__eyebrow"><?php esc_html_e( 'Order confirmation', 'simms-research' ); ?></p>
				<h1 id="simms-order-confirmed-title"><?php esc_html_e( 'Order confirmed', 'simms-research' ); ?></h1>
				<p class="simms-order-confirmation__thanks">
					<?php
					printf(
						/* translators: %s: Customer first name. */
						esc_html__( '%s, thank you for your order.', 'simms-research' ),
						esc_html( $customer_name )
					);
					?>
				</p>
				<p><?php esc_html_e( 'We have received your purchase and will email tracking details as soon as your package ships.', 'simms-research' ); ?></p>
			</section>

			<dl class="simms-order-confirmation__overview">
				<div>
					<dt><?php esc_html_e( 'Order number', 'woocommerce' ); ?></dt>
					<dd><?php echo esc_html( $order->get_order_number() ); ?></dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'Date', 'woocommerce' ); ?></dt>
					<dd><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></dd>
				</div>
				<?php if ( $order->get_billing_email() ) : ?>
					<div>
						<dt><?php esc_html_e( 'Email', 'woocommerce' ); ?></dt>
						<dd><?php echo esc_html( $order->get_billing_email() ); ?></dd>
					</div>
				<?php endif; ?>
				<div>
					<dt><?php esc_html_e( 'Total', 'woocommerce' ); ?></dt>
					<dd><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd>
				</div>
			</dl>

			<?php
			if ( $show_downloads ) {
				wc_get_template(
					'order/order-downloads.php',
					array(
						'downloads'  => $order->get_downloadable_items(),
						'show_title' => true,
					)
				);
			}
			?>

			<section class="simms-order-confirmation__section simms-order-confirmation__summary" aria-labelledby="simms-order-summary-title">
				<div class="simms-order-confirmation__section-head">
					<p class="simms-order-confirmation__eyebrow"><?php esc_html_e( 'Order summary', 'simms-research' ); ?></p>
					<h2 id="simms-order-summary-title"><?php esc_html_e( 'Purchase information', 'simms-research' ); ?></h2>
				</div>

				<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

				<div class="simms-order-confirmation__items">
					<?php
					do_action( 'woocommerce_order_details_before_order_table_items', $order );

					foreach ( $order_items as $item_id => $item ) :
						if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
							continue;
						}

						$product           = $item->get_product();
						$is_visible        = $product && $product->is_visible();
						$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $order );
						$qty               = $item->get_quantity();
						$refunded_qty      = $order->get_qty_refunded_for_item( $item_id );
						$qty_display       = $refunded_qty ? '<del>' . esc_html( (string) $qty ) . '</del> <ins>' . esc_html( (string) ( $qty - ( $refunded_qty * -1 ) ) ) . '</ins>' : esc_html( (string) $qty );
						$item_meta         = wc_display_item_meta(
							$item,
							array(
								'before'       => '<ul class="simms-order-confirmation__item-meta"><li>',
								'after'        => '</li></ul>',
								'separator'    => '</li><li>',
								'echo'         => false,
								'label_before' => '<span>',
								'label_after'  => ':</span> ',
							)
						);
						?>
						<article class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'simms-order-confirmation__item order_item', $item, $order ) ); ?>">
							<span class="simms-order-confirmation__item-media">
								<?php
								if ( $product ) {
									echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'simms-order-confirmation__product-image' ) ) );
								} else {
									echo wp_kses_post( wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'simms-order-confirmation__product-image' ) ) );
								}
								?>
							</span>
							<span class="simms-order-confirmation__item-body">
								<h3>
									<?php
									echo wp_kses_post(
										apply_filters(
											'woocommerce_order_item_name',
											$product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), esc_html( $item->get_name() ) ) : esc_html( $item->get_name() ),
											$item,
											$is_visible
										)
									);
									?>
								</h3>
								<span class="simms-order-confirmation__quantity">
									<?php
									printf(
										/* translators: %s: Product quantity. */
										wp_kses_post( __( 'Quantity %s', 'simms-research' ) ),
										wp_kses_post( $qty_display )
									);
									?>
								</span>
								<?php do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false ); ?>
								<?php echo wp_kses_post( $item_meta ); ?>
								<?php do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false ); ?>
							</span>
							<strong class="simms-order-confirmation__item-total"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></strong>
						</article>

						<?php if ( $show_purchase_note && $product && $product->get_purchase_note() ) : ?>
							<div class="simms-order-confirmation__purchase-note">
								<?php echo wp_kses_post( wpautop( do_shortcode( $product->get_purchase_note() ) ) ); ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>

					<?php do_action( 'woocommerce_order_details_after_order_table_items', $order ); ?>
				</div>

				<div class="simms-order-confirmation__totals">
					<h3><?php esc_html_e( 'Order total', 'simms-research' ); ?></h3>
					<dl>
						<?php foreach ( $order->get_order_item_totals() as $key => $total ) : ?>
							<?php if ( 'payment_method' === $key ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<div class="<?php echo 'order_total' === $key ? 'is-total' : ''; ?>">
								<dt><?php echo esc_html( wp_strip_all_tags( $total['label'] ) ); ?></dt>
								<dd><?php echo wp_kses_post( $total['value'] ); ?></dd>
							</div>
						<?php endforeach; ?>
						<?php if ( $order->get_customer_note() ) : ?>
							<div>
								<dt><?php esc_html_e( 'Note', 'woocommerce' ); ?></dt>
								<dd><?php echo wp_kses( nl2br( wc_wptexturize_order_note( $order->get_customer_note() ) ), array( 'br' => array() ) ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				</div>

				<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
			</section>

			<?php do_action( 'woocommerce_after_order_details', $order ); ?>

			<section class="simms-order-confirmation__section simms-order-confirmation__addresses" aria-labelledby="simms-order-addresses-title">
				<div class="simms-order-confirmation__section-head">
					<p class="simms-order-confirmation__eyebrow"><?php esc_html_e( 'Billing and shipping', 'simms-research' ); ?></p>
					<h2 id="simms-order-addresses-title"><?php esc_html_e( 'Order destination', 'simms-research' ); ?></h2>
				</div>
				<div class="simms-order-confirmation__address-grid">
					<div class="simms-order-confirmation__detail-card">
						<h3><?php esc_html_e( 'Billing address', 'woocommerce' ); ?></h3>
						<address>
							<?php echo wp_kses_post( $billing_address ); ?>
							<?php if ( $order->get_billing_phone() ) : ?>
								<span><?php echo esc_html( $order->get_billing_phone() ); ?></span>
							<?php endif; ?>
							<?php if ( $order->get_billing_email() ) : ?>
								<span><?php echo esc_html( $order->get_billing_email() ); ?></span>
							<?php endif; ?>
							<?php do_action( 'woocommerce_order_details_after_customer_address', 'billing', $order ); ?>
						</address>
					</div>

					<?php if ( $show_shipping ) : ?>
						<div class="simms-order-confirmation__detail-card">
							<h3><?php esc_html_e( 'Shipping address', 'woocommerce' ); ?></h3>
							<address>
								<?php echo wp_kses_post( $shipping_address ); ?>
								<?php if ( $order->get_shipping_phone() ) : ?>
									<span><?php echo esc_html( $order->get_shipping_phone() ); ?></span>
								<?php endif; ?>
								<?php do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order ); ?>
							</address>
						</div>
					<?php endif; ?>
				</div>
				<?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
			</section>

			<section class="simms-order-confirmation__section simms-order-confirmation__methods" aria-label="<?php esc_attr_e( 'Payment and shipping methods', 'simms-research' ); ?>">
				<?php if ( $order->get_payment_method_title() ) : ?>
					<div class="simms-order-confirmation__detail-card">
						<span class="simms-order-confirmation__detail-icon" aria-hidden="true"><?php echo simms_inline_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<h3><?php esc_html_e( 'Payment method', 'woocommerce' ); ?></h3>
						<p><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></p>
					</div>
				<?php endif; ?>
				<div class="simms-order-confirmation__detail-card">
					<span class="simms-order-confirmation__detail-icon" aria-hidden="true"><?php echo simms_inline_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<h3><?php esc_html_e( 'Shipping method', 'woocommerce' ); ?></h3>
					<p><?php echo esc_html( '' !== $shipping_method ? $shipping_method : __( 'To be confirmed', 'simms-research' ) ); ?></p>
				</div>
			</section>

			<div class="simms-order-confirmation__actions">
				<a class="button simms-order-confirmation__button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Continue shopping', 'simms-research' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a class="button simms-order-confirmation__button simms-order-confirmation__button--secondary" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'View orders', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $additional_content ) : ?>
				<section class="simms-order-confirmation__additional">
					<?php echo $additional_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</section>
			<?php endif; ?>
		<?php endif; ?>
	<?php else : ?>
		<section class="simms-order-confirmation__hero simms-order-confirmation__hero--compact" aria-labelledby="simms-order-received-title">
			<span class="simms-order-confirmation__status-icon" aria-hidden="true"><?php echo simms_inline_icon( 'checkmark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<p class="simms-order-confirmation__eyebrow"><?php esc_html_e( 'Order confirmation', 'simms-research' ); ?></p>
			<h1 id="simms-order-received-title"><?php esc_html_e( 'Order received', 'woocommerce' ); ?></h1>
			<p><?php echo wp_kses_post( apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), false ) ); ?></p>
		</section>
	<?php endif; ?>
</div>
