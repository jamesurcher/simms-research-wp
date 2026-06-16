# Wiring The WordPress Scaffold

There are two ways to wire this up.

## Option A - Local Docker WordPress

Use this when you want a fresh local WordPress sandbox.

```sh
cd wordpress-migration
cp .env.example .env
docker compose up -d
```

Then open:

```text
http://localhost:8080
```

In WordPress admin:

1. Complete the WordPress installer.
2. Install and activate WooCommerce.
3. Activate `Simms Lab Results`.
4. Activate `Simms Research WP`.
5. Create pages named `Shop`, `Cart`, `Checkout`, `My account`, and `Lab Results`.
6. Set `Lab Results` to use the `Lab Results` template if WordPress does not auto-detect `page-lab-results.php`.
7. Go to WooCommerce settings and assign the WooCommerce pages.
8. Go to Appearance -> Menus and assign the primary/footer menus.

## Option B - Existing WordPress Install

Use this when WordPress already exists on your server or locally.

```sh
./wordpress-migration/bin/link-into-wp.sh /absolute/path/to/wordpress-root
```

Then in WordPress admin:

1. Install and activate WooCommerce.
2. Activate `Simms Lab Results`.
3. Activate `Simms Research WP`.

## Option C - WordPress.com Uploads

Use this when your site is hosted on WordPress.com.

First package the custom theme and plugin:

```sh
./wordpress-migration/bin/package-wordpress-artifacts.sh
```

This creates:

```text
wordpress-migration/dist/simms-research-wp.zip
wordpress-migration/dist/simms-lab-results.zip
```

Upload order in WordPress.com:

1. Install and activate WooCommerce from Plugins.
2. Upload and activate `simms-lab-results.zip` from Plugins -> Add New Plugin -> Upload Plugin.
3. Upload and activate `simms-research-wp.zip` from Appearance -> Themes -> Add Theme -> Upload Theme.

For repeated code syncs on WordPress.com, use GitHub Deployments or SFTP on a Business or Commerce plan. Manual ZIP upload is fine for the first smoke test, but it is clumsy once development is active.

### Upload Troubleshooting

- `simms-lab-results.zip` is the plugin. Upload it through Plugins.
- `simms-research-wp.zip` is the theme. Upload it through Appearance -> Themes, not Plugins.
- If the normal WordPress.com theme screen fails, try the classic WP Admin upload URL:

```text
/wp-admin/theme-install.php
```

- If WordPress says the theme is missing `style.css`, regenerate the ZIP:

```sh
./wordpress-migration/bin/package-wordpress-artifacts.sh
```

## First Smoke Test

After activation:

1. Add one WooCommerce product.
2. Fill in its Simms technical fields.
3. Add one `COA Batch` record and set its product ID to that product.
4. Visit `/lab-results/`.
5. Confirm the product appears in the Lab Results grid.

## Notes

- This is a scaffold. It will not import Shopify data until Shopify exports are available.
- The theme is guarded so it can activate before WooCommerce, but WooCommerce is required for catalog/cart/checkout work.
- Product images, product data, COA files, menus, and pages still need to be imported or created.
