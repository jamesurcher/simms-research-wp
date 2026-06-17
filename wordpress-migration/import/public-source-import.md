# Public Source Product and COA Import

This import path uses the public Shopify storefront as the source of truth:

- Products, variants, pricing, availability, descriptions, and image URLs: `https://simmsresearch.com/products.json?limit=250`
- COA batch rows and PDF URLs: `https://simmsresearch.com/pages/lab-results`
- Product technical specs: each public PDP at `https://simmsresearch.com/products/{handle}`

## Generate CSVs

From the repository root:

```sh
node wordpress-migration/bin/scrape-public-source.mjs
```

Output files are written into the mounted plugin path so WP-CLI can read them inside Docker:

```text
wordpress-migration/wp-content/plugins/simms-lab-results/import/generated/products-public.csv
wordpress-migration/wp-content/plugins/simms-lab-results/import/generated/coa-batches-public.csv
wordpress-migration/wp-content/plugins/simms-lab-results/import/generated/source-manifest.json
```

If network access is unavailable but source files have already been downloaded:

```sh
node wordpress-migration/bin/scrape-public-source.mjs \
  --offline \
  --products-file /tmp/simms-products.json \
  --lab-file /tmp/simms-live-lab-results.html
```

Offline mode only fills PDP technical specs when a product page cache is supplied:

```sh
node wordpress-migration/bin/scrape-public-source.mjs \
  --offline \
  --products-file /tmp/simms-products.json \
  --lab-file /tmp/simms-live-lab-results.html \
  --product-page-dir /tmp/simms-product-pages
```

The page cache should contain one file per handle, for example `/tmp/simms-product-pages/bpc-157.html`.

## Dry-Run Import

Start the local sandbox if needed:

```sh
cd wordpress-migration
docker compose up -d
```

Run product dry-run:

```sh
docker exec -u www-data -e HOME=/tmp wordpress-migration-wordpress-1 \
  wp simms import products wp-content/plugins/simms-lab-results/import/generated/products-public.csv --dry-run
```

Run COA dry-run:

```sh
docker exec -u www-data -e HOME=/tmp wordpress-migration-wordpress-1 \
  wp simms import coa wp-content/plugins/simms-lab-results/import/generated/coa-batches-public.csv --dry-run
```

Remove `--dry-run` only after the dry-run counts look right.
