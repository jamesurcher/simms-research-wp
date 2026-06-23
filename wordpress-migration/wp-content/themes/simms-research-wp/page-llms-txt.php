<?php
/**
 * Plain llms-txt page ported from Shopify's layout-none template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$origin = untrailingslashit( home_url() );

header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
?><meta name="robots" content="noindex">
# Simms

> US-sourced research compounds with 99%+ purity, trusted worldwide.

## About

Simms Research is a United States-based supplier of research-grade peptides headquartered in Tampa, Florida. All compounds are synthesized in cGMP-aligned US facilities, independently verified by third-party HPLC and mass spectrometry, and ship with a batch-level Certificate of Analysis (COA).

## Key Facts

- Purity: &ge;99% HPLC verified
- Testing: Independent third-party laboratory verification on every batch
- Documentation: Certificate of Analysis published for every product, every lot
- Location: Tampa, FL, United States
- Shipping: Same-day processing, free 2-day air on orders over $200 (US)
- Form: Lyophilized powder, temperature-controlled shipping

## Products

Research-grade peptides supplied for laboratory, analytical, and research use only. Not for human consumption, medical use, or veterinary use.

Each product page includes:
- CAS number
- Molecular formula and weight
- Amino acid sequence
- HPLC purity percentage
- Batch-level Certificate of Analysis

## Pages

- Homepage: <?php echo esc_url( $origin ) . "\n"; ?>
- All Products: <?php echo esc_url( $origin . '/shop' ) . "\n"; ?>
- Lab Results / COA Library: <?php echo esc_url( $origin . '/lab-results' ) . "\n"; ?>
- About / Quality: <?php echo esc_url( $origin . '/about' ) . "\n"; ?>
- FAQ: <?php echo esc_url( $origin . '/faq' ) . "\n"; ?>
- Contact: <?php echo esc_url( $origin . '/contact' ) . "\n"; ?>

## Contact

- Website: <?php echo esc_url( $origin ) . "\n"; ?>
- Support: <?php echo esc_url( $origin . '/contact' ) . "\n"; ?>
<?php
exit;
