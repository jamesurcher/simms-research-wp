# Simms WordPress Design Iteration Loop

Purpose: make the WooCommerce site visually catch up to the original Shopify site as fast as possible without touching live WooCommerce database data.

## The loop we are using

1. Edit code locally in this repo.
2. Package or deploy only these code directories:
   - `wordpress-migration/wp-content/themes/simms-research-wp/`
   - `wordpress-migration/wp-content/plugins/simms-lab-results/`
3. Test on the Kinsta temporary/staging URL.
4. Compare against the original Shopify site.
5. Patch the theme/plugin again.
6. Repeat until home, shop, PDP, cart, and checkout are acceptable.

## Do not push databases during this loop

WooCommerce orders, customers, products, menus, pages, payment settings, tax settings, shipping settings, and plugin settings live in the WordPress database.

For design iteration, deploy code only:

- theme PHP/CSS/JS
- plugin PHP/CSS/JS
- WooCommerce template overrides
- assets/fonts/icons

Do not sync a local database into production once orders are possible.

## Immediate manual ZIP loop

Use this when SSH/SFTP is not wired yet.

From repo root:

```sh
./wordpress-migration/bin/package-wordpress-artifacts.sh
```

This creates:

```text
wordpress-migration/dist/simms-research-wp.zip
wordpress-migration/dist/simms-lab-results.zip
```

Upload updated theme:

WordPress admin -> Appearance -> Themes -> Add New Theme -> Upload Theme -> `simms-research-wp.zip`

Upload updated plugin only if plugin changed:

WordPress admin -> Plugins -> Add New Plugin -> Upload Plugin -> `simms-lab-results.zip`

Then hard-refresh and test:

- homepage
- shop page
- hero product page
- add to cart
- cart
- checkout
- lab results

## Faster Kinsta SSH/SFTP loop

Use once MyKinsta SSH/SFTP details are available.

Set the deploy variables for the current terminal session:

```sh
export KINSTA_SSH_TARGET='username@hostname'
export KINSTA_SSH_PORT='12345'
export KINSTA_WP_ROOT='/www/sitename/public'
```

Dry run first:

```sh
DRY_RUN=1 ./wordpress-migration/bin/deploy-code-to-kinsta.sh
```

Real code-only deploy:

```sh
./wordpress-migration/bin/deploy-code-to-kinsta.sh
```

Deploy theme only:

```sh
DEPLOY_PLUGIN=0 ./wordpress-migration/bin/deploy-code-to-kinsta.sh
```

Deploy plugin only:

```sh
DEPLOY_THEME=0 ./wordpress-migration/bin/deploy-code-to-kinsta.sh
```

## Data needed from Kinsta

From MyKinsta -> WordPress Sites -> Simms site -> Info / SFTP-SSH:

- SSH host
- SSH port
- SSH username
- WordPress public path, usually shaped like `/www/<site>/public`
- temporary/staging URL

Do not paste passwords into chat if avoidable. Use SSH key auth or enter passwords locally when the command prompts.

## Design QA priority order

1. Header: does it look like Simms, not stock WordPress?
2. Homepage hero: above-fold brand parity.
3. Product card grid: clean image, title, price, always-visible CTA.
4. Hero product PDP: image left, sticky details right, price, variants, add-to-cart, trust/COA/research details.
5. Cart: readable, working totals, working checkout button.
6. Checkout: clean enough, fields readable, payment method visible, place-order works.
7. Footer/legal: RUO disclaimer, policies, contact.

## Definition of good enough for go-live

- User can land on the homepage and understand the brand.
- User can open the hero product page and it looks intentional/premium.
- User can add to cart and reach checkout.
- Checkout does not look broken or suspicious.
- Lab results page exists and is reachable.
- No database syncs are needed for design-only patches.
