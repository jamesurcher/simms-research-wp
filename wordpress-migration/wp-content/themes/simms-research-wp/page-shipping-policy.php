<?php
/**
 * Shipping policy page ported from the Shopify generic page content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="simms-policy-page main-page main-page--shipping-policy color-scheme-1">
	<div class="section-content-wrapper">
		<div class="shopify-block rte">
			<rte-formatter>
				<div class="text-center mb-16">
					<h1 class="font-display text-4xl md:text-5xl font-bold tracking-tight mb-6">SHIPPING POLICY</h1>
				</div>
				<div class="prose prose-invert max-w-none space-y-8">
					<div class="border border-white/10 rounded-sm p-6 bg-white/[0.02]">
						<p class="text-white/50 leading-relaxed">At Simms Research, we strive to ensure your order is processed quickly and delivered reliably.</p>
					</div>
					<section>
						<div class="flex items-center gap-3 mb-4">
							<h2 class="font-display text-xl font-bold tracking-tight">Order Processing</h2>
						</div>
						<p class="text-white/50 leading-relaxed">All orders are shipped the same or next business day, excluding Sundays and public holidays. Orders placed before 1PM PST will ship the same day. Once your order ships, you will receive a confirmation email with tracking details.</p>
					</section>
					<section>
						<div class="flex items-center gap-3 mb-4">
							<h2 class="font-display text-xl font-bold tracking-tight">Shipping Options &amp; Estimated Delivery</h2>
						</div>
						<p class="text-white/50 leading-relaxed mb-4">We offer both Standard and Expedited shipping options at checkout. We ship to all 50 US states and internationally.</p>
						<div class="border border-white/10 rounded-sm p-5 bg-white/[0.02]">
							<h3 class="text-sm font-bold uppercase tracking-[0.1em] mb-3">Estimated Delivery Times</h3>
							<div class="flex items-center gap-3 text-white/50 text-sm">
								<span>Domestic Shipping: 2-4 business days (after dispatch)</span>
							</div>
						</div>
					</section>
					<section>
						<div class="flex items-center gap-3 mb-4">
							<h2 class="font-display text-xl font-bold tracking-tight">Import Duties &amp; Customs Fees</h2>
						</div>
						<p class="text-white/50 leading-relaxed mb-3">For international orders, import duties, customs fees, VAT, and any other charges imposed by your country's customs authority are<span>&nbsp;</span><strong class="text-white/70">the sole responsibility of the buyer</strong>. These fees are not included in our product prices or shipping charges and are not covered by Simms Research.</p>
						<p class="text-white/50 leading-relaxed mb-3">Customs policies vary widely by country. We recommend checking with your local customs office before placing an order to understand what fees may apply.</p>
						<div class="border border-white/[0.08] rounded-sm p-5 bg-amber-950/20">
							<p class="text-amber-400/80 text-sm leading-relaxed"><strong>Please note:</strong><span>&nbsp;</span>Simms Research is not responsible for any import fees, customs duties, or delays caused by customs clearance. Refusals to pay customs fees do not qualify for a refund or return.</p>
						</div>
					</section>
					<section>
						<h2 class="font-display text-xl font-bold tracking-tight mb-4">Shipping Rates</h2>
						<p class="text-white/50 leading-relaxed mb-4">Shipping costs are calculated at checkout based on your location and the selected shipping method. Free shipping is available on all orders over $200.</p>
					</section>
					<section>
						<div class="flex items-center gap-3 mb-4">
							<h2 class="font-display text-xl font-bold tracking-tight">Shipping Delays</h2>
						</div>
						<p class="text-white/50 leading-relaxed mb-3">Please note, Simms Research is not responsible for delays caused by:</p>
						<ul class="space-y-2 text-white/50 text-sm">
							<li class="flex items-start gap-3"><span class="text-white/20 mt-1">-</span><span>Customs inspections</span></li>
							<li class="flex items-start gap-3"><span class="text-white/20 mt-1">-</span><span>Courier issues or disruptions</span></li>
							<li class="flex items-start gap-3"><span class="text-white/20 mt-1">-</span><span>Weather or natural disasters</span></li>
						</ul>
						<p class="text-white/50 leading-relaxed mt-4">Once an order has been shipped, responsibility for delivery lies with the carrier.</p>
					</section>
				</div>
			</rte-formatter>
		</div>
	</div>
</section>
<?php
get_footer();
