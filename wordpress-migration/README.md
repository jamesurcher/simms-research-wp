# Simms Research WordPress Migration

This folder is a local migration starter kit for moving the current Shopify Horizon theme to WordPress + WooCommerce.

It is intentionally isolated from the live Shopify theme files. Nothing in this folder is loaded by Shopify.

## What Is Set Up

- `wp-content/themes/simms-research-wp/` - starter custom WordPress theme.
- `wp-content/plugins/simms-lab-results/` - starter plugin for COA/lab-result records.
- `docs/` - migration plan, data model, component map, and QA checklist.
- `import/` - CSV schemas and notes for products and COA batches.
- `wp-content/plugins/simms-lab-results/import/manual/` - hand-audited migration CSVs that need to be available to WP-CLI inside WordPress.
- `redirects/` - starter Shopify-to-WordPress redirect map.

## Target Stack

- WordPress
- WooCommerce
- Custom Simms Research theme
- Custom Simms Lab Results plugin
- Optional ACF Pro for editor-friendly field groups

## Local Install Shape

When a WordPress install exists, copy or symlink:

```text
wordpress-migration/wp-content/themes/simms-research-wp
wordpress-migration/wp-content/plugins/simms-lab-results
```

into:

```text
wp-content/themes/simms-research-wp
wp-content/plugins/simms-lab-results
```

Then activate:

1. WooCommerce
2. Simms Lab Results
3. Simms Research WP theme

## Current Limits

This starter does not include order/customer migration, payment gateway configuration, or production hosting configuration. Those require store/admin/server access.
