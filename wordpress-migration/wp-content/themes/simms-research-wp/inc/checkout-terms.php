<?php
/**
 * Explicit Terms / age (21+) / research-use acceptance at checkout.
 *
 * WooCommerce ships an implicit "By proceeding with your purchase you agree…"
 * terms block. We turn it into an explicit, required consent checkbox that links
 * to the Terms and Privacy pages and adds the 21+/research-use acknowledgement,
 * and we record the acceptance on each order (meta + admin display) — mirroring a
 * standard Shopify terms-of-service checkbox.
 *
 * Why the native terms block (and not an "additional checkout field"): block-checkout
 * additional fields render their checkbox label as plain text (no HTML), so policy
 * links cannot be embedded in them. The native terms block's `text` is sanitized
 * RichText that supports links and wraps naturally on mobile, so we transform it in
 * code via `render_block_data`. This keeps the change in deployable theme code and
 * touches no database or order data (the live checkout page content is untouched;
 * remove these filters to revert).
 *
 * The native terms checkbox gates order placement client-side; we additionally stamp
 * the acceptance onto every placed order so there is a durable per-order record.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consent text (HTML). The "Terms and Conditions" / "Privacy Policy" phrases link to
 * the policy pages; the rest is the 21+/research-use acknowledgement.
 *
 * @return string
 */
function simms_checkout_terms_text(): string {
	$terms_link = sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
		esc_url( home_url( '/terms-conditions/' ) ),
		esc_html__( 'Terms and Conditions', 'simms-research' )
	);

	$privacy_link = sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
		esc_url( home_url( '/privacy-policy/' ) ),
		esc_html__( 'Privacy Policy', 'simms-research' )
	);

	return sprintf(
		/* translators: 1: Terms and Conditions link, 2: Privacy Policy link. */
		__( 'I confirm I am at least 21 years of age and acknowledge all products purchased are intended for laboratory and research use, not for human or animal consumption. I have read and agree to the %1$s and %2$s.', 'simms-research' ),
		$terms_link,
		$privacy_link
	);
}

/**
 * Turn WooCommerce's implicit terms block into a required, explicit checkbox with our
 * custom consent text.
 */
add_filter(
	'render_block_data',
	function ( array $block ): array {
		if ( isset( $block['blockName'] ) && 'woocommerce/checkout-terms-block' === $block['blockName'] ) {
			$block['attrs']['checkbox'] = true;
			$block['attrs']['text']     = simms_checkout_terms_text();
		}

		return $block;
	}
);

/**
 * Mirror the consent on the classic "Pay for order" page (order-pay endpoint).
 *
 * That page renders WooCommerce's classic checkout/terms.php — not the block
 * terms block — so it otherwise shows the default "I have read and agree to the
 * website terms" label. Swap in the same explicit 21+/research-use consent text.
 * The label is run through wp_kses_post() in the template, so the policy links in
 * the returned HTML are preserved. Scoped to the pay page; the block /checkout is
 * handled by the render_block_data filter above.
 */
add_filter(
	'woocommerce_get_terms_and_conditions_checkbox_text',
	function ( $text ) {
		if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
			return simms_checkout_terms_text();
		}

		return $text;
	}
);

/**
 * Hide the "Add a note to your order" block at render time (code-only, reversible).
 */
add_filter(
	'render_block',
	function ( string $block_content, array $block ): string {
		if ( isset( $block['blockName'] ) && 'woocommerce/checkout-order-note-block' === $block['blockName'] ) {
			return '';
		}

		return $block_content;
	},
	10,
	2
);

/**
 * Record the acceptance on the order. The checkbox is required at checkout, so a
 * successfully placed (Store API) order means the shopper ticked it; we stamp a
 * durable per-order consent record that surfaces as order meta and in wp-admin
 * (below).
 *
 * The record is deliberately self-contained for audit/defensibility: alongside the
 * flag and timestamp we snapshot the exact consent text shown (so the wording can
 * change later without rewriting history), a short version tag of that text, and the
 * shopper's IP and user agent at the moment of consent.
 */
add_action(
	'woocommerce_store_api_checkout_order_processed',
	function ( WC_Order $order ): void {
		$consent_html = simms_checkout_terms_text();

		$ip = $order->get_customer_ip_address();
		if ( '' === $ip && class_exists( 'WC_Geolocation' ) ) {
			$ip = WC_Geolocation::get_ip_address();
		}

		$order->update_meta_data( '_simms_terms_accepted', 'yes' );
		$order->update_meta_data( '_simms_terms_accepted_at', current_time( 'mysql', true ) );
		$order->update_meta_data( '_simms_terms_text', $consent_html );
		$order->update_meta_data( '_simms_terms_version', substr( md5( $consent_html ), 0, 8 ) );
		$order->update_meta_data( '_simms_terms_ip', $ip );
		$order->update_meta_data( '_simms_terms_user_agent', $order->get_customer_user_agent() );
		$order->save_meta_data();
	}
);

/**
 * Surface the full consent record on the admin order edit screen.
 */
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	function ( WC_Order $order ): void {
		if ( 'yes' !== $order->get_meta( '_simms_terms_accepted' ) ) {
			return;
		}

		$accepted_at = $order->get_meta( '_simms_terms_accepted_at' );
		$ip          = $order->get_meta( '_simms_terms_ip' );
		$user_agent  = $order->get_meta( '_simms_terms_user_agent' );
		$version     = $order->get_meta( '_simms_terms_version' );
		$text        = $order->get_meta( '_simms_terms_text' );

		echo '<div class="simms-terms-consent" style="margin-top:10px">';

		echo '<p style="margin:0 0 4px"><strong>' . esc_html__( 'Terms, age (21+) &amp; research-use', 'simms-research' ) . ':</strong> ' . esc_html__( 'Accepted at checkout', 'simms-research' );
		if ( $accepted_at ) {
			echo ' &mdash; ' . esc_html( get_date_from_gmt( $accepted_at, 'M j, Y g:i a' ) );
		}
		echo '</p>';

		if ( $ip || $user_agent ) {
			echo '<p style="margin:0 0 4px;color:#666;font-size:12px">';
			if ( $ip ) {
				echo esc_html__( 'IP', 'simms-research' ) . ': ' . esc_html( $ip );
			}
			if ( $user_agent ) {
				echo $ip ? ' &middot; ' : '';
				echo esc_html( $user_agent );
			}
			echo '</p>';
		}

		if ( $text ) {
			$heading = $version
				/* translators: %s: short version tag of the consent text. */
				? sprintf( esc_html__( 'Agreed to (v%s)', 'simms-research' ), esc_html( $version ) )
				: esc_html__( 'Agreed to', 'simms-research' );

			echo '<p style="margin:0;color:#666;font-size:12px"><em>' . $heading . ':</em> ' . wp_kses(
				$text,
				array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
			) . '</p>';
		}

		echo '</div>';
	}
);
