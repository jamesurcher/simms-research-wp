/**
 * Fires Meta (x2 pixels) + TikTok commerce events.
 *
 * Base pixels (fbq init x2, ttq.load, clarity) are printed in <head> by
 * inc/tracking.php, which also injects the per-page payload (window.simmsTracking).
 * fbq('track', ...) fires to BOTH initialised Meta pixels, giving the redundancy
 * the store ran on Shopify. content_id values are WooCommerce product/variation IDs.
 */
( function () {
	var cfg = window.simmsTracking;
	if ( ! cfg ) {
		return;
	}

	var page = cfg.page || {};

	// Meta standard event -> TikTok standard event.
	var TIKTOK_EVENT = {
		ViewContent: 'ViewContent',
		Search: 'Search',
		AddToCart: 'AddToCart',
		InitiateCheckout: 'InitiateCheckout',
		AddPaymentInfo: 'AddPaymentInfo',
		Purchase: 'CompletePayment'
	};

	// Fire each pixel explicitly with trackSingle so both redundant pixels
	// provably receive every event (mirrors the original Shopify custom pixel).
	function metaTrack( event, data, eventId ) {
		if ( typeof window.fbq !== 'function' ) {
			return;
		}
		var ids = cfg.metaPixelIds || [];
		ids.forEach( function ( id ) {
			if ( eventId ) {
				window.fbq( 'trackSingle', id, event, data || {}, { eventID: eventId } );
			} else {
				window.fbq( 'trackSingle', id, event, data || {} );
			}
		} );
	}

	function tiktokTrack( event, data, eventId ) {
		if ( ! window.ttq || typeof window.ttq.track !== 'function' ) {
			return;
		}
		var ttEvent = TIKTOK_EVENT[ event ];
		if ( ! ttEvent ) {
			return;
		}

		data = data || {};
		var contents = ( data.contents || [] ).map( function ( c ) {
			return {
				content_id: String( c.id ),
				content_type: 'product',
				content_name: data.content_name,
				quantity: c.quantity,
				price: c.item_price
			};
		} );

		var payload = {
			content_type: 'product',
			currency: data.currency || cfg.currency,
			value: data.value
		};
		if ( contents.length ) {
			payload.contents = contents;
		}
		if ( data.content_ids && data.content_ids.length ) {
			payload.content_id = String( data.content_ids[ 0 ] );
		}
		if ( data.search_string ) {
			payload.query = data.search_string;
		}

		window.ttq.track( ttEvent, payload, eventId ? { event_id: eventId } : undefined );
	}

	function track( event, data, eventId ) {
		metaTrack( event, data, eventId );
		tiktokTrack( event, data, eventId );
	}

	// Page-load event: ViewContent / Search / InitiateCheckout / Purchase.
	if ( page.event ) {
		track( page.event, page.data, page.eventId );
	}

	// AddToCart — dispatched by cart-drawer.js after a successful add.
	document.addEventListener( 'simms:added-to-cart', function ( e ) {
		var detail = e.detail || {};
		track( 'AddToCart', detail, detail.eventId );
	} );

	// AddPaymentInfo — block checkout. Fire once when the shopper submits the
	// order (the payment-info-submitted moment). Uses the WooCommerce Blocks
	// checkout data store; polls briefly since wp.data may load after this script.
	if ( 'InitiateCheckout' === page.event ) {
		var fired = false;

		var setup = function () {
			if ( ! ( window.wp && wp.data && typeof wp.data.subscribe === 'function' ) ) {
				return false;
			}
			if ( ! wp.data.select( 'wc/store/checkout' ) ) {
				return false;
			}

			var unsubscribe = wp.data.subscribe( function () {
				try {
					var store = wp.data.select( 'wc/store/checkout' );
					if ( ! store ) {
						return;
					}
					var inPayment =
						( typeof store.isBeforeProcessing === 'function' && store.isBeforeProcessing() ) ||
						( typeof store.isProcessing === 'function' && store.isProcessing() );

					if ( ! fired && inPayment ) {
						fired = true;
						track( 'AddPaymentInfo', page.data );
						if ( typeof unsubscribe === 'function' ) {
							unsubscribe();
						}
					}
				} catch ( err ) {}
			} );

			return true;
		};

		if ( ! setup() ) {
			var tries = 0;
			var iv = window.setInterval( function () {
				tries += 1;
				if ( setup() || tries > 50 ) {
					window.clearInterval( iv );
				}
			}, 200 );
		}
	}
}() );
