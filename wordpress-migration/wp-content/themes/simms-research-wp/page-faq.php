<?php
/**
 * FAQ page — 1:1 from the live /pages/faq (6 categories, accordions).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$faq_categories = array(
	array(
		'icon'  => 'file-text',
		'title' => 'General',
		'qa'    => array(
			array( 'What are research compounds?', 'Research compounds are materials supplied for laboratory, analytical, or research use only. They are not sold for human or veterinary use.' ),
			array( 'Who can order from Simms Research?', 'Customers are responsible for understanding and following all laws, regulations, and institutional requirements that apply to their research.' ),
			array( 'Are your products intended for human use?', 'No. Products sold by Simms Research are strictly for research use only and are not intended for human consumption, medical use, or veterinary use.' ),
		),
	),
	array(
		'icon'  => 'flask-conical',
		'title' => 'Quality & Testing',
		'qa'    => array(
			array( 'Are your compounds tested for purity?', 'Products are supported by batch-level testing where available, including Certificates of Analysis and product specification data.' ),
			array( 'Where can I find Certificates of Analysis?', 'Certificates of Analysis are displayed on product pages when batch records are available.' ),
			array( 'What purity level can I expect?', 'Purity details are listed on the product page and reflected in available batch verification data.' ),
		),
	),
	array(
		'icon'  => 'truck',
		'title' => 'Shipping & Delivery',
		'qa'    => array(
			array( 'When do orders ship?', 'Orders are processed during business days. Shipping timelines depend on order volume, carrier service, and cutoff timing.' ),
			array( 'Do you offer free shipping?', 'Free shipping eligibility is shown in the site announcement bar and at checkout when applicable.' ),
			array( 'Where do you ship from?', 'Orders are fulfilled from the United States.' ),
		),
	),
	array(
		'icon'  => 'clipboard-list',
		'title' => 'Orders & Payment',
		'qa'    => array(
			array( 'How do I check my order status?', 'Use your order confirmation email or contact support with your order number for help locating the latest status.' ),
			array( 'Can I change or cancel an order?', 'Contact support as soon as possible. Once an order is processed or handed to the carrier, changes may not be available.' ),
			array( 'What payment methods are accepted?', 'Available payment methods are shown at checkout.' ),
		),
	),
	array(
		'icon'  => 'package',
		'title' => 'Returns & Issues',
		'qa'    => array(
			array( 'What if my order arrives damaged?', 'Contact support with your order number and photos of the package and product so the issue can be reviewed.' ),
			array( 'What if I received the wrong item?', 'Contact support with your order number and a photo of the item received. We will review the order and resolve the issue.' ),
			array( 'Do you accept returns?', 'Return eligibility depends on the product condition, order status, and applicable policy terms.' ),
		),
	),
	array(
		'icon'  => 'shield-check',
		'title' => 'Research Use & Compliance',
		'qa'    => array(
			array( 'Are products for research use only?', 'Yes. Simms Research products are sold for research and laboratory use only.' ),
			array( 'Can I use these products personally?', 'No. Products are not intended for personal use, human consumption, medical use, or veterinary use.' ),
			array( 'Do you provide usage or dosing guidance?', 'No. Simms Research does not provide dosing, administration, or personal-use guidance.' ),
		),
	),
);
?>
<div class="faq-page color-scheme-1">
	<header class="faq-page__hero">
		<p class="faq-page__eyebrow"><?php esc_html_e( 'Support', 'simms-research' ); ?></p>
		<h1 class="faq-page__heading"><?php esc_html_e( 'Frequently Asked Questions', 'simms-research' ); ?></h1>
	</header>

	<div class="faq-page__groups">
		<?php foreach ( $faq_categories as $cat ) : ?>
			<section class="faq-page__group">
				<h2 class="faq-page__group-title">
					<span class="faq-page__group-icon" aria-hidden="true"><?php echo simms_inline_icon( $cat['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php echo esc_html( $cat['title'] ); ?>
				</h2>
				<ul class="faq-accordion__list">
					<?php foreach ( $cat['qa'] as $qa ) : ?>
						<li class="faq-accordion__item">
							<details class="faq-accordion__details">
								<summary class="faq-accordion__summary">
									<span class="faq-accordion__question"><?php echo esc_html( $qa[0] ); ?></span>
									<span class="faq-accordion__chevron" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 5L7 9L11 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
								</summary>
								<div class="faq-accordion__answer"><p><?php echo esc_html( $qa[1] ); ?></p></div>
							</details>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	</div>
</div>
<?php
get_footer();
