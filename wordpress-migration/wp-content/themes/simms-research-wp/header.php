<?php
/**
 * Site header.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'simms-research' ); ?></a>
<header class="simms-header">
	<div class="simms-header__inner">
		<a class="simms-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Simms Research home', 'simms-research' ); ?>">
			<span class="simms-logo">simms</span>
			<span class="simms-logo__sub">research</span>
		</a>
		<nav class="simms-header__nav" aria-label="<?php esc_attr_e( 'Primary menu', 'simms-research' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'simms-menu',
				)
			);
			?>
		</nav>
		<div class="simms-header__actions">
			<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url() ); ?>" aria-label="<?php esc_attr_e( 'Account', 'simms-research' ); ?>">
				<?php echo simms_inline_icon( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
			<a href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'simms-research' ); ?>">
				<?php echo simms_inline_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
	</div>
</header>
<main id="main" class="site-main">
