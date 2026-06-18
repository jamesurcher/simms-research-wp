<?php
/**
 * Site footer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<?php if ( ! ( function_exists( 'is_checkout' ) && is_checkout() ) ) : ?>
<footer class="site-footer">
	<div class="site-footer__top">
		<div class="site-footer__brand">
			<img src="<?php echo esc_url( SIMMS_THEME_URI . '/assets/images/simms-logo-inverse.png' ); ?>" alt="Simms Research" width="800" height="200">
			<p class="site-footer__tagline"><?php esc_html_e( 'Premium research-grade peptides. US-based compounds with 99%+ purity, third-party tested and trusted by researchers worldwide.', 'simms-research' ); ?></p>
			<div class="site-footer__ctas">
				<a class="site-footer__cta site-footer__cta--primary" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop all peptides', 'simms-research' ); ?></a>
				<a class="site-footer__cta site-footer__cta--ghost" href="<?php echo esc_url( home_url( '/lab-results/' ) ); ?>"><?php esc_html_e( 'View COA library', 'simms-research' ); ?></a>
			</div>
			<div class="site-footer__social">
				<a href="https://www.facebook.com/simmsresearch" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Simms Research on Facebook', 'simms-research' ); ?>">
					<?php echo simms_inline_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a href="https://www.instagram.com/simmsresearch/" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Simms Research on Instagram', 'simms-research' ); ?>">
					<?php echo simms_inline_icon( 'instagram' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a href="https://www.tiktok.com/@simmsresearch" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Simms Research on TikTok', 'simms-research' ); ?>">
					<?php echo simms_inline_icon( 'tiktok' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>
		</div>

		<div class="site-footer__col">
			<h2><?php esc_html_e( 'Explore', 'simms-research' ); ?></h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer_shop',
					'container'      => false,
					'items_wrap'     => '<ul class="site-footer__menu">%3$s</ul>',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</div>

		<div class="site-footer__col">
			<h2><?php esc_html_e( 'Legal', 'simms-research' ); ?></h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer_legal',
					'container'      => false,
					'items_wrap'     => '<ul class="site-footer__menu">%3$s</ul>',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</div>

		<div class="site-footer__col site-footer__contact">
			<h2><?php esc_html_e( 'Contact', 'simms-research' ); ?></h2>
			<p><a href="mailto:support@simmsresearch.com">support@simmsresearch.com</a></p>
			<p>5908 Breckenridge Pkwy,<br>Tampa, FL 33610</p>
		</div>
	</div>

	<div class="site-footer__bottom">
		<div class="site-footer__bottom-inner">
			<p class="site-footer__disclaimer"><?php esc_html_e( 'The statements on this website have not been evaluated by the U.S. Food and Drug Administration. The products and information provided by Simms Research are not intended to diagnose, treat, cure, or prevent any disease. All products are sold for research and laboratory use only. Not for human consumption.', 'simms-research' ); ?></p>
			<p class="site-footer__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Simms Research. <?php esc_html_e( 'All rights reserved.', 'simms-research' ); ?></p>
		</div>
	</div>
</footer>
<?php endif; ?>
<?php get_template_part( 'template-parts/cart-drawer' ); ?>
<?php wp_footer(); ?>
</body>
</html>
