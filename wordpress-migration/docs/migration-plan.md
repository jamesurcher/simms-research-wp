# Migration Plan

## Phase 1 - Inventory

- Crawl all live Shopify URLs.
- Export products, variants, collections, images, metafields, metaobjects, pages, blogs, redirects, and files.
- Identify Shopify apps that affect checkout, discounts, compliance, email, analytics, shipping, tax, account, and pixel behavior.
- Confirm every current page maps to a WordPress template or reusable block.

## Phase 2 - WordPress Foundation

- Provision a staging WordPress install.
- Install WooCommerce.
- Install the `simms-lab-results` plugin scaffold.
- Activate the `simms-research-wp` theme scaffold.
- Configure permalinks and WooCommerce base URLs.
- Configure product image sizes.

## Phase 3 - Data Model

- Register product technical fields.
- Register COA batch records.
- Link COA batches to WooCommerce products.
- Build import scripts after Shopify exports exist.
- Verify one representative product end-to-end before bulk import.

## Phase 4 - Theme Build

- Build global header, announcement bar, account/cart icons, footer, and legal disclaimer.
- Build homepage sections from reusable components.
- Build catalog/product archive grid.
- Build product card.
- Build PDP layout with variants, bundle tiers, sticky add-to-cart, research profile, technical specs, and COA card.
- Build Lab Results library from COA batch records.
- Build about, FAQ, contact, partners, affiliate, and legal pages.

## Phase 5 - Commerce

- Configure payment gateway.
- Configure shipping methods and free-shipping threshold.
- Configure taxes.
- Recreate bundle/volume discounts.
- Configure emails.
- Confirm account migration strategy.

## Phase 6 - SEO And Launch

- Preserve or redirect every Shopify URL.
- Preserve title/meta descriptions where available.
- Move analytics and pixels.
- Run mobile/desktop visual QA.
- Run checkout QA.
- Perform final delta import.
- Switch DNS.

