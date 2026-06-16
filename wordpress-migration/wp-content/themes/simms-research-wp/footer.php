<?php
/**
 * Site footer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer class="simms-footer">
	<div class="simms-footer__inner">
		<div class="simms-footer__brand">
			<span class="simms-logo simms-logo--inverse">simms</span>
			<span class="simms-logo__sub">research</span>
			<p>Laboratory-grade research compounds with 99%+ purity, trusted by researchers worldwide.</p>
		</div>
		<nav aria-label="<?php esc_attr_e( 'Footer shop menu', 'simms-research' ); ?>">
			<h2><?php esc_html_e( 'Shop', 'simms-research' ); ?></h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer_shop',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'simms-footer__menu',
				)
			);
			?>
		</nav>
		<nav aria-label="<?php esc_attr_e( 'Footer legal menu', 'simms-research' ); ?>">
			<h2><?php esc_html_e( 'Legal', 'simms-research' ); ?></h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer_legal',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'simms-footer__menu',
				)
			);
			?>
		</nav>
		<div>
			<h2><?php esc_html_e( 'Contact', 'simms-research' ); ?></h2>
			<p><a href="mailto:support@simmsresearch.com">support@simmsresearch.com</a></p>
			<p>5908 Breckenridge Pkwy, Tampa, FL 33610</p>
		</div>
		<p class="simms-footer__disclaimer">
			The statements on this website have not been evaluated by the U.S. Food and Drug Administration. The products and information provided by Simms Research are not intended to diagnose, treat, cure, or prevent any disease. All products are sold for research and laboratory use only. Not for human consumption.
		</p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

