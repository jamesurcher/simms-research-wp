# PayPal Checkout Webhook Order ID Incident

Date investigated: 2026-06-28

## Summary

A customer approved a PayPal checkout payment, but WooCommerce did not complete the order. PayPal created and approved checkout order `1LE89515G6148725W`, and its webhook identified the matching WooCommerce order with `custom_id: "459"`. When the webhook reached WordPress, the WooCommerce PayPal Payments plugin found Woo order `#459` but the order was missing the plugin's required `_ppcp_paypal_order_id` metadata. The plugin then threw a fatal `PayPalOrderMissingException`, returned HTTP 500 to PayPal, and the payment was never captured.

Woo order `#459` remained unpaid and was later cancelled by the store's 60-minute stock-hold timeout.

## Customer-Facing Impact

- The buyer completed PayPal approval but the store did not finalize the order.
- WooCommerce created order `#459` with payment method `ppcp-gateway` and status later moved to `cancelled`.
- No PayPal transaction ID or paid date was saved to the order.
- Stock was held temporarily and released when the unpaid order was cancelled.

## Evidence

- Kinsta PHP fatal log showed:
  - Exception: `WooCommerce\PayPalCommerce\WcGateway\Exception\PayPalOrderMissingException`
  - File: `woocommerce-paypal-payments/modules/ppcp-wc-gateway/src/Processor/OrderProcessor.php:109`
  - Request: `POST /wp-json/paypal/v1/incoming`
  - Stack path: `CheckoutOrderApproved->handle_request()` -> `OrderProcessor->process()`
- PayPal webhook details showed:
  - Webhook ID: `WH-9P938324FL826883K-93P30653D9818244S`
  - Event type: `CHECKOUT.ORDER.APPROVED`
  - Resource ID: `1LE89515G6148725W`
  - Resource status: `APPROVED`
  - `custom_id: "459"`
  - `invoice_id: "cadedf-459"`
  - Webhook URL: `https://simmsresearch.kinsta.cloud/wp-json/paypal/v1/incoming`
  - Response: HTTP `500 Internal Server Error`
- WooCommerce order `#459` showed:
  - `created_via: store-api`
  - `payment_method: ppcp-gateway`
  - total `$328.50`
  - no transaction ID
  - no `_ppcp_paypal_order_id`
  - status `cancelled`

The `kinsta.cloud` webhook URL was not the root issue. It points to the same production WordPress install and the webhook did reach WordPress. The failure happened inside the PayPal plugin after WordPress received the webhook.

## Investigation Process

1. Confirmed the Kinsta fatal was from the PayPal Payments plugin, not custom theme checkout code.
2. Verified production plugin versions:
   - WooCommerce `10.8.1`
   - WooCommerce PayPal Payments `4.0.4`
3. Inspected the production checkout page content and confirmed it uses WooCommerce Blocks checkout.
4. Checked Woo order `#459` via WP-CLI and confirmed:
   - PayPal was selected.
   - The order existed.
   - The order had no PayPal order metadata or transaction ID.
5. Pulled the PayPal plugin source to inspect the failing path.
6. Found that `OrderProcessor->process()` expects either:
   - an existing `_ppcp_paypal_order_id` on the Woo order, or
   - request/session PayPal order data.
7. Inspected WooCommerce fatal logs and found the processor was being called from the PayPal `CHECKOUT.ORDER.APPROVED` webhook handler, where there is no shopper checkout session.
8. Compared with PayPal's webhook details and confirmed the webhook itself contained the missing PayPal order ID and the Woo order ID via `resource.id` and `custom_id`.

## Inventory Configuration Hypothesis

After the initial fix, the GLP-3 product inventory configuration was reviewed as a possible contributing factor. The suspected historical configuration was:

- Parent variable product `GLP-3 (RT)` / product `#48`:
  - parent stock management enabled
  - parent stock quantity `0`
  - parent stock status `outofstock`
- 20mg variation / variation `#350`:
  - variation stock management enabled
  - variation stock quantity available
  - variation stock status `instock`
- 10mg and 30mg variations:
  - stock quantity `0`
  - stock status `outofstock`

This configuration is not ideal. Parent-level stock management on a variable product can create storefront/catalog availability confusion, especially when the parent is out of stock while a child variation is still available. It should be corrected so stock is managed at the variation level for this product.

However, this does not explain the specific PayPal/WooCommerce order ID disconnect for order `#459`.

Diagnosis:

- Order `#459` contained the 20mg variation `#350`, quantity `3`.
- WooCommerce considered variation `#350` in stock and purchasable.
- WooCommerce accepted the line item into the order.
- WooCommerce applied the stock hold successfully.
- PayPal created and approved a PayPal order.
- PayPal's webhook included `custom_id: "459"`, proving PayPal had the Woo order reference.
- The failure was still that Woo order `#459` lacked `_ppcp_paypal_order_id` when the PayPal approval webhook reached the WooCommerce PayPal Payments plugin.

Conclusion: the GLP-3 parent/variation stock setup was a real product configuration issue and worth fixing, but it was not the root cause of this incident. If it had been the direct cause, the expected failure mode would have been a product availability, add-to-cart, or checkout stock validation error before PayPal approval. Instead, the order was created, stock was held, and PayPal approval happened.

## Root Cause

The PayPal approval webhook had enough information to connect PayPal order `1LE89515G6148725W` to Woo order `#459`, but Woo order `#459` did not already have `_ppcp_paypal_order_id` saved.

The PayPal plugin's webhook handler resolved the Woo order from `custom_id`, then called the order processor. The processor did not use the webhook resource ID as a fallback, so it treated the PayPal order ID as missing and threw a fatal error. Because the webhook returned HTTP 500, PayPal kept the event in `PENDING` retry state.

## Fault Attribution

The fault is attributable to the WooCommerce PayPal Payments integration plugin, not PayPal checkout itself and not WooCommerce core.

- PayPal did its part: it created PayPal order `1LE89515G6148725W`, the buyer approved it, and PayPal sent a webhook containing the correct Woo order reference, `custom_id: "459"`.
- WooCommerce core did its part: it created WooCommerce order `#459`.
- The integration bridge failed: the WooCommerce PayPal Payments plugin did not have/save the PayPal order ID on Woo order `#459`, then crashed when the approval webhook arrived.

Primary fault: WooCommerce PayPal Payments plugin / PayPal-WooCommerce integration logic.

Not attributable to: the customer, Kinsta, the `kinsta.cloud` webhook URL, PayPal's approval flow, or WooCommerce core checkout.

## Fix Implemented

Added `wp-content/themes/simms-research-wp/inc/paypal-payments.php` and loaded it from `functions.php`.

The compatibility module does two things:

1. Disables the PayPal Blocks non-express "Proceed to PayPal" fallback method:
   - Hook: `woocommerce_paypal_payments_blocks_add_place_order_method`
   - Result: shoppers use the PayPal express button flow instead of the redirect fallback path that can create this missing metadata state.

2. Repairs approved PayPal webhooks before PayPal's order processor runs:
   - Hook: `woocommerce_paypal_payments_before_order_process`
   - Only runs for webhook processing, where the gateway argument is `null`.
   - Confirms the payment method is `ppcp-gateway`.
   - Confirms the webhook event is `CHECKOUT.ORDER.APPROVED`.
   - Confirms the webhook `custom_id` matches the Woo order ID.
   - Saves `resource.id` into `_ppcp_paypal_order_id` before the plugin processor runs.
   - Skips cancelled or refunded orders so old webhook retries do not revive/capture cancelled orders.

Also updated `inc/tracking.php` so PostHog `order_completed` only fires after `woocommerce_payment_complete`. Previously it ran on Store API order creation, which meant a failed PayPal attempt could be counted as a purchase.

## Production Verification

After deploying only the targeted files:

- PHP lint passed for:
  - `functions.php`
  - `inc/tracking.php`
  - `inc/paypal-payments.php`
- WP-CLI confirmed:
  - PayPal compatibility file loaded.
  - PayPal Blocks fallback method disabled.
  - PostHog purchase tracking attached to `woocommerce_payment_complete`.
  - Old Store API purchase tracking hook removed.
- `/checkout/` returned normally for an empty session with a WooCommerce redirect to `/cart/`.
- Production error log showed no new PHP fatals after deployment.
