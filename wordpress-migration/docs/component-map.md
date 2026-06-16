# Component Map

## Global

| Shopify source | WordPress target |
| --- | --- |
| `layout/theme.liquid` | `header.php`, `footer.php`, `functions.php` |
| `sections/header.liquid` | Theme header template part |
| `sections/footer.liquid` | Theme footer template part |
| `snippets/custom-fonts.liquid` | `assets/css/simms-base.css` |
| `snippets/theme-overrides.liquid` | `assets/css/simms-base.css` and template-specific CSS |
| `snippets/tos-gate.liquid` | Theme access gate component |

## Commerce

| Shopify source | WordPress target |
| --- | --- |
| `snippets/product-card.liquid` | `template-parts/product-card.php` and `woocommerce/content-product.php` |
| `sections/main-collection.liquid` | `woocommerce/archive-product.php` |
| `sections/product-information.liquid` | WooCommerce single product template override |
| `snippets/volume-tier-selector.liquid` | PDP quantity-tier component and WooCommerce discount rules |
| `snippets/product-research-details.liquid` | PDP product-spec template part |
| `snippets/batch-verification.liquid` | PDP COA template part |
| `sections/product-recommendations.liquid` | WooCommerce related products template |

## Content Pages

| Shopify source | WordPress target |
| --- | --- |
| `sections/lab-results-index.liquid` | `page-lab-results.php` and `simms_coa_batch` records |
| `sections/faq-page.liquid` | Page template or FAQ block |
| `sections/contact-page.liquid` | Page template plus form plugin |
| `sections/about-quality.liquid` | Reusable editorial section/block |
| `sections/process-steps.liquid` | Reusable process block |
| `sections/precision-verify.liquid` | Reusable stats/feature block |
| `sections/newsletter-research.liquid` | Newsletter integration block |
| `sections/cta-research.liquid` | CTA block |

