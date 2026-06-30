# PayPal Advanced Card Checkout Context Incident

Date investigated: 2026-06-28

## Summary

PayPal Developer logs showed repeated `422` failures on:

`POST /v2/checkout/orders/{paypal_order_id}/confirm-payment-source`

The visible PayPal issue was `PAYER_CANNOT_PAY`, and WooCommerce order `#451` showed:

`Payment declined by card processor: 9510: Security Violation. Please use a different payment method or contact your bank. OrderEndpoint.php:245`

At first glance this looked like a normal card authorization or risk decline. The deeper checkout investigation showed a front-facing integration issue in the PayPal Advanced Card block flow: PayPal's card order was being created before the final WooCommerce order existed, and the PayPal block create-order request was not sending the shopper's checkout payer context, especially phone, billing name, billing address, and email, before PayPal ran card confirmation.

## Customer-Facing Impact

- A shopper could enter valid checkout data in WooCommerce, including phone number, but PayPal's card confirmation could still run with less customer context than the shopper entered on the page.
- This could increase false declines from PayPal's automated card/risk layer.
- Affected checkouts failed as WooCommerce `Failed` orders, not `Cancelled` timeout orders.
- Example: Woo order `#451` failed via `Debit & Credit Cards` with processor code `9510: Security Violation`.

This is separate from the PayPal wallet webhook/order-id incident affecting order `#459`.

## Evidence

- PayPal API error example:
  - Date/time: 2026-06-26 2:54 PM
  - Request path: `/v2/checkout/orders/93563625FN791370Y/confirm-payment-source`
  - Status: `422`
  - Debug ID: `ca11e6b530906`
  - Issue: `PAYER_CANNOT_PAY`
  - Description: `Payer cannot pay for this transaction. Please contact the payer to find other ways to pay for this transaction.`
- WooCommerce order `#451`:
  - Status: `failed`
  - Payment method: `ppcp-credit-card-gateway`
  - Payment title: `Debit & Credit Cards`
  - Billing phone present: yes
  - Shipping phone present: yes
  - Order note: `Payment declined by card processor: 9510: Security Violation`
- Nearby successful card orders also had phone numbers, so the issue was not simply "Woo order has no phone."
- The failed PayPal debug ID referenced PayPal order `93563625FN791370Y`, while Woo order `#451` later stored `_ppcp_paypal_order_id = 6VX32081CJ3922543`, indicating multiple card attempts/retries in the same checkout path.

## Investigation Process

1. Compared WooCommerce order `#451` against nearby successful Advanced Card orders.
2. Confirmed `#451` was a real WooCommerce failed order, not an unpaid-order cancellation.
3. Confirmed the production checkout phone field is currently required via `woocommerce_checkout_phone_field = required`.
4. Inspected the live checkout page and confirmed it uses WooCommerce Blocks checkout.
5. Inspected WooCommerce PayPal Payments `4.0.4` block/card source.
6. Found that the Advanced Card block `createOrder` call sends only basic data:
   - nonce
   - context
   - payment method
   - save-card flag
7. Confirmed that this create-order call does not send the checkout form payer data before `CardFields.submit()` triggers PayPal card confirmation.
8. Confirmed the PayPal plugin does have a server filter, `ppcp_create_order_request_body_data`, that can safely enrich the PayPal create-order body.

## Root Cause

The PayPal Advanced Card block flow creates the PayPal order before the final WooCommerce order exists.

Because the block create-order request did not include full checkout payer data, PayPal could run `confirm-payment-source` without the same customer context that later appeared on the WooCommerce order. That made the failure look like a normal PayPal/card authorization problem, but the checkout integration was withholding useful risk/identity context at the exact moment PayPal made the automated decision.

## Fault Attribution

Primary fault is attributable to the WooCommerce PayPal Payments Advanced Card block integration.

PayPal's processor made the final automated decision, but the integration gave PayPal incomplete checkout context during the card confirmation step. WooCommerce core later saved the phone/address data correctly on the order, which is why the admin order screen looked more complete than the earlier PayPal API decision point.

Not attributable to: the customer, Kinsta, WooCommerce core order storage, or the site theme styling.

## Fix Implemented

Updated:

`wp-content/themes/simms-research-wp/inc/paypal-payments.php`

Added a compatibility filter:

`ppcp_create_order_request_body_data`

For `ppcp-credit-card-gateway` checkout requests, the filter now enriches PayPal's create-order payload with the current WooCommerce customer context before card confirmation:

- payer email
- payer first/last name
- billing address
- payer phone
- shipping phone number when a shipping object exists

Phone values are normalized to digits and capped at PayPal's expected length.

## Production Verification

After deployment:

- Production PHP lint passed for the deployed file.
- WP-CLI simulation confirmed the PayPal create-order body now receives:
  - `payer.email_address`
  - `payer.name`
  - `payer.address`
  - `payer.phone.phone_number.national_number`
  - `purchase_units[0].shipping.phone_number`
- `/checkout/` continued responding normally.
- Production error log showed no new PHP fatal after deployment.

## Expected Outcome

This fix will not prevent every legitimate card decline, because PayPal and card networks can still reject a transaction for real risk, issuer, or processor reasons.

It should reduce false declines caused by PayPal receiving less checkout context than the shopper actually entered on the site.
