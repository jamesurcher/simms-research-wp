<?php
/**
 * Marketing / analytics pixels.
 *
 * Re-implements the original Shopify Customer Events pixel stack on WooCommerce:
 *   - Meta Pixel x2 (redundant): 27069095412778622 + 1522907926130322
 *   - TikTok Pixel: D87RSK3C77U8UFHBAMQ0
 *   - Microsoft Clarity: wxpzcqr3ui
 *
 * Shopify's analytics.subscribe(...) Customer Events API has no WooCommerce
 * equivalent, so each commerce event is re-wired to its WooCommerce trigger here
 * (server-side payloads) and fired in assets/js/simms-tracking.js.
 *
 *   PageView         -> every page (head snippet)
 *   ViewContent      -> single product page
 *   Search           -> search results page
 *   AddToCart        -> custom cart-drawer AJAX success (see inc/woocommerce.php)
 *   InitiateCheckout -> checkout page load
 *   AddPaymentInfo   -> block-checkout payment step (JS, wc/store/checkout)
 *   Purchase         -> order-received page (eventID = order id, for CAPI dedup later)
 *
 * content_id values are WooCommerce product/variation IDs (never SKU). The Meta
 * and TikTok product catalogs must key on the same WC IDs for catalog/DPA
 * retargeting to match.
 *
 * Client-side only for now. Every fire carries an eventID so a server-side Meta
 * Conversions API layer can be added later with clean deduplication.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SIMMS_META_PIXEL_IDS  = array( '27069095412778622', '1522907926130322' );
const SIMMS_TIKTOK_PIXEL_ID = 'D87RSK3C77U8UFHBAMQ0';
const SIMMS_CLARITY_ID      = 'wxpzcqr3ui';
const SIMMS_POSTHOG_TOKEN   = 'phc_mL2DBJr4fEL4AU8hwDtLTsfcWBqhnx4tZcwQpb7jFNQ6';
const SIMMS_POSTHOG_HOST    = 'https://us.i.posthog.com';

/**
 * Whether tracking should run for the current request. Front-end visitors only;
 * shop managers/admins are excluded so internal browsing does not pollute the
 * ad-platform data (matches standard practice).
 */
function simms_tracking_active(): bool {
	if ( is_admin() || wp_doing_ajax() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return false;
	}

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return false;
	}

	return true;
}

/* -------------------------------------------------------------------------
 * Base pixel snippets — printed as early as possible in <head>.
 * ---------------------------------------------------------------------- */

add_action( 'wp_head', 'simms_tracking_head', 1 );

function simms_tracking_head(): void {
	if ( ! simms_tracking_active() ) {
		return;
	}

	$meta_inits     = '';
	$meta_pageviews = '';
	foreach ( SIMMS_META_PIXEL_IDS as $pid ) {
		$meta_inits     .= "fbq('init','" . esc_js( $pid ) . "');\n";
		$meta_pageviews .= "fbq('trackSingle','" . esc_js( $pid ) . "','PageView');\n";
	}
	?>
	<!-- Simms tracking: Meta (x2) + TikTok + Microsoft Clarity -->
	<script>
	/* Meta Pixel — both IDs init'd; track() fires to all of them (redundancy). */
	!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
	n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
	n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
	t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
	document,'script','https://connect.facebook.net/en_US/fbevents.js');
	<?php
	echo $meta_inits;     // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — IDs escaped via esc_js above
	echo $meta_pageviews; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — IDs escaped via esc_js above
	?>
	</script>
	<script>
	/* TikTok Pixel */
	!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
	ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"];
	ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
	for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
	ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
	ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=d.createElement("script");o.type="text/javascript",o.async=!0,o.src=r+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
	ttq.load('<?php echo esc_js( SIMMS_TIKTOK_PIXEL_ID ); ?>');
	ttq.page();
	}(window,document,'ttq');
	</script>
	<script>
	/* Microsoft Clarity */
	(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
	t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
	y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
	})(window,document,"clarity","script","<?php echo esc_js( SIMMS_CLARITY_ID ); ?>");
	</script>
	<script>
	/* PostHog — autocapture on, heatmaps + web vitals off (Clarity covers heatmaps/replay) */
	!function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug getPageViewId captureTraceFeedback captureTraceMetric".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
	posthog.init('<?php echo esc_js( SIMMS_POSTHOG_TOKEN ); ?>',{api_host:'<?php echo esc_js( SIMMS_POSTHOG_HOST ); ?>',defaults:'2026-05-30',person_profiles:'identified_only',autocapture:true,capture_heatmaps:false,capture_performance:false});
	</script>
	<?php
}

/* -------------------------------------------------------------------------
 * Per-page event payloads + the firing script.
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'simms_tracking_enqueue', 30 );

function simms_tracking_enqueue(): void {
	if ( ! simms_tracking_active() ) {
		return;
	}

	wp_enqueue_script(
		'simms-tracking',
		SIMMS_THEME_URI . '/assets/js/simms-tracking.js',
		array(),
		SIMMS_THEME_VERSION,
		true
	);

	wp_localize_script(
		'simms-tracking',
		'simmsTracking',
		array(
			'metaPixelIds' => array_values( SIMMS_META_PIXEL_IDS ),
			'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
			'page'         => simms_tracking_page_payload(),
		)
	);
}

/**
 * The single event to fire on page load for the current request (if any).
 *
 * @return array{event?:string,data?:array,eventId?:string}
 */
function simms_tracking_page_payload(): array {
	if ( ! function_exists( 'is_product' ) ) {
		return array();
	}

	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return simms_tracking_purchase_payload();
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return simms_tracking_cart_payload( 'InitiateCheckout' );
	}

	if ( is_product() ) {
		return simms_tracking_view_content_payload();
	}

	$is_search = is_search() || ( function_exists( 'simms_is_search_route' ) && simms_is_search_route() );
	if ( $is_search ) {
		$query = function_exists( 'simms_search_query' ) ? simms_search_query() : get_search_query();

		return array(
			'event' => 'Search',
			'data'  => array( 'search_string' => (string) $query ),
		);
	}

	return array();
}

function simms_tracking_view_content_payload(): array {
	$product = wc_get_product( get_queried_object_id() );

	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$price = (float) $product->get_price();
	if ( $price <= 0 && $product->is_type( 'variable' ) ) {
		$price = (float) $product->get_variation_price( 'min', true );
	}

	$id = (string) $product->get_id();

	return array(
		'event' => 'ViewContent',
		'data'  => array(
			'content_ids'  => array( $id ),
			'content_name' => $product->get_name(),
			'content_type' => 'product',
			'contents'     => array(
				array(
					'id'         => $id,
					'quantity'   => 1,
					'item_price' => $price,
				),
			),
			'value'        => $price,
			'currency'     => get_woocommerce_currency(),
		),
	);
}

/**
 * Build an InitiateCheckout-shaped payload from the live cart.
 */
function simms_tracking_cart_payload( string $event ): array {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}

	$cart     = WC()->cart;
	$ids      = array();
	$contents = array();
	$num      = 0;

	foreach ( $cart->get_cart() as $item ) {
		$product = $item['data'] ?? null;
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$id  = (string) ( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
		$qty = (int) $item['quantity'];

		$ids[]      = $id;
		$contents[] = array(
			'id'         => $id,
			'quantity'   => $qty,
			'item_price' => (float) $product->get_price(),
		);
		$num       += $qty;
	}

	if ( empty( $ids ) ) {
		return array();
	}

	return array(
		'event' => $event,
		'data'  => array(
			'content_ids'  => $ids,
			'content_type' => 'product',
			'contents'     => $contents,
			'num_items'    => $num,
			'value'        => (float) $cart->get_total( 'edit' ),
			'currency'     => get_woocommerce_currency(),
		),
	);
}

/**
 * Shared order -> tracking properties (content_ids are WC product/variation IDs).
 */
function simms_tracking_order_data( WC_Order $order ): array {
	$ids      = array();
	$contents = array();
	$num      = 0;

	foreach ( $order->get_items() as $item ) {
		$id  = (string) ( $item->get_variation_id() ?: $item->get_product_id() );
		$qty = (int) $item->get_quantity();

		$ids[]      = $id;
		$contents[] = array(
			'id'         => $id,
			'quantity'   => $qty,
			'item_price' => $qty ? (float) ( $item->get_total() / $qty ) : 0.0,
		);
		$num       += $qty;
	}

	return array(
		'content_ids'  => $ids,
		'content_type' => 'product',
		'contents'     => $contents,
		'num_items'    => $num,
		'value'        => (float) $order->get_total(),
		'currency'     => $order->get_currency(),
		'order_id'     => (string) $order->get_id(),
	);
}

/**
 * Purchase payload for the client-side Meta/TikTok pixels on the order-received
 * page. (PostHog's purchase is sent server-side instead — see below.) eventID is
 * the stable order id so a future Conversions API call can dedupe against it.
 */
function simms_tracking_purchase_payload(): array {
	$order_id = absint( get_query_var( 'order-received' ) );
	if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return array();
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return array();
	}

	// Only fire when the URL carries the matching order key (don't fire Purchase
	// for an arbitrary order id pasted into the address bar).
	$key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' === $key || ! hash_equals( $order->get_order_key(), $key ) ) {
		return array();
	}

	return array(
		'event'   => 'Purchase',
		'eventId' => 'order_' . $order->get_id(),
		'data'    => simms_tracking_order_data( $order ),
	);
}

/**
 * AddToCart payload for a freshly added line. Called from the cart-drawer AJAX
 * handler (inc/woocommerce.php) and returned to the browser, which fires it.
 */
function simms_tracking_added_to_cart_payload( int $product_id, int $variation_id, int $quantity ): array {
	$id      = $variation_id ?: $product_id;
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;

	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$price    = (float) $product->get_price();
	$quantity = max( 1, $quantity );
	$id       = (string) $id;

	return array(
		'content_ids'  => array( $id ),
		'content_name' => $product->get_name(),
		'content_type' => 'product',
		'contents'     => array(
			array(
				'id'         => $id,
				'quantity'   => $quantity,
				'item_price' => $price,
			),
		),
		'value'        => $price * $quantity,
		'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
	);
}

/* -------------------------------------------------------------------------
 * Phase 3 — server-side Purchase capture for PostHog.
 *
 * Client-side order_completed can be lost to ad-blockers or a closed tab, so
 * PostHog's purchase is fired from the server on every order (authoritative
 * conversion count / CVR / revenue). It is stitched to the shopper's PostHog
 * person via the distinct_id in their first-party PostHog cookie, so the server
 * purchase joins the same funnel journey as their client-side checkout_started.
 *
 * Loss-rate measurement: server order_completed = every order; the client
 * $pageview on the order-received page = orders where the client pixel actually
 * ran. The gap between them is the real client-side loss rate — i.e. the ceiling
 * on what a reverse proxy (Phase 4) could recover.
 * ---------------------------------------------------------------------- */

/**
 * PostHog distinct_id (+ session id) for the current request, read from the
 * first-party PostHog cookie so the server event attaches to the same person.
 *
 * @return array{distinct_id:string,session_id:string}
 */
function simms_posthog_identity(): array {
	$cookie = 'ph_' . SIMMS_POSTHOG_TOKEN . '_posthog';
	$raw    = isset( $_COOKIE[ $cookie ] ) ? wp_unslash( $_COOKIE[ $cookie ] ) : '';

	if ( '' === $raw ) {
		return array(
			'distinct_id' => '',
			'session_id'  => '',
		);
	}

	$data = json_decode( (string) $raw, true );
	if ( ! is_array( $data ) ) {
		$data = json_decode( (string) rawurldecode( (string) $raw ), true );
	}
	if ( ! is_array( $data ) ) {
		return array(
			'distinct_id' => '',
			'session_id'  => '',
		);
	}

	$session_id = '';
	if ( isset( $data['$sessionid'] ) && is_array( $data['$sessionid'] ) && isset( $data['$sessionid'][1] ) ) {
		$session_id = sanitize_text_field( (string) $data['$sessionid'][1] );
	}

	return array(
		'distinct_id' => isset( $data['distinct_id'] ) ? sanitize_text_field( (string) $data['distinct_id'] ) : '',
		'session_id'  => $session_id,
	);
}

/**
 * Send one event to PostHog from the server. Blocking with a short timeout —
 * ingestion is fast and we want the conversion reliably recorded.
 */
function simms_posthog_capture_server( string $event, string $distinct_id, array $properties, string $event_uuid = '' ): void {
	if ( '' === SIMMS_POSTHOG_TOKEN || '' === $distinct_id ) {
		return;
	}

	$body = array(
		'api_key'     => SIMMS_POSTHOG_TOKEN,
		'event'       => $event,
		'distinct_id' => $distinct_id,
		'properties'  => $properties,
		'timestamp'   => gmdate( 'c' ),
	);
	if ( '' !== $event_uuid ) {
		$body['uuid'] = $event_uuid;
	}

	wp_remote_post(
		SIMMS_POSTHOG_HOST . '/capture/',
		array(
			'timeout'  => 3,
			'blocking' => true,
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'body'     => wp_json_encode( $body ),
		)
	);
}

/**
 * Fire order_completed to PostHog server-side, once per order. Hooked to both the
 * block (Store API) and classic checkout order-processed actions; the first arg
 * is a WC_Order (Store API) or an order id (classic).
 *
 * @param int|WC_Order $order_or_id Order or order id.
 */
function simms_tracking_send_purchase_server( $order_or_id ): void {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( $order_or_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	// Once per order (uuid also dedupes at PostHog as a backstop).
	if ( 'yes' === $order->get_meta( '_simms_ph_purchase_sent' ) ) {
		return;
	}

	$identity    = simms_posthog_identity();
	$distinct_id = '' !== $identity['distinct_id'] ? $identity['distinct_id'] : ( 'wc_order_' . $order->get_id() );

	$properties                   = simms_tracking_order_data( $order );
	$properties['capture_method'] = 'server';
	if ( '' !== $identity['session_id'] ) {
		$properties['$session_id'] = $identity['session_id'];
	}

	simms_posthog_capture_server( 'order_completed', $distinct_id, $properties, 'order_' . $order->get_id() );

	$order->update_meta_data( '_simms_ph_purchase_sent', 'yes' );
	$order->save();
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'simms_tracking_send_purchase_server', 20 );
add_action( 'woocommerce_checkout_order_processed', 'simms_tracking_send_purchase_server', 20 );
