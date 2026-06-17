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

<?php
$announcements = array(
	__( 'Free Shipping On Orders $200+', 'simms-research' ),
	__( 'US-Based | Third-Party Tested', 'simms-research' ),
	__( 'For Research Use Only', 'simms-research' ),
);
?>
<div class="announcement-bar" data-simms-announcement data-interval="5000">
	<div class="announcement-bar__track" aria-live="polite">
		<?php foreach ( $announcements as $i => $message ) : ?>
			<span class="announcement-bar__item<?php echo 0 === $i ? ' is-active' : ''; ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>><?php echo esc_html( $message ); ?></span>
		<?php endforeach; ?>
	</div>
</div>

<header class="site-header">
	<div class="site-header__inner">
		<button class="site-header__toggle" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'simms-research' ); ?>" aria-expanded="false">
			<?php echo simms_inline_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<a class="site-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Simms Research home', 'simms-research' ); ?>">
			<img src="<?php echo esc_url( SIMMS_THEME_URI . '/assets/images/simms-logo.png' ); ?>" alt="Simms Research" width="800" height="200">
		</a>
		<nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'simms-research' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '<ul>%3$s</ul>',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</nav>
		<div class="site-header__actions">
			<a href="<?php echo esc_url( home_url( '/search/' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'simms-research' ); ?>"><?php echo simms_inline_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url() ); ?>" aria-label="<?php esc_attr_e( 'Account', 'simms-research' ); ?>"><?php echo simms_inline_icon( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<a class="site-header__cart" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'simms-research' ); ?>" aria-haspopup="dialog" aria-controls="simms-cart-drawer" data-simms-cart-open>
				<?php echo simms_inline_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo simms_cart_count_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
	</div>
</header>
<main id="main" class="site-main">
