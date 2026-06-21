/**
 * "Total savings" summary line below the Cart / Checkout block Total — the
 * combined amount the customer saved, the way Shopify shows it.
 *
 * It sums BOTH discount types this store uses:
 *   - product/volume discount (Flycart product-adjustment mode lowers the unit
 *     price, so it never reaches the totals — recovered from regular vs. current
 *     price per line, the same plugin-agnostic basis the drawer uses), and
 *   - cart-level discounts / coupons (cart.totals.total_discount, e.g. an
 *     affiliate code).
 *
 * Rendered via ExperimentalOrderMeta, so it sits BELOW the Total — it is an
 * informational "you saved $X" figure, not a line in the subtotal->total math,
 * so it never double-counts the already-net subtotal.
 */
( function ( wp, wc ) {
	if ( ! wp || ! wc || ! wc.blocksCheckout || ! wp.plugins || ! wp.element || ! wp.data ) {
		return;
	}

	var el             = wp.element.createElement;
	var registerPlugin = wp.plugins.registerPlugin;
	var useSelect      = wp.data.useSelect;
	var OrderMeta      = wc.blocksCheckout.ExperimentalOrderMeta;

	if ( ! OrderMeta || ! registerPlugin || ! useSelect ) {
		return;
	}

	function formatMinor( minorTotal, c ) {
		var unit   = c.currency_minor_unit;
		var amount = ( minorTotal / Math.pow( 10, unit ) ).toFixed( unit );
		var parts  = amount.split( '.' );

		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, c.currency_thousand_separator );
		amount     = parts.join( c.currency_decimal_separator );

		return c.currency_prefix + amount + c.currency_suffix;
	}

	function TotalSavings() {
		var cart = useSelect( function ( select ) {
			var store = select( 'wc/store/cart' );
			return store ? store.getCartData() : null;
		}, [] );

		if ( ! cart || ! cart.totals ) {
			return null;
		}

		var productSavings = 0;

		( cart.items || [] ).forEach( function ( item ) {
			var prices = item.prices;
			if ( ! prices ) {
				return;
			}

			var regular = parseInt( prices.regular_price, 10 );
			var current = parseInt( prices.price, 10 );
			var qty     = item.quantity || 0;

			if ( regular > current ) {
				productSavings += ( regular - current ) * qty;
			}
		} );

		var couponSavings = parseInt( cart.totals.total_discount || '0', 10 );
		var combined      = productSavings + couponSavings;

		if ( combined <= 0 ) {
			return null;
		}

		return el(
			'div',
			{ className: 'wc-block-components-totals-item simms-total-savings' },
			el(
				'span',
				{ className: 'wc-block-components-totals-item__label' },
				'Total savings'
			),
			el(
				'span',
				{ className: 'wc-block-components-totals-item__value simms-total-savings__value' },
				formatMinor( combined, cart.totals )
			)
		);
	}

	registerPlugin( 'simms-total-savings', {
		render: function () {
			return el( OrderMeta, null, el( TotalSavings, null ) );
		},
		scope: 'woocommerce-checkout',
	} );
} )( window.wp, window.wc );
