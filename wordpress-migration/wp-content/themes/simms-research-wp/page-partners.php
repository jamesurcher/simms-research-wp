<?php
/**
 * Partners page ported from the Shopify partners-page section.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$stats = array(
	array( '15%', 'Starting commission' ),
	array( '30 Days', 'Payout schedule' ),
	array( '30 Days', 'Attribution window' ),
);

$steps = array(
	array( '01', 'Apply & get approved', 'Fill out a quick application. We review every research partner to keep the program high-quality and well-supported.' ),
	array( '02', 'Get your unique link', 'Once approved, you receive a personal tracking link to share with your research audience.' ),
	array( '03', 'Drive traffic', 'Refer researchers and scientists to Simms Research. Our product pages and COA visibility support conversion.' ),
	array( '04', 'Get paid every 30 days', 'Commissions are attributed through the partner window, with payouts processed every 30 days.' ),
);

$benefits = array(
	array( 'Commission from day one', 'Start earning 15% on every referred sale immediately &mdash; no ramp-up period, no tiers to unlock.' ),
	array( 'Products people trust', 'Promote research compounds backed by product specifications, batch verification, and Certificates of Analysis.' ),
	array( '30-day payouts', 'Payouts are processed on a clear 30-day schedule, so partners know when commissions move.' ),
	array( '30-day attribution', 'Referrals remain attributed for 30 days, giving qualified traffic time to convert.' ),
	array( 'No cap on earnings', 'There is no ceiling. Drive more qualified traffic and your commission scales with your effort.' ),
	array( 'Long cookie window', 'Referral attribution remains active for returning customers within the program cookie window.' ),
);

$faqs = array(
	array( 'What is the commission rate?', 'Research partners start at 15% commission on every referred sale.' ),
	array( 'When do I get paid?', 'Payouts are processed every 30 days.' ),
	array( 'What payment methods are available?', 'Payment details are confirmed during partner approval so payouts can be routed to the correct account.' ),
	array( 'What am I promoting exactly?', 'Simms Research sells research-grade compounds for laboratory research use only, supported by product specifications and Certificates of Analysis.' ),
	array( 'Is there an approval process?', 'Yes. Applications are reviewed before approval to keep the partner program aligned with research-use-only guidelines.' ),
	array( 'Can I promote on social media?', 'Yes, with compliant language. Partners may not make health claims or imply human use.' ),
);
?>
<section
	class="partners-page section section--page-width color-scheme-1"
	style="--partners-page-padding-block-start: 48px; --partners-page-padding-block-end: 120px;"
>
	<div class="partners-page__inner">
		<header class="partners-page__hero">
			<p class="partners-page__eyebrow"><?php esc_html_e( 'Referral Partners', 'simms-research' ); ?></p>
			<h1 class="partners-page__title"><?php esc_html_e( 'Earn With Simms Research', 'simms-research' ); ?></h1>
			<div class="partners-page__subhead">
				<p><?php esc_html_e( 'Refer researchers to a trusted source of premium research compounds and earn 15% commission on every sale, paid on a 30-day schedule.', 'simms-research' ); ?></p>
			</div>
			<div class="partners-page__actions">
				<a class="button partners-page__button" href="<?php echo esc_url( home_url( '/apply/' ) ); ?>">
					<?php esc_html_e( 'Apply Now', 'simms-research' ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
				<a class="partners-page__secondary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
					<?php esc_html_e( 'Questions? Contact', 'simms-research' ); ?>
				</a>
			</div>
			<p class="partners-page__micro"><?php esc_html_e( 'For research use only', 'simms-research' ); ?></p>
		</header>

		<div class="partners-page__stats" aria-label="<?php esc_attr_e( 'Partner program highlights', 'simms-research' ); ?>">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="partners-page__stat">
					<p><?php echo esc_html( $stat[0] ); ?></p>
					<span><?php echo esc_html( $stat[1] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<section class="partners-page__section" aria-labelledby="PartnersHowItWorks">
			<p class="partners-page__eyebrow"><?php esc_html_e( 'Simple Process', 'simms-research' ); ?></p>
			<h2 id="PartnersHowItWorks" class="partners-page__section-title"><?php esc_html_e( 'How It Works', 'simms-research' ); ?></h2>
			<div class="partners-page__steps">
				<?php foreach ( $steps as $step ) : ?>
					<article class="partners-page__step">
						<p class="partners-page__step-number"><?php echo esc_html( $step[0] ); ?></p>
						<h3><?php echo esc_html( $step[1] ); ?></h3>
						<p><?php echo esc_html( $step[2] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="partners-page__section" aria-labelledby="PartnersBenefits">
			<p class="partners-page__eyebrow"><?php esc_html_e( 'Why Join', 'simms-research' ); ?></p>
			<h2 id="PartnersBenefits" class="partners-page__section-title"><?php esc_html_e( 'Built For Serious Research Partners', 'simms-research' ); ?></h2>
			<div class="partners-page__benefits">
				<?php foreach ( $benefits as $benefit ) : ?>
					<article class="partners-page__card">
						<h3><?php echo esc_html( $benefit[0] ); ?></h3>
						<p><?php echo wp_kses_post( $benefit[1] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="partners-page__section partners-page__section--faq" aria-labelledby="PartnersFaq">
			<p class="partners-page__eyebrow"><?php esc_html_e( 'Questions', 'simms-research' ); ?></p>
			<h2 id="PartnersFaq" class="partners-page__section-title"><?php esc_html_e( 'Common Questions', 'simms-research' ); ?></h2>
			<div class="partners-page__faq">
				<?php foreach ( $faqs as $faq ) : ?>
					<details class="partners-page__faq-item">
						<summary>
							<span aria-hidden="true">&rsaquo;</span>
							<?php echo esc_html( $faq[0] ); ?>
						</summary>
						<div class="partners-page__faq-answer">
							<?php echo esc_html( $faq[1] ); ?>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="partners-page__final-cta" aria-label="<?php esc_attr_e( 'Apply to the research partner program', 'simms-research' ); ?>">
			<p class="partners-page__eyebrow"><?php esc_html_e( 'Ready to earn', 'simms-research' ); ?></p>
			<h2><?php esc_html_e( 'Start earning 15% from your first sale', 'simms-research' ); ?></h2>
			<div class="partners-page__cta-copy">
				<p><?php esc_html_e( 'Apply to become a Simms Research partner, get your tracking link, and start earning commissions on every research compound sale you refer, paid on a 30-day schedule.', 'simms-research' ); ?></p>
			</div>
			<a class="button partners-page__button" href="<?php echo esc_url( home_url( '/apply/' ) ); ?>">
				<?php esc_html_e( 'Apply Now', 'simms-research' ); ?>
				<span aria-hidden="true">&rarr;</span>
			</a>
		</section>
	</div>
</section>
<?php
get_footer();
