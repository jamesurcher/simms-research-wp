<?php
/**
 * WooCommerce PayPal Payments compatibility fixes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The PayPal Payments block integration registers an additional non-express
 * "Proceed to PayPal" method by default. In plugin 4.0.4 that redirect path can
 * leave the Woo order without `_ppcp_paypal_order_id` before PayPal's approval
 * webhook arrives, causing a fatal in the webhook processor. The express buttons
 * remain enabled via PayPal's own location settings.
 */
add_filter( 'woocommerce_paypal_payments_blocks_add_place_order_method', '__return_false', 20 );

/**
 * WooCommerce Blocks Advanced Card creates the PayPal order before the final
 * Woo order exists. The PayPal plugin's block script does not include checkout
 * payer data in that create-order request, so PayPal can run card confirmation
 * with less customer context than Woo has on the checkout screen.
 */
function simms_paypal_add_customer_context_to_card_order( array $data, string $payment_method, array $request ): array {
	if ( 'ppcp-credit-card-gateway' !== $payment_method ) {
		return $data;
	}

	$context = isset( $request['context'] ) ? (string) $request['context'] : '';
	if ( ! in_array( $context, array( 'checkout', 'checkout-block' ), true ) ) {
		return $data;
	}

	$customer = function_exists( 'WC' ) ? WC()->customer : null;
	if ( ! $customer instanceof WC_Customer ) {
		return $data;
	}

	$payer    = isset( $data['payer'] ) && is_array( $data['payer'] ) ? $data['payer'] : array();
	$email    = sanitize_email( (string) $customer->get_billing_email() );

	if ( $email && is_email( $email ) && empty( $payer['email_address'] ) ) {
		$payer['email_address'] = $email;
	}

	$first_name = sanitize_text_field( (string) $customer->get_billing_first_name() );
	$last_name  = sanitize_text_field( (string) $customer->get_billing_last_name() );

	if ( $first_name || $last_name ) {
		$payer['name'] = array_filter(
			array(
				'given_name' => $first_name,
				'surname'    => $last_name,
			)
		);
	}

	$address = simms_paypal_customer_billing_address( $customer );
	if ( $address ) {
		$payer['address'] = $address;
	}

	$phone = simms_paypal_normalized_customer_phone( $customer );
	if ( $phone ) {
		$payer['phone'] = array(
			'phone_type'   => 'HOME',
			'phone_number' => array(
				'national_number' => $phone,
			),
		);

		if ( isset( $data['purchase_units'][0]['shipping'] ) && is_array( $data['purchase_units'][0]['shipping'] ) ) {
			$data['purchase_units'][0]['shipping']['phone_number'] = array(
				'national_number' => $phone,
			);
		}
	}

	if ( $payer ) {
		$data['payer'] = $payer;
	}

	return $data;
}
add_filter( 'ppcp_create_order_request_body_data', 'simms_paypal_add_customer_context_to_card_order', 20, 3 );

/**
 * Record PayPal webhook context on the Woo order for later investigation.
 *
 * @param bool     $process  Whether PayPal Payments should process the order.
 * @param mixed    $gateway  Gateway instance, or null from the webhook handler.
 * @param WC_Order $wc_order WooCommerce order.
 * @return bool
 */
function simms_paypal_record_webhook_diagnostics( bool $process, $gateway, WC_Order $wc_order ): bool {
	if ( ! simms_paypal_is_diagnostic_order( $wc_order ) ) {
		return $process;
	}

	$payload = simms_paypal_current_webhook_payload();
	if ( empty( $payload['id'] ) || empty( $payload['event_type'] ) ) {
		return $process;
	}

	$resource = isset( $payload['resource'] ) && is_array( $payload['resource'] ) ? $payload['resource'] : array();
	$event    = simms_paypal_build_order_diagnostics( $wc_order, 'paypal_webhook' );

	$event['webhook_event_id'] = sanitize_text_field( (string) $payload['id'] );
	$event['event_type']       = sanitize_text_field( (string) $payload['event_type'] );
	$event['resource_status']  = isset( $resource['status'] ) ? sanitize_text_field( (string) $resource['status'] ) : '';

	if ( ! empty( $resource['id'] ) ) {
		$resource_id = sanitize_text_field( (string) $resource['id'] );

		if ( 0 === strpos( $event['event_type'], 'PAYMENT.CAPTURE.' ) ) {
			$event['paypal_capture_id']     = $resource_id;
			$event['paypal_transaction_id'] = $resource_id;
		} elseif ( empty( $event['paypal_order_id'] ) ) {
			$event['paypal_order_id'] = $resource_id;
		}
	}

	if ( ! empty( $resource['supplementary_data']['related_ids']['order_id'] ) ) {
		$event['paypal_order_id'] = sanitize_text_field( (string) $resource['supplementary_data']['related_ids']['order_id'] );
	}

	if ( ! empty( $resource['network_transaction_reference']['network'] ) ) {
		$event['card_brand'] = sanitize_text_field( (string) $resource['network_transaction_reference']['network'] );
	}

	if ( ! empty( $resource['processor_response'] ) && is_array( $resource['processor_response'] ) ) {
		$event = array_merge( $event, simms_paypal_normalize_processor_response( $resource['processor_response'] ) );
	}

	if ( ! empty( $resource['seller_protection']['status'] ) ) {
		$event['seller_protection'] = sanitize_text_field( (string) $resource['seller_protection']['status'] );
	}

	simms_paypal_update_order_diagnostics( $wc_order, $event );

	return $process;
}
add_filter( 'woocommerce_paypal_payments_before_order_process', 'simms_paypal_record_webhook_diagnostics', 20, 3 );

/**
 * Record failed/recovered PayPal order state in internal notes and order meta.
 */
function simms_paypal_record_status_diagnostics( int $order_id, string $old_status, string $new_status, WC_Order $order ): void {
	if ( ! simms_paypal_is_diagnostic_order( $order ) ) {
		return;
	}

	$watched_statuses = array( 'failed', 'pending', 'on-hold', 'processing', 'completed' );
	if ( ! in_array( $new_status, $watched_statuses, true ) ) {
		return;
	}

	if ( 'pending' === $new_status && 'failed' !== $old_status ) {
		return;
	}

	$event               = simms_paypal_build_order_diagnostics( $order, 'woocommerce_status' );
	$event['old_status'] = sanitize_key( $old_status );
	$event['new_status'] = sanitize_key( $new_status );

	if ( in_array( $new_status, array( 'processing', 'completed' ), true ) && simms_paypal_order_has_processor_decline( $order ) ) {
		$event['retry_outcome'] = 'succeeded_after_prior_decline';
	}

	simms_paypal_update_order_diagnostics( $order, $event );

	if ( in_array( $new_status, array( 'failed', 'processing', 'completed' ), true ) ) {
		simms_paypal_add_diagnostics_note( $order, $event );
	}
}
add_action( 'woocommerce_order_status_changed', 'simms_paypal_record_status_diagnostics', 20, 4 );

function simms_paypal_is_diagnostic_order( WC_Order $order ): bool {
	return in_array(
		$order->get_payment_method(),
		array( 'ppcp-credit-card-gateway', 'ppcp-gateway' ),
		true
	);
}

/**
 * @return array<string,string>
 */
function simms_paypal_build_order_diagnostics( WC_Order $order, string $source ): array {
	$diagnostics = array(
		'recorded_at'           => current_time( 'mysql', true ),
		'source'                => sanitize_key( $source ),
		'woo_order_id'          => (string) $order->get_id(),
		'woo_status'            => $order->get_status(),
		'payment_method'        => $order->get_payment_method(),
		'paypal_order_id'       => sanitize_text_field( (string) $order->get_meta( '_ppcp_paypal_order_id' ) ),
		'paypal_transaction_id' => sanitize_text_field( (string) $order->get_transaction_id() ),
	);

	$fraud_result = simms_paypal_order_fraud_result( $order );
	if ( $fraud_result ) {
		$diagnostics = array_merge( $diagnostics, $fraud_result );
	}

	$decline = simms_paypal_latest_processor_decline( $order );
	if ( $decline ) {
		$diagnostics['latest_decline'] = $decline;
	}

	$debug_id = simms_paypal_latest_debug_id( $order );
	if ( $debug_id ) {
		$diagnostics['debug_id'] = $debug_id;
	}

	return array_filter(
		$diagnostics,
		static function ( $value ): bool {
			return null !== $value && '' !== $value && array() !== $value;
		}
	);
}

/**
 * @return array<string,string>
 */
function simms_paypal_order_fraud_result( WC_Order $order ): array {
	$result = $order->get_meta( '_ppcp_paypal_fraud_result' );

	if ( is_string( $result ) ) {
		$decoded = json_decode( $result, true );
		$result  = is_array( $decoded ) ? $decoded : array();
	}

	if ( is_object( $result ) ) {
		$result = (array) $result;
	}

	if ( ! is_array( $result ) ) {
		return array();
	}

	$diagnostics = simms_paypal_normalize_processor_response( $result );
	$card        = isset( $result['card'] ) && is_array( $result['card'] ) ? $result['card'] : $result;

	foreach ( array( 'brand', 'card_brand', 'network' ) as $key ) {
		if ( ! empty( $card[ $key ] ) ) {
			$diagnostics['card_brand'] = sanitize_text_field( (string) $card[ $key ] );
			break;
		}
	}

	foreach ( array( 'last_digits', 'last4', 'card_last4' ) as $key ) {
		if ( ! empty( $card[ $key ] ) ) {
			$diagnostics['card_last4'] = sanitize_text_field( (string) $card[ $key ] );
			break;
		}
	}

	return $diagnostics;
}

/**
 * @param array<string,mixed> $response PayPal processor/fraud response data.
 * @return array<string,string>
 */
function simms_paypal_normalize_processor_response( array $response ): array {
	$source = isset( $response['processor_response'] ) && is_array( $response['processor_response'] )
		? $response['processor_response']
		: $response;

	$fields = array(
		'avs_code'      => array( 'avs_code', 'avs' ),
		'cvv_code'      => array( 'cvv_code', 'cvv2_code', 'cvv' ),
		'response_code' => array( 'response_code', 'processor_response_code' ),
	);

	$diagnostics = array();

	foreach ( $fields as $target => $keys ) {
		foreach ( $keys as $key ) {
			if ( ! empty( $source[ $key ] ) ) {
				$diagnostics[ $target ] = sanitize_text_field( (string) $source[ $key ] );
				break;
			}
		}
	}

	return $diagnostics;
}

function simms_paypal_latest_processor_decline( WC_Order $order ): string {
	$notes = wc_get_order_notes(
		array(
			'order_id' => $order->get_id(),
			'type'     => 'internal',
			'limit'    => 20,
			'orderby'  => 'date_created',
			'order'    => 'DESC',
		)
	);

	foreach ( $notes as $note ) {
		$content = html_entity_decode( wp_strip_all_tags( (string) $note->content ) );
		$content = trim( $content );

		if ( 0 === strpos( $content, 'PayPal diagnostics:' ) ) {
			continue;
		}

		if ( false === strpos( $content, 'Payment declined by card processor:' ) ) {
			continue;
		}

		$content = preg_replace( '/\s+/', ' ', $content );

		return is_string( $content ) ? sanitize_text_field( $content ) : '';
	}

	return '';
}

function simms_paypal_order_has_processor_decline( WC_Order $order ): bool {
	return '' !== simms_paypal_latest_processor_decline( $order );
}

function simms_paypal_latest_debug_id( WC_Order $order ): string {
	$notes = wc_get_order_notes(
		array(
			'order_id' => $order->get_id(),
			'type'     => 'internal',
			'limit'    => 20,
			'orderby'  => 'date_created',
			'order'    => 'DESC',
		)
	);

	foreach ( $notes as $note ) {
		$content = html_entity_decode( wp_strip_all_tags( (string) $note->content ) );

		if ( ! preg_match( '/(?:debug id|debug_id)[:\s]+([a-z0-9_-]+)/i', $content, $matches ) ) {
			continue;
		}

		return sanitize_text_field( $matches[1] );
	}

	return '';
}

/**
 * @param array<string,string> $event Diagnostic values for the current event.
 */
function simms_paypal_update_order_diagnostics( WC_Order $order, array $event ): void {
	$current = $order->get_meta( '_simms_paypal_diagnostics' );
	$current = is_array( $current ) ? $current : array();
	$current = array_merge( $current, $event );

	$events = $order->get_meta( '_simms_paypal_diagnostic_events' );
	$events = is_array( $events ) ? $events : array();

	$fingerprint = simms_paypal_diagnostic_fingerprint( $event );
	$last_event  = end( $events );
	$last_hash   = is_array( $last_event ) && isset( $last_event['fingerprint'] ) ? (string) $last_event['fingerprint'] : '';

	if ( $fingerprint !== $last_hash ) {
		$event['fingerprint'] = $fingerprint;
		$events[]             = $event;
		$events               = array_slice( $events, -10 );
	}

	$order->update_meta_data( '_simms_paypal_diagnostics', $current );
	$order->update_meta_data( '_simms_paypal_diagnostic_events', $events );
	$order->save_meta_data();
}

/**
 * @param array<string,string> $event Diagnostic values for the current event.
 */
function simms_paypal_add_diagnostics_note( WC_Order $order, array $event ): void {
	$fingerprint = simms_paypal_diagnostic_fingerprint( $event );
	$note_key    = '_simms_paypal_diagnostics_note_' . sanitize_key( $event['new_status'] ?? $event['event_type'] ?? 'event' );

	if ( $fingerprint === (string) $order->get_meta( $note_key ) ) {
		return;
	}

	$parts = array(
		'status'       => $event['new_status'] ?? $event['woo_status'] ?? '',
		'paypal_order' => $event['paypal_order_id'] ?? '',
		'transaction'  => $event['paypal_transaction_id'] ?? $event['paypal_capture_id'] ?? '',
		'processor'    => $event['response_code'] ?? '',
		'avs'          => $event['avs_code'] ?? '',
		'cvv'          => $event['cvv_code'] ?? '',
		'card'         => trim( ( $event['card_brand'] ?? '' ) . ' ' . ( $event['card_last4'] ?? '' ) ),
		'retry'        => $event['retry_outcome'] ?? '',
	);

	if ( ! empty( $event['latest_decline'] ) ) {
		$parts['latest_decline'] = $event['latest_decline'];
	}

	$note_parts = array();
	foreach ( $parts as $label => $value ) {
		if ( '' !== $value ) {
			$note_parts[] = $label . '=' . $value;
		}
	}

	if ( ! $note_parts ) {
		return;
	}

	$order->add_order_note( 'PayPal diagnostics: ' . implode( '; ', $note_parts ), false, false );
	$order->update_meta_data( $note_key, $fingerprint );
	$order->save_meta_data();
}

/**
 * @param array<string,string> $event Diagnostic values for the current event.
 */
function simms_paypal_diagnostic_fingerprint( array $event ): string {
	$keys = array(
		'source',
		'event_type',
		'webhook_event_id',
		'old_status',
		'new_status',
		'paypal_order_id',
		'paypal_capture_id',
		'paypal_transaction_id',
		'response_code',
		'avs_code',
		'cvv_code',
		'latest_decline',
	);

	$values = array();
	foreach ( $keys as $key ) {
		$values[ $key ] = isset( $event[ $key ] ) ? (string) $event[ $key ] : '';
	}

	return md5( wp_json_encode( $values ) ?: serialize( $values ) );
}

function simms_paypal_add_classic_diagnostics_meta_box( string $post_type ): void {
	if ( 'shop_order' !== $post_type ) {
		return;
	}

	add_meta_box(
		'simms-paypal-diagnostics',
		__( 'PayPal diagnostics', 'simms-research' ),
		'simms_paypal_render_diagnostics_meta_box',
		'shop_order',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'simms_paypal_add_classic_diagnostics_meta_box', 20 );

function simms_paypal_add_hpos_diagnostics_meta_box(): void {
	add_meta_box(
		'simms-paypal-diagnostics',
		__( 'PayPal diagnostics', 'simms-research' ),
		'simms_paypal_render_diagnostics_meta_box',
		'woocommerce_page_wc-orders',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_woocommerce_page_wc-orders', 'simms_paypal_add_hpos_diagnostics_meta_box', 20 );

/**
 * @param WP_Post|WC_Order $post_or_order_object Admin screen object.
 */
function simms_paypal_render_diagnostics_meta_box( $post_or_order_object ): void {
	$order = $post_or_order_object instanceof WC_Order
		? $post_or_order_object
		: wc_get_order( $post_or_order_object->ID ?? 0 );

	if ( ! $order instanceof WC_Order ) {
		echo '<p>' . esc_html__( 'No order diagnostics available.', 'simms-research' ) . '</p>';
		return;
	}

	if ( ! simms_paypal_is_diagnostic_order( $order ) ) {
		echo '<p>' . esc_html__( 'This order was not paid through PayPal Payments.', 'simms-research' ) . '</p>';
		return;
	}

	$diagnostics = $order->get_meta( '_simms_paypal_diagnostics' );
	$diagnostics = is_array( $diagnostics ) ? $diagnostics : simms_paypal_build_order_diagnostics( $order, 'admin_view' );

	if ( ! $diagnostics ) {
		echo '<p>' . esc_html__( 'No PayPal diagnostics have been recorded yet.', 'simms-research' ) . '</p>';
		return;
	}

	$rows = array(
		__( 'Status', 'simms-research' )           => $diagnostics['woo_status'] ?? '',
		__( 'PayPal order', 'simms-research' )     => $diagnostics['paypal_order_id'] ?? '',
		__( 'Transaction', 'simms-research' )      => $diagnostics['paypal_transaction_id'] ?? $diagnostics['paypal_capture_id'] ?? '',
		__( 'Event', 'simms-research' )            => $diagnostics['event_type'] ?? '',
		__( 'Processor', 'simms-research' )        => $diagnostics['response_code'] ?? '',
		__( 'AVS', 'simms-research' )              => $diagnostics['avs_code'] ?? '',
		__( 'CVV', 'simms-research' )              => $diagnostics['cvv_code'] ?? '',
		__( 'Card', 'simms-research' )             => trim( ( $diagnostics['card_brand'] ?? '' ) . ' ' . ( $diagnostics['card_last4'] ?? '' ) ),
		__( 'Retry', 'simms-research' )            => $diagnostics['retry_outcome'] ?? '',
		__( 'Latest decline', 'simms-research' )   => $diagnostics['latest_decline'] ?? '',
		__( 'PayPal debug ID', 'simms-research' )  => $diagnostics['debug_id'] ?? '',
		__( 'Recorded', 'simms-research' )         => $diagnostics['recorded_at'] ?? '',
	);

	echo '<table class="widefat striped" style="border:0;">';
	echo '<tbody>';

	foreach ( $rows as $label => $value ) {
		if ( '' === $value ) {
			continue;
		}

		echo '<tr>';
		echo '<th style="width:42%;padding-left:0;">' . esc_html( $label ) . '</th>';
		echo '<td style="word-break:break-word;">' . esc_html( (string) $value ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';
}

/**
 * @return array<string,string>
 */
function simms_paypal_customer_billing_address( WC_Customer $customer ): array {
	$address = array_filter(
		array(
			'country_code'   => sanitize_text_field( (string) $customer->get_billing_country() ),
			'address_line_1' => sanitize_text_field( (string) $customer->get_billing_address_1() ),
			'address_line_2' => sanitize_text_field( (string) $customer->get_billing_address_2() ),
			'admin_area_1'   => sanitize_text_field( (string) $customer->get_billing_state() ),
			'admin_area_2'   => sanitize_text_field( (string) $customer->get_billing_city() ),
			'postal_code'    => sanitize_text_field( (string) $customer->get_billing_postcode() ),
		)
	);

	if ( empty( $address['country_code'] ) || empty( $address['address_line_1'] ) ) {
		return array();
	}

	return $address;
}

function simms_paypal_normalized_customer_phone( WC_Customer $customer ): string {
	$phone = (string) ( $customer->get_billing_phone() ?: $customer->get_shipping_phone() );
	$phone = preg_replace( '/[^0-9]/', '', $phone );

	if ( ! is_string( $phone ) || '' === $phone ) {
		return '';
	}

	return substr( $phone, 0, 14 );
}

/**
 * Recover the PayPal order link from a verified CHECKOUT.ORDER.APPROVED webhook
 * before PayPal Payments processes the Woo order.
 *
 * The plugin's webhook handler resolves the Woo order from PayPal `custom_id`,
 * but its OrderProcessor then expects `_ppcp_paypal_order_id` to already exist.
 * For the block redirect flow, the webhook itself is the first reliable place we
 * can bridge that missing metadata.
 *
 * @param bool     $process  Whether PayPal Payments should process the order.
 * @param mixed    $gateway  Gateway instance, or null from the webhook handler.
 * @param WC_Order $wc_order WooCommerce order.
 * @return bool
 */
function simms_paypal_link_approved_webhook_order( bool $process, $gateway, WC_Order $wc_order ): bool {
	if ( ! $process || null !== $gateway ) {
		return $process;
	}

	if ( 'ppcp-gateway' !== $wc_order->get_payment_method() ) {
		return $process;
	}

	if ( $wc_order->has_status( array( 'cancelled', 'refunded' ) ) ) {
		return false;
	}

	if ( $wc_order->get_meta( '_ppcp_paypal_order_id' ) ) {
		return $process;
	}

	$payload = simms_paypal_current_webhook_payload();
	if ( 'CHECKOUT.ORDER.APPROVED' !== ( $payload['event_type'] ?? '' ) ) {
		return $process;
	}

	$resource = isset( $payload['resource'] ) && is_array( $payload['resource'] ) ? $payload['resource'] : array();
	$order_id = isset( $resource['id'] ) ? sanitize_text_field( (string) $resource['id'] ) : '';

	if ( '' === $order_id || ! simms_paypal_webhook_matches_order( $resource, $wc_order ) ) {
		return $process;
	}

	$wc_order->update_meta_data( '_ppcp_paypal_order_id', $order_id );
	$wc_order->save_meta_data();

	return $process;
}
add_filter( 'woocommerce_paypal_payments_before_order_process', 'simms_paypal_link_approved_webhook_order', 5, 3 );

/**
 * Decode the current JSON webhook payload once per request.
 *
 * @return array<string,mixed>
 */
function simms_paypal_current_webhook_payload(): array {
	static $payload = null;

	if ( null !== $payload ) {
		return $payload;
	}

	$payload = array();
	$raw     = file_get_contents( 'php://input' );

	if ( ! is_string( $raw ) || '' === $raw ) {
		return $payload;
	}

	$decoded = json_decode( $raw, true );
	if ( is_array( $decoded ) ) {
		$payload = $decoded;
	}

	return $payload;
}

/**
 * Confirm the approved PayPal order belongs to this Woo order.
 *
 * @param array<string,mixed> $resource PayPal webhook resource.
 * @param WC_Order            $wc_order WooCommerce order.
 * @return bool
 */
function simms_paypal_webhook_matches_order( array $resource, WC_Order $wc_order ): bool {
	$expected = (string) $wc_order->get_id();
	$ids      = array();

	if ( ! empty( $resource['custom_id'] ) ) {
		$ids[] = (string) $resource['custom_id'];
	}

	if ( ! empty( $resource['purchase_units'] ) && is_array( $resource['purchase_units'] ) ) {
		foreach ( $resource['purchase_units'] as $purchase_unit ) {
			if ( is_array( $purchase_unit ) && ! empty( $purchase_unit['custom_id'] ) ) {
				$ids[] = (string) $purchase_unit['custom_id'];
			}
		}
	}

	return in_array( $expected, $ids, true );
}
