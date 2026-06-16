# Simms Research 12-Hour WooCommerce Launch Checklist

Goal: get Simms selling again on WooCommerce within 12 hours, with the hero product live, payment working, checkout working, and Shopify traffic redirected.

This is an emergency commerce restoration checklist, not a perfect migration plan.

## Minimum viable launch

Must be live:

- Homepage
- Shop/category page
- Hero product PDP
- Cart
- Checkout
- Payment gateway or manual-payment fallback
- Shipping method
- RUO/compliance language
- Legal/policy pages
- Core redirects
- Analytics basics
- DNS cutover

Defer until after launch:

- Full historical customer/order migration
- Every blog/article
- Perfect lab-result library
- Full affiliate program
- Vercel/headless setup
- Advanced automation
- Cosmetic perfection
- Complex account migration

## Critical path order

1. Kinsta foundation
2. WooCommerce + theme/plugin activation
3. Payment gateway
4. Hero product + essential products
5. Shipping/tax
6. Compliance/legal
7. Checkout test
8. Redirects/SEO basics
9. DNS cutover
10. Post-launch smoke test

---

## Hour 0-1: Kinsta foundation

In Kinsta:

- [X] Create new WordPress site.
- [X] Use a fresh WordPress install.
- [X] Use latest stable PHP supported by Kinsta.
- [X] Use the Kinsta temporary domain during setup.
- [X] Set site name to Simms Research.
- [X] Create secure admin user; do not use `admin`.
- [X] Keep search-engine visibility discouraged during setup if available.
- [X] Confirm `/wp-admin` loads.
- [X] Confirm Kinsta SFTP/SSH details are available.
- [X] Confirm Kinsta logs are available.
- [X] Confirm Kinsta cache controls are available.
- [X] Create an on-demand backup immediately after base install.

Do not point DNS yet.

---

## Hour 1-2: Install WordPress/WooCommerce stack

Install/activate in this order:

1. WooCommerce
2. Simms Lab Results plugin
3. Simms Research WP theme

Local artifact paths:

```text
/Users/James/Simms/Frontend/code/simms-research/wordpress-migration/dist/simms-lab-results.zip
/Users/James/Simms/Frontend/code/simms-research/wordpress-migration/dist/simms-research-wp.zip
```

### Install WooCommerce

WordPress admin:

- [X] Plugins -> Add New Plugin.
- [X] Search `WooCommerce`.
- [X] Install.
- [X] Activate.
- [X] Run basic setup wizard only as far as needed.

### Install Simms Lab Results plugin

WordPress admin:

- [X] Plugins -> Add New Plugin -> Upload Plugin.
- [X] Upload `simms-lab-results.zip`.
- [X] Click Install Now.
- [X] Activate Plugin.

### Install Simms Research WP theme

WordPress admin:

- [X] Appearance -> Themes -> Add New Theme -> Upload Theme.
- [X] Upload `simms-research-wp.zip`.
- [X] Click Install Now.
- [X] Activate.

### Permalinks

WordPress admin:

- [X] Settings -> Permalinks.
- [X] Select `Post name`.
- [X] Save Changes.

### Create required pages

Pages -> Add New:

- [X] Shop
- [X] Cart
- [X] Checkout
- [X] My Account
- [X] Lab Results
- [X] Contact
- [X] FAQ
- [X] Terms
- [X] Privacy Policy
- [X] Refund Policy
- [X] Shipping Policy

### Assign WooCommerce pages

WooCommerce -> Settings -> Advanced -> Page setup:

- [X] Cart page = Cart
- [X] Checkout page = Checkout
- [X] My account page = My Account
- [X] Terms and conditions = Terms

WooCommerce -> Settings -> Products:

- [X] Shop page = Shop

### Lab Results page

If the Lab Results page is blank:

- [X] Pages -> Lab Results -> Edit.
- [X] Right sidebar -> Page Template.
- [X] Set template to `Lab Results`.
- [X] Update page.

---

## Hour 2-4: Product launch data

Do not attempt perfect full catalog import first.

Launch order:

1. Hero product
2. Next 5-10 highest-priority SKUs
3. Rest of catalog after checkout works

For each launch product:

- [ ] Product name
- [ ] Slug/handle
- [ ] Price
- [ ] SKU
- [ ] Stock status
- [ ] Product image
- [ ] Dosage/variant
- [ ] Short description
- [ ] RUO disclaimer
- [ ] CAS, if available
- [ ] Formula, if available
- [ ] Molecular weight, if available
- [ ] Sequence, if available
- [ ] Form, if available
- [ ] Solubility, if available
- [ ] Storage, if available
- [ ] Purity, if available
- [ ] COA link, if available

If variant import is not ready:

- [ ] Create simple products manually for launch.
- [ ] Add variants later.

Hero product PDP checklist:

- [ ] Product visible.
- [ ] Add to cart works.
- [ ] Price correct.
- [ ] Inventory/stock status correct.
- [ ] Image loads.
- [ ] Research-use-only language visible.
- [ ] No human-use/dosing/therapeutic claims.
- [ ] “Not for human consumption” language visible.
- [ ] Shipping/refund expectation clear.
- [ ] Checkout button works.

---

## Hour 2-5 parallel: Payment gateway

This is the real launch blocker.

Install the exact WooCommerce payment gateway required by the processor.

Possible gateway types:

- NMI WooCommerce gateway
- Authorize.net WooCommerce gateway
- Processor-specific WooCommerce plugin
- ACH/eDebit gateway
- Hosted checkout redirect gateway

Payment checklist:

- [ ] Gateway plugin installed.
- [ ] Merchant credentials/API keys entered.
- [ ] Sandbox/test mode first if available.
- [ ] Webhook/callback URL configured.
- [ ] Checkout payment method appears.
- [ ] Test transaction succeeds.
- [ ] Order status updates correctly.
- [ ] Customer confirmation email sends.
- [ ] Admin order email sends.
- [ ] Failed payment shows a clean error.
- [ ] Refund/void behavior understood.

Do not cut DNS until a real or test payment path works.

If card processing is not ready within 12 hours, launch with manual payment fallback:

- [ ] Bank transfer / invoice
- [ ] ACH invoice
- [ ] Wire
- [ ] Manual order approval
- [ ] Pay after invoice

Manual payment is not ideal, but it is better than no order capture.

---

## Hour 4-5: Shipping/tax

Keep shipping simple.

Recommended MVP:

- US only, if that matches current operation
- Flat-rate standard shipping
- Free shipping threshold matching current offer
- Optional expedited later
- No international unless already operational

Shipping checklist:

- [ ] Shipping zone created.
- [ ] Flat rate works.
- [ ] Free shipping threshold works.
- [ ] Cart/checkout show correct shipping.
- [ ] No “no shipping methods available” error.
- [ ] Physical products marked as shipping-required.
- [ ] Product weights added if shipping logic needs them.

Tax checklist:

- [ ] Match current Shopify tax behavior if known.
- [ ] Keep launch settings simple and revisit after launch.

---

## Hour 5-6: Compliance/legal hardening

Global requirements:

- [ ] Footer disclaimer visible sitewide.
- [ ] Product pages include RUO language.
- [ ] Cart/checkout include acknowledgment if possible.
- [ ] Terms page live.
- [ ] Privacy Policy live.
- [ ] Refund Policy live.
- [ ] Shipping Policy live.
- [ ] Contact page live.

Minimum RUO language placement:

1. Footer
2. Product page
3. Cart or checkout
4. Terms page

Avoid:

- Human dosage language
- Treatment claims
- Weight-loss claims
- Injury/healing claims
- “For injection” language
- “Safe for human use” language
- Disease/condition claims

If there is time:

- [ ] Add required checkout checkbox: “I understand these products are sold for research use only and are not for human consumption.”

If there is not time:

- [ ] Put disclaimer visibly above checkout/place-order area and in Terms.

---

## Hour 6-7: Theme/visual smoke test

Test on desktop and mobile:

- [ ] Homepage
- [ ] Shop
- [ ] Hero product
- [ ] Cart
- [ ] Checkout
- [ ] Lab Results
- [ ] FAQ
- [ ] Contact
- [ ] Terms/policies

Visual pass/fail standard: not perfect; trustworthy enough to transact.

Check:

- [ ] Header works.
- [ ] Logo loads.
- [ ] Cart icon works.
- [ ] Mobile menu works.
- [ ] Product grid works.
- [ ] PDP purchase area works.
- [ ] Buttons are readable.
- [ ] No broken fonts.
- [ ] No broken icons.
- [ ] No white text on white background.
- [ ] No obvious Shopify leftovers.
- [ ] No dead Shopify URLs in nav/footer.
- [ ] Checkout does not look broken.

---

## Hour 7-8: Analytics + email basics

Tracking minimum:

- [ ] GA4 or Google Tag Manager installed.
- [ ] Meta Pixel installed if already used.
- [ ] Microsoft Clarity installed if already used.
- [ ] Pageview fires once.
- [ ] Checkout page loads with tracking.

Purchase event is nice-to-have, not a launch blocker.

Transactional email:

- [ ] WooCommerce emails enabled.
- [ ] From name/email set.
- [ ] Test customer order confirmation.
- [ ] Test admin new-order email.

Better if time:

- [ ] Install SMTP plugin.
- [ ] Authenticate sending domain via Google Workspace/Brevo/etc.

---

## Hour 8-9: Redirects

Install a redirect plugin or configure redirects on server/Cloudflare.

Minimum redirect map:

- [ ] `/products/{handle}` -> product page, or preserve exact URL if possible.
- [ ] `/collections/all` -> `/shop`
- [ ] `/collections/{handle}` -> category/shop equivalent
- [ ] `/pages/lab-results` -> `/lab-results`
- [ ] `/pages/about-us` -> `/about-us`
- [ ] `/pages/contact` -> `/contact`
- [ ] `/pages/faq` -> `/faq`
- [ ] `/pages/partners` -> `/partners`
- [ ] `/cart` -> `/cart`

Core redirect rule:

Old traffic should never hit a 404 for:

- [ ] Hero product
- [ ] Shop/all collection
- [ ] Cart
- [ ] Lab results
- [ ] Policy pages
- [ ] Contact/FAQ

---

## Hour 9-10: Pre-DNS full checkout test

Before DNS, on the Kinsta temporary domain:

- [ ] Open homepage.
- [ ] Click into shop.
- [ ] Open hero product.
- [ ] Add hero product to cart.
- [ ] Change quantity.
- [ ] Go to cart.
- [ ] Apply coupon if any.
- [ ] Continue checkout.
- [ ] Enter shipping address.
- [ ] Confirm shipping method appears.
- [ ] Select payment.
- [ ] Place test order.
- [ ] Confirm order created in WooCommerce.
- [ ] Confirm customer email received.
- [ ] Confirm admin email received.
- [ ] Confirm payment status correct.
- [ ] Confirm order has correct product/SKU/price/shipping/tax.
- [ ] Confirm no compliance/legal issue in checkout copy.
- [ ] Confirm mobile checkout works.

If any of these fail, do not cut DNS unless manual-payment fallback is acceptable.

---

## Hour 10-11: DNS cutover

Before switching:

- [ ] Lower DNS TTL if not already low.
- [ ] Screenshot current Shopify DNS/settings for rollback reference.
- [ ] Confirm Kinsta domain instructions.
- [ ] Add production domain to Kinsta.
- [ ] Set SSL for production domain.
- [ ] Confirm www/non-www preference.

Recommended canonical:

- `simmsresearch.com` primary
- `www.simmsresearch.com` redirects to primary, or vice versa
- Use one canonical only

If using Cloudflare:

- [ ] Point A/CNAME according to Kinsta instructions.
- [ ] Keep proxy on if Kinsta supports/recommends it.
- [ ] Ensure SSL mode is Full/Strict when certificate is valid.
- [ ] Avoid redirect loops.

After DNS:

- [ ] Purge Kinsta cache.
- [ ] Purge Cloudflare cache.
- [ ] Re-save WordPress permalinks if needed.

---

## Hour 11-12: Live verification

Run live smoke test on production domain:

- [ ] Homepage loads.
- [ ] No SSL warnings.
- [ ] Product page loads.
- [ ] Hero product is purchasable.
- [ ] Cart works.
- [ ] Checkout works.
- [ ] Payment works.
- [ ] Shipping works.
- [ ] WooCommerce order appears.
- [ ] Email sends.
- [ ] Mobile checkout works.
- [ ] Old Shopify hero product URL redirects or resolves.
- [ ] `/collections/all` resolves.
- [ ] `/cart` resolves.
- [ ] `/pages/lab-results` resolves.
- [ ] Legal pages resolve.
- [ ] Analytics fires once.
- [ ] Search engines are not blocked accidentally.
- [ ] Kinsta cache is not caching cart/checkout/account pages.
- [ ] No obvious 500/PHP fatal errors in Kinsta logs.

---

## Launch blockers

These block go-live:

- No working payment or manual-payment fallback
- Hero product not purchasable
- Checkout broken
- Shipping method missing
- SSL broken
- Product/legal pages contain risky human-use claims
- DNS/domain not resolving
- WooCommerce order not created after checkout

These do not block go-live:

- Full catalog incomplete
- Blog not migrated
- Perfect lab results not done
- Affiliate program not live
- Historical orders not migrated
- Exact Shopify design not 1:1
- Advanced tracking not perfect
- All redirects not complete, as long as core sales URLs work

---

## Tonight's recommended stack

Use now:

- Kinsta WordPress
- WooCommerce
- Simms custom theme zip
- Simms Lab Results plugin zip
- Payment gateway plugin
- Simple shipping
- Core policies
- Core redirects
- Cloudflare/DNS
- Manual/urgent checkout QA

Do not add tonight unless absolutely needed:

- Elementor
- Giant page builders
- Too many CRO plugins
- Affiliate plugin
- Complex discount plugin
- Subscriptions
- Vercel/headless
- Full customer/order migration

---

## After launch: next 48-hour cleanup

- [ ] Import rest of catalog.
- [ ] Finish redirects from full crawl.
- [ ] Add/verify COA records.
- [ ] Improve PDP design.
- [ ] Configure SMTP properly.
- [ ] Configure purchase tracking/server-side events.
- [ ] Add AffiliateWP or chosen affiliate plugin.
- [ ] Add backup/export routine.
- [ ] Set GitHub/Kinsta deploy loop.
- [ ] Document payment/fulfillment SOP.

## Win condition

A customer can land on Simms, buy the hero product, pay successfully or submit a manual-payment order, receive an order confirmation, and you can fulfill the order.
