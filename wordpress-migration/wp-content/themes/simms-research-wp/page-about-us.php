<?php
/**
 * About / Quality page ported from the Shopify about-quality section.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$differentiators = array(
	array(
		'icon'    => 'map-pin',
		'eyebrow' => 'Sourcing',
		'title'   => 'American API only',
		'body'    => 'We source exclusively from US-licensed manufacturers with documented synthesis chains.',
	),
	array(
		'icon'    => 'flask-conical',
		'eyebrow' => 'Testing',
		'title'   => 'Independent third-party',
		'body'    => 'Every batch is submitted to an independent ISO-certified laboratory before release.',
	),
	array(
		'icon'    => 'qr-code',
		'eyebrow' => 'Labeling',
		'title'   => 'Batch-level COA',
		'body'    => 'Each vial is labeled with its batch ID. The matching COA is published on the product page.',
	),
	array(
		'icon'    => 'package',
		'eyebrow' => 'Dispatch',
		'title'   => 'Same-week fulfillment',
		'body'    => 'Orders placed on in-stock items ship within 2 business days from our Tampa facility.',
	),
);

$steps = array(
	array( 'Raw material sourcing', 'Peptides sourced from US-licensed manufacturers with documented synthesis chains.' ),
	array( 'In-house identity check', 'Each incoming batch is logged and visually inspected before entering inventory.' ),
	array( 'Third-party lab submission', 'Samples submitted to an independent ISO-certified lab for HPLC purity analysis.' ),
	array( 'COA review and approval', 'Results reviewed against our internal acceptance criteria. Batches below threshold are rejected.' ),
	array( 'Batch labeling', 'Approved vials receive a unique batch ID traceable to the specific COA.' ),
	array( 'Fulfillment + COA published', 'Vials ship same week. The COA is published to the product page and indexed on the lab-results page.' ),
);

$commitments = array(
	array( 'High quality blends', 'Compounds delivered at a stated concentration.' ),
	array( 'No fillers or excipients', "Lyophilized powder only. What the label says is what's in the vial." ),
	array( 'Published batch results', "Every batch we sell has a publicly accessible COA. If we can't show the test, we don't ship the batch." ),
	array( 'US-based operations', 'Sourced, tested, labeled, and shipped from Tampa, FL.' ),
);
?>
<section
	class="about-quality section section--page-width color-scheme-1"
	style="--about-quality-padding-block-start: 48px; --about-quality-padding-block-end: 120px;"
>
	<div class="about-quality__inner">
		<header class="about-quality__hero">
			<p class="about-quality__eyebrow"><?php echo esc_html( 'Simms Research' ); ?> &middot; <?php esc_html_e( 'Tampa, FL', 'simms-research' ); ?></p>
			<h1 class="about-quality__heading"><?php esc_html_e( 'Your source for research-grade peptides.', 'simms-research' ); ?></h1>
			<p class="about-quality__subhead"><?php esc_html_e( 'Every vial we ship is third-party tested, batch-labeled, and documented', 'simms-research' ); ?> &mdash; <?php esc_html_e( 'before it leaves our facility.', 'simms-research' ); ?></p>
		</header>

		<div class="about-quality__diffs">
			<?php foreach ( $differentiators as $item ) : ?>
				<div class="about-quality__diff">
					<span class="about-quality__diff-icon" aria-hidden="true"><?php echo simms_inline_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<p class="about-quality__diff-eyebrow"><?php echo esc_html( $item['eyebrow'] ); ?></p>
					<p class="about-quality__diff-title"><?php echo esc_html( $item['title'] ); ?></p>
					<p class="about-quality__diff-body"><?php echo esc_html( $item['body'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="about-quality__process">
			<div class="about-quality__section-header">
				<p class="about-quality__eyebrow"><?php esc_html_e( 'How We Verify Every Batch', 'simms-research' ); ?></p>
				<p class="about-quality__section-heading"><?php esc_html_e( 'From synthesis to shipment.', 'simms-research' ); ?></p>
			</div>
			<ol class="about-quality__steps">
				<?php foreach ( $steps as $index => $step ) : ?>
					<li class="about-quality__step">
						<span class="about-quality__step-num"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
						<div class="about-quality__step-body">
							<p class="about-quality__step-title"><?php echo esc_html( $step[0] ); ?></p>
							<p class="about-quality__step-desc"><?php echo esc_html( $step[1] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<div class="about-quality__commitments">
			<div class="about-quality__section-header">
				<p class="about-quality__eyebrow"><?php esc_html_e( 'Our Commitments', 'simms-research' ); ?></p>
				<p class="about-quality__section-heading"><?php esc_html_e( 'What you can always expect.', 'simms-research' ); ?></p>
			</div>
			<div class="about-quality__commitment-grid">
				<?php foreach ( $commitments as $commitment ) : ?>
					<div class="about-quality__commitment">
						<svg class="about-quality__check" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<polyline points="3,8 6.5,11.5 13,4.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<div>
							<p class="about-quality__commitment-title"><?php echo esc_html( $commitment[0] ); ?></p>
							<p class="about-quality__commitment-body"><?php echo esc_html( $commitment[1] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="about-quality__cta">
			<p class="about-quality__eyebrow"><?php esc_html_e( 'See The Results', 'simms-research' ); ?></p>
			<p class="about-quality__cta-heading"><?php esc_html_e( 'Every batch. Every test.', 'simms-research' ); ?></p>
			<div class="about-quality__cta-buttons">
				<a href="<?php echo esc_url( home_url( '/lab-results/' ) ); ?>" class="about-quality__btn about-quality__btn--primary"><?php esc_html_e( 'View Lab Results', 'simms-research' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="about-quality__btn about-quality__btn--secondary"><?php esc_html_e( 'Shop the Catalog', 'simms-research' ); ?></a>
			</div>
		</div>
	</div>
</section>
<?php
get_footer();
