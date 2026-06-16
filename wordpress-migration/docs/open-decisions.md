# Open Decisions

These do not block the local scaffold, but they must be answered before launch.

## Commerce

- Final WooCommerce product URL base: `/product/{handle}` or preserved `/products/{handle}`.
- Payment gateway and underwriting status for the catalog.
- Whether historical Shopify customers/orders need to be migrated or archived.
- Whether customers should receive account invitation/password reset emails.
- Final shipping zones, methods, rates, and free-shipping threshold.
- Final volume discount mechanism: WooCommerce coupon, pricing plugin, or custom code.
- Whether shipment protection remains as a paid add-on, included promise, or removed.

## Data

- Source of truth for COA files: WordPress media library, private object storage, or public CDN.
- Whether COA batch records should be a custom post type or custom database table after final volume is known.
- Whether ACF Pro will be used for editor-friendly field groups.
- Whether lab-result detail pages need their own public URLs.

## Content

- Final list of all 30+ pages.
- Which Shopify pages become editable WordPress pages versus hard-coded templates.
- Legal/compliance copy approval.
- Newsletter provider.
- Contact/application form provider.

## Launch

- Hosting environment.
- CDN/cache layer.
- Redirect manager.
- Analytics/pixel stack.
- DNS cutover process.

