# Checkout TOS / Age / Research-Use Consent Checkbox

Purpose: how the explicit consent checkbox on the WooCommerce block checkout
works, why it's built the way it is, and what it records on each order. All of it
lives in deployable theme code — no database or order data is touched (consistent
with [data-handling](data-handling.md)).

## What the shopper sees

WooCommerce's block checkout normally shows an *implicit* line ("By proceeding
with your purchase you agree to our Terms…"). We replace that with a single,
**required** checkbox they must tick to place the order:

> ☐ I confirm I am at least 21 years of age and acknowledge all products
> purchased are intended for laboratory and research use, not for human or animal
> consumption. I have read and agree to the [Terms and Conditions] and
> [Privacy Policy].

"Terms and Conditions" → `/terms-conditions/`, "Privacy Policy" → `/privacy-policy/`
(both open in a new tab). The "Add a note to your order" field is also hidden.

## How it works

All in `wp-content/themes/simms-research-wp/inc/checkout-terms.php` (loaded from
`functions.php`), plus a few lines of CSS in `assets/css/simms-sections.css`.

- **The checkbox** — we transform WooCommerce's native `checkout-terms-block` in
  code via the `render_block_data` filter: set `checkbox = true` and `text =`
  our consent HTML. WordPress emits these as `data-checkbox` / `data-text`
  attributes that the checkout's React frontend hydrates from.
- **Hiding "Add a note"** — a `render_block` filter returns empty for
  `woocommerce/checkout-order-note-block`.

### Why the native terms block and not an "additional checkout field"

WooCommerce's Additional Checkout Fields API can add a server-validated,
auto-recorded checkbox — but its label is rendered as **plain text** (the label
is passed to the checkbox control as a string and React-escapes it), so inline
**links are impossible**. The native terms block renders its `text` as sanitized
HTML, so the Terms/Privacy links render and the long text wraps cleanly on mobile.
The links requirement is what forced the native block.

## Enforcement: client-side gate (a deliberate trade)

The native checkbox is a **client-side** gate: the browser will not place the
order until it's ticked (the requirement is registered as a validation error and
revealed on the Place Order click). The WooCommerce Store API has **no**
server-side terms validation, so — unlike the additional-field API — the backend
itself doesn't reject an unticked submission from a non-browser path.

This is an accepted trade because (a) server "enforcement" is trivially bypassed
by any script that just sends `true`, so its real protective value is low, and
(b) we record consent on every placed order regardless (below). This matches how
Shopify's own TOS checkbox behaves.

## The per-order consent record

On `woocommerce_store_api_checkout_order_processed` we stamp a self-contained,
defensible record as order meta. Because the checkbox is required, every placed
order carries it.

| Meta key | Value |
|---|---|
| `_simms_terms_accepted` | `yes` |
| `_simms_terms_accepted_at` | acceptance time (GMT, `Y-m-d H:i:s`) |
| `_simms_terms_text` | the exact consent HTML the shopper saw |
| `_simms_terms_version` | short md5 tag of that text (group orders by wording) |
| `_simms_terms_ip` | shopper IP at consent |
| `_simms_terms_user_agent` | shopper user agent at consent |

Snapshotting the text + version means the wording can change later without
rewriting history — each order keeps what *that* shopper agreed to.

It surfaces on the **admin order edit screen** (below the billing address, via
`woocommerce_admin_order_data_after_billing_address`): "Accepted at checkout —
<date>", the IP/user-agent, and the exact text agreed to (with working links).

## Changing the wording

Edit `simms_checkout_terms_text()` in `inc/checkout-terms.php`. New orders get the
new text and a new `_simms_terms_version`; existing orders keep their snapshot.

## Reverting

Remove (or comment out) the filters in `inc/checkout-terms.php`. The live checkout
page content is never modified, so removing the code restores WooCommerce's
default implicit terms text and the order-note field.

## Status

Built, verified locally (WP-CLI integration tests), and **deployed to live** on
2026-06-20 (theme version `0.1.53`) via the code-only deploy — see
[design-iteration-loop](design-iteration-loop.md). Verify with a live smoke test:
load `/checkout` with an item in the cart and confirm the checkbox + links render
and that an unticked Place Order is blocked.

## In one line

A required, link-bearing 21+/research-use consent checkbox on the block checkout
(client-side gated), with a full per-order consent record (text, version, IP, UA,
timestamp) shown in wp-admin — all in theme code, nothing in the database.
