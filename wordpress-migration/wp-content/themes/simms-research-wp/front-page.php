<?php
/**
 * Front page — 1:1 port of Shopify templates/index.json.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<!-- ============ HERO ============ -->
<section class="hero color-scheme-2">
	<div class="hero__media">
		<img src="<?php echo esc_url( SIMMS_THEME_URI . '/assets/images/hero.png' ); ?>" alt="" fetchpriority="high">
	</div>
	<div class="hero__inner">
		<p class="hero__eyebrow"><?php esc_html_e( 'Premium Research-Grade Peptides', 'simms-research' ); ?></p>
		<h1 class="hero__title"><?php esc_html_e( 'Simms Research', 'simms-research' ); ?></h1>
		<p class="hero__subtitle"><?php esc_html_e( 'Where precision meets excellence. US-based compounds with 99%+ purity trusted worldwide.', 'simms-research' ); ?></p>
		<div class="hero__buttons">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop Now', 'simms-research' ); ?></a>
			<a class="btn btn--secondary" href="<?php echo esc_url( home_url( '/lab-results/' ) ); ?>"><?php esc_html_e( 'View Lab Results', 'simms-research' ); ?></a>
		</div>
	</div>
</section>

<!-- ============ TRUST STRIP ============ -->
<section class="trust-strip section section--page-width color-scheme-1">
	<div class="trust-strip__inner">
		<div class="trust-strip__grid">
			<?php
			$trust_columns = array(
				array( 'badge-check', '99%+', 'Purity Guaranteed' ),
				array( 'file-text', 'COA Included', 'Full Documentation' ),
				array( 'flask-conical', 'Third Party', 'Lab Tested' ),
				array( 'truck', '2-Day', 'Fast Delivery' ),
			);
			foreach ( $trust_columns as $col ) :
				?>
				<div class="trust-strip__column">
					<span class="trust-strip__icon" aria-hidden="true"><?php echo simms_inline_icon( $col[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div class="trust-strip__value"><?php echo esc_html( $col[1] ); ?></div>
					<div class="trust-strip__label"><?php echo esc_html( $col[2] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ============ POPULAR PRODUCTS ============ -->
<section class="home-products section section--page-width color-scheme-1">
	<div class="home-products__inner">
		<div class="home-products__head">
			<p class="home-products__eyebrow"><?php esc_html_e( 'New Arrivals', 'simms-research' ); ?></p>
			<h2 class="home-products__title"><?php esc_html_e( 'Latest research peptides', 'simms-research' ); ?></h2>
		</div>
		<?php
		$home_products = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'post_name__in'  => array( 'bpc-157', 'mots-c', 'nad', 'semax', 'selank', 'ghk-cu', 'thymosin-alpha-1', 'ipamorelin' ),
				'posts_per_page' => 8,
				'orderby'        => 'post_name__in',
				'meta_query'     => array(
					array(
						'key'     => '_price',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);
		if ( $home_products->have_posts() ) :
			?>
			<ul class="home-products__grid">
				<?php
				while ( $home_products->have_posts() ) :
					$home_products->the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;
				wp_reset_postdata();
				?>
			</ul>
		<?php endif; ?>
	</div>
</section>

<!-- ============ PROCESS STEPS ============ -->
<section class="process-steps section section--page-width color-scheme-1">
	<div class="process-steps__inner">
		<header class="process-steps__hero">
			<p class="process-steps__eyebrow"><?php esc_html_e( 'Simple process', 'simms-research' ); ?></p>
			<h2 class="process-steps__heading"><?php esc_html_e( 'Three steps to your next compound.', 'simms-research' ); ?></h2>
			<p class="process-steps__subhead"><?php esc_html_e( 'Verified compounds with full documentation.', 'simms-research' ); ?></p>
		</header>
		<ol class="process-steps__grid">
			<?php
			$steps = array(
				array( 'list-filter', 'Browse & select', 'Filter the catalog by research category, purity grade, or molecular target.' ),
				array( 'file-text', 'Review the COA', 'View the third-party Certificate of Analysis before you order. Full mass-spec data on file.' ),
				array( 'package', 'Order research-ready', 'Ships same-day, lyophilized and stable. Arrives in 2 days, ready for your protocol.' ),
			);
			foreach ( $steps as $i => $step ) :
				$num = sprintf( '%02d', $i + 1 );
				?>
				<li class="process-steps__card">
					<span class="process-steps__icon" aria-hidden="true"><?php echo simms_inline_icon( $step[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="process-steps__bignum" aria-hidden="true"><?php echo esc_html( $num ); ?></span>
					<div class="process-steps__body">
						<p class="process-steps__step-label"><?php printf( esc_html__( 'Step %s', 'simms-research' ), esc_html( $num ) ); ?></p>
						<h3 class="process-steps__title"><?php echo esc_html( $step[1] ); ?></h3>
						<p class="process-steps__desc"><?php echo esc_html( $step[2] ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
		<div class="process-steps__footer">
			<p class="process-steps__footnote"><?php esc_html_e( 'Same-day shipping. Free 2-day air on orders $200+.', 'simms-research' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="process-steps__cta">
				<span><?php esc_html_e( 'Browse compounds', 'simms-research' ); ?></span>
				<svg class="process-steps__cta-arrow" width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M1 7H13M13 7L8 2M13 7L8 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</a>
		</div>
	</div>
</section>

<!-- ============ PRECISION VERIFY ============ -->
<section class="precision-verify section section--page-width color-scheme-1">
	<div class="precision-verify__inner">
		<header class="precision-verify__hero">
			<div class="precision-verify__hero-left">
				<p class="precision-verify__eyebrow"><?php esc_html_e( 'Why Simms Research', 'simms-research' ); ?></p>
				<h2 class="precision-verify__heading"><?php esc_html_e( 'Precision you can verify.', 'simms-research' ); ?></h2>
			</div>
			<div class="precision-verify__hero-right">
				<p class="precision-verify__body"><?php esc_html_e( 'We connect researchers with precision-grade peptides through expert synthesis, independent third-party testing, and complete documentation — every step of the way.', 'simms-research' ); ?></p>
			</div>
		</header>
		<div class="precision-verify__pillars">
			<?php
			$pillars = array(
				array( 'Independent Verification', 'Every compound verified by third-party HPLC and Mass Spectrometry before it ships. No exceptions.' ),
				array( 'Full COA Transparency', 'Certificates of Analysis published for every product, every batch.' ),
				array( 'Free Shipping', 'Via 2-Day Air on orders $200+ to anywhere in the United States, including Alaska and Hawaii.' ),
				array( 'Complete Scientific Documentation', 'CAS number, molecular weight, amino acid sequence, and storage specifications included.' ),
			);
			foreach ( $pillars as $i => $pillar ) :
				?>
				<article class="precision-verify__pillar">
					<p class="precision-verify__pillar-num"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></p>
					<h3 class="precision-verify__pillar-title"><?php echo esc_html( $pillar[0] ); ?></h3>
					<p class="precision-verify__pillar-body"><?php echo esc_html( $pillar[1] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
		<div class="precision-verify__stats">
			<?php
			$stats = array(
				array( '≥99%', 'Minimum purity' ),
				array( '100%', 'COA coverage' ),
				array( '20+', 'Batches tested' ),
				array( 'Same day', 'Processing' ),
			);
			foreach ( $stats as $stat ) :
				?>
				<div class="precision-verify__stat">
					<p class="precision-verify__stat-value"><?php echo esc_html( $stat[0] ); ?></p>
					<p class="precision-verify__stat-label"><?php echo esc_html( $stat[1] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ============ FAQ ============ -->
<section class="faq-accordion section section--page-width color-scheme-1">
	<div class="faq-accordion__inner">
		<header class="faq-accordion__hero">
			<p class="faq-accordion__eyebrow"><?php esc_html_e( 'FAQ', 'simms-research' ); ?></p>
			<h2 class="faq-accordion__heading"><?php esc_html_e( 'Questions researchers ask.', 'simms-research' ); ?></h2>
			<p class="faq-accordion__contact">
				<span class="faq-accordion__contact-prefix"><?php esc_html_e( "Can't find your answer?", 'simms-research' ); ?></span>
				<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="faq-accordion__contact-link"><?php esc_html_e( 'Contact us', 'simms-research' ); ?> <span aria-hidden="true">&rarr;</span></a>
			</p>
		</header>
		<ul class="faq-accordion__list">
			<?php
			$faqs = array(
				array( 'How is purity verified?', 'Every batch is tested in-house by HPLC and independently re-verified by a third-party laboratory. We publish the Certificate of Analysis for each lot before the product ships.' ),
				array( "What's included in the COA?", "Each Certificate of Analysis includes the compound's identity, HPLC purity percentage, mass-spec verification, lot number, manufacture date, and a sign-off from the testing laboratory." ),
				array( 'How are compounds shipped and stored?', 'Compounds ship lyophilized in temperature-controlled, insulated packaging with cold packs. Once received, store at the temperature specified on the COA — typically refrigerated or frozen.' ),
				array( 'Can I access historical COAs for past lots?', 'Yes. Every lot we have ever shipped is searchable in the public COA library by batch ID. Reach out if you need a hand locating a specific record.' ),
				array( 'What is the replacement policy on purity?', 'If a batch does not meet the purity threshold listed on the COA, we replace it — no questions. Independent re-verification is welcome and reimbursable.' ),
				array( 'Do you ship internationally?', "Domestic US shipping is supported by default. International availability depends on the destination country's regulations — contact us for a routing assessment before placing an order." ),
				array( 'How quickly do orders process?', 'Orders placed before the same-day cutoff ship the same business day. Most US destinations arrive within 1–3 business days, all temperature-controlled end-to-end.' ),
				array( 'Where are the compounds synthesized?', 'All compounds are synthesized in cGMP-aligned facilities in the United States, then third-party tested before they are released for sale.' ),
			);
			foreach ( $faqs as $faq ) :
				?>
				<li class="faq-accordion__item">
					<details class="faq-accordion__details">
						<summary class="faq-accordion__summary">
							<span class="faq-accordion__question"><?php echo esc_html( $faq[0] ); ?></span>
							<span class="faq-accordion__chevron" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 5L7 9L11 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
						</summary>
						<div class="faq-accordion__answer"><p><?php echo esc_html( $faq[1] ); ?></p></div>
					</details>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<!-- ============ CTA RESEARCH ============ -->
<section class="cta-research section section--page-width">
	<div class="cta-research__inner">
		<div class="cta-research__card">
			<div class="cta-research__glow" aria-hidden="true"></div>
			<div class="cta-research__content">
				<p class="cta-research__eyebrow"><?php esc_html_e( '7,500+ Vials Delivered', 'simms-research' ); ?></p>
				<h2 class="cta-research__heading"><?php esc_html_e( 'Ready to start your research?', 'simms-research' ); ?></h2>
				<p class="cta-research__subhead"><?php esc_html_e( 'Verified compounds with full COA documentation. Ships same-day, arrives research-ready.', 'simms-research' ); ?></p>
				<div class="cta-research__buttons">
					<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="cta-research__btn cta-research__btn--primary">
						<span><?php esc_html_e( 'Shop all peptides', 'simms-research' ); ?></span>
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M1 7H13M13 7L8 2M13 7L8 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</a>
					<a href="<?php echo esc_url( home_url( '/lab-results/' ) ); ?>" class="cta-research__btn cta-research__btn--secondary">
						<span class="cta-research__btn-icon" aria-hidden="true"><?php echo simms_inline_icon( 'file-text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php esc_html_e( 'View COA library', 'simms-research' ); ?></span>
					</a>
				</div>
				<ul class="cta-research__badges">
					<?php
					$badges = array(
						array( 'shield-check', '≥99% HPLC purity' ),
						array( 'flask-conical', 'Third-party tested' ),
						array( 'file-text', 'COA every batch' ),
						array( 'truck', 'Free 2-day shipping' ),
					);
					foreach ( $badges as $badge ) :
						?>
						<li class="cta-research__badge">
							<span class="cta-research__badge-icon" aria-hidden="true"><?php echo simms_inline_icon( $badge[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><?php echo esc_html( $badge[1] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>

<!-- ============ NEWSLETTER ============ -->
<section class="newsletter-research section section--page-width color-scheme-1">
	<div class="newsletter-research__inner">
		<div class="newsletter-research__copy">
			<p class="newsletter-research__eyebrow"><?php esc_html_e( 'Research updates', 'simms-research' ); ?></p>
			<h2 class="newsletter-research__heading"><?php esc_html_e( 'First to know. First to research.', 'simms-research' ); ?></h2>
			<p class="newsletter-research__body"><?php esc_html_e( 'New compound releases, COA updates, research protocols, and exclusive pricing — delivered to your inbox. Unsubscribe anytime.', 'simms-research' ); ?></p>
		</div>
		<div class="newsletter-research__form-wrap">
			<?php // TODO: wire to newsletter provider (Klaviyo) — provider is an open decision. ?>
			<form class="newsletter-research__form" action="#" method="post">
				<div class="newsletter-research__field">
					<label for="newsletter-email" class="screen-reader-text"><?php esc_html_e( 'Your email address', 'simms-research' ); ?></label>
					<input type="email" id="newsletter-email" name="email" class="newsletter-research__input" placeholder="<?php esc_attr_e( 'Your email address', 'simms-research' ); ?>" autocomplete="email" required>
					<button type="submit" class="newsletter-research__submit">
						<span><?php esc_html_e( 'Subscribe', 'simms-research' ); ?></span>
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M1 7H13M13 7L8 2M13 7L8 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				</div>
				<p class="newsletter-research__reassurance"><?php esc_html_e( 'No spam. Research updates and offers only.', 'simms-research' ); ?></p>
			</form>
		</div>
	</div>
</section>

<?php
get_footer();
