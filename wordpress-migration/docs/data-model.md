# Data Model

## WooCommerce Product

Maps from Shopify `product`.

| WordPress field | Shopify source |
| --- | --- |
| Product title | `product.title` |
| Slug | `product.handle` |
| Description | `product.description` |
| Product image/gallery | Shopify product media/files |
| Price | selected/default variant price |
| Variations | Shopify variants, especially dosage |
| Categories | Shopify collections |
| Stock/SKU | Shopify variant SKU/inventory |

## Product Technical Metadata

Registered as product post meta by the lab-results plugin.

| Meta key | Current Shopify source |
| --- | --- |
| `_simms_cas` | `product.metafields.spec.cas` or `product.metafields.details.cas` |
| `_simms_formula` | `product.metafields.details.formula` |
| `_simms_molecular_weight` | `product.metafields.details.molecular_weight` |
| `_simms_sequence` | `product.metafields.details.sequence` |
| `_simms_form` | `product.metafields.details.form` |
| `_simms_solubility` | `product.metafields.details.solubility` |
| `_simms_storage` | `product.metafields.details.storage` |
| `_simms_purity` | `product.metafields.spec.purity` |
| `_simms_dosage_summary` | Derived from variants/options |

## COA Batch

Registered as the custom post type `simms_coa_batch`.

| Meta key | Current Shopify source |
| --- | --- |
| `_simms_product_id` | Related WooCommerce product ID |
| `_simms_variant_label` | `coa_batch.variant_label` |
| `_simms_batch_id` | `coa_batch.batch_id` |
| `_simms_purity` | `coa_batch.purity` or `coa_batch.avg_purity` |
| `_simms_avg_purity` | `coa_batch.avg_purity` |
| `_simms_vials_tested` | `coa_batch.vials_tested` |
| `_simms_labeled_content` | `coa_batch.labeled_content` |
| `_simms_net_content` | `coa_batch.net_content` |
| `_simms_net_content_delta` | `coa_batch.net_content_delta` |
| `_simms_endotoxins` | `coa_batch.endotoxins` |
| `_simms_test_type` | `coa_batch.test_type` |
| `_simms_tested_at` | `coa_batch.tested_at` |
| `_simms_coa_url` | `coa_batch.coa_url` or exported Shopify file URL |
| `_simms_coa_file_id` | Imported WordPress media attachment ID |
| `_simms_is_current` | `coa_batch.is_current` |

## Global Settings

These should live in WordPress options or Customizer fields.

| Setting | Current source |
| --- | --- |
| Volume pricing enabled | `settings.volume_pricing_enabled` |
| Tier 1 quantity/discount | `settings.volume_tier_1_qty`, `settings.volume_tier_1_pct` |
| Tier 2 quantity/discount | `settings.volume_tier_2_qty`, `settings.volume_tier_2_pct` |
| Tier 3 quantity/discount | `settings.volume_tier_3_qty`, `settings.volume_tier_3_pct` |
| TOS gate background | `settings.tos_gate_background` |
| TOS gate overlay | `settings.tos_gate_overlay_opacity` |

