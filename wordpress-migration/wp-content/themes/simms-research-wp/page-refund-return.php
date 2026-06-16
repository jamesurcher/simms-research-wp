<?php
/**
 * Refund and return policy page ported from the Shopify generic page content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="simms-policy-page main-page main-page--refund-return color-scheme-1">
	<div class="section-content-wrapper">
		<div class="shopify-block rte">
			<rte-formatter>
				<div class="prose prose-invert max-w-none space-y-8">
					<div class="border border-white/10 rounded-sm p-6 bg-white/[0.02]">
						<div class="flex items-start gap-4"></div>
					</div>
				</div>
				<div class="text-center mb-16">
					<h1 class="font-display text-4xl md:text-5xl font-bold tracking-tight mb-6">REFUND &amp; RETURN POLICY</h1>
				</div>
				<div class="prose prose-invert max-w-none space-y-8">
					<div class="border border-white/10 rounded-sm p-6 bg-white/[0.02]">
						<p class="text-white/50 leading-relaxed">At Simms Research, the integrity, safety, and purity of our products are our top priorities. As all of our compounds are intended strictly for research use only and are highly sensitive in nature, we maintain a strict no refund and no return policy.</p>
					</div>
					<section>
						<h2 class="font-display text-xl font-bold tracking-tight mb-4">All Sales Are Final</h2>
						<p class="text-white/50 leading-relaxed">We do not offer refunds or accept returns for any reason, including ordering errors, change of mind, or misuse. This policy helps us maintain quality control and ensures that all customers receive products that meet our rigorous standards.</p>
					</section>
					<section>
						<h2 class="font-display text-xl font-bold tracking-tight mb-4">Damaged or Defective Orders</h2>
						<p class="text-white/50 leading-relaxed mb-4">If your order arrives damaged, defective, or you receive the wrong item, you must notify us within 48 hours of delivery at support@simmsresearch.com. Please include a detailed description and clear photos of the issue. Our support team will review your case and determine eligibility for a replacement.</p>
					</section>
					<section>
						<h2 class="font-display text-xl font-bold tracking-tight mb-4">Refund Eligibility</h2>
						<p class="text-white/50 leading-relaxed mb-3">Refunds will only be considered in rare cases where:</p>
						<ul class="space-y-2 text-white/50 text-sm">
							<li class="flex items-start gap-3"><span class="text-white/20 mt-1">-</span><span>The order was never delivered due to an error on our part</span></li>
							<li class="flex items-start gap-3"><span class="text-white/20 mt-1">-</span><span>A replacement is not possible and the issue was reported within the required timeframe</span></li>
						</ul>
					</section>
					<section>
						<p class="text-white/50 leading-relaxed">We do not refund or replace items that have been opened, used, or returned without prior authorization.</p>
					</section>
					<div class="border border-white/10 rounded-sm p-6 bg-white/[0.02]">
						<div class="flex items-start gap-4">
							<div>
								<h3 class="text-sm font-bold uppercase tracking-[0.1em] mb-2">Need Assistance?</h3>
								<p class="text-xs text-white/40 leading-relaxed">For any concerns or assistance regarding your order, please contact our support team at<span>&nbsp;</span><a class="text-white/60 hover:text-white underline" href="mailto:support@simmsresearch.com">support@simmsresearch.com</a></p>
							</div>
						</div>
					</div>
				</div>
			</rte-formatter>
		</div>
	</div>
</section>
<?php
get_footer();
