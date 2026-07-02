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
// Shopify-style focused checkout chrome: drop the announcement bar + nav,
// leaving a centered logo and the cart only.
$simms_is_checkout = function_exists( 'is_checkout' ) && is_checkout();
// Reuse the same focused chrome on the logged-out account gate: centered logo,
// no announcement bar, nav or cart (matches the Shopify-style sign-in screen).
$simms_is_auth     = function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in();
$simms_focused     = $simms_is_checkout || $simms_is_auth;
?>

<?php if ( ! $simms_focused ) : ?>
	<?php
	$announcements = array(
		__( 'FREE SHIPPING ON ORDERS $200+', 'simms-research' ),
		__( 'US-Based | Third-Party Tested', 'simms-research' ),
		__( 'FOR RESEARCH USE ONLY', 'simms-research' ),
	);
	?>
	<div class="announcement-bar" data-simms-announcement data-interval="5000">
		<div class="announcement-bar__track" aria-live="polite">
			<?php foreach ( $announcements as $i => $message ) : ?>
				<span class="announcement-bar__item<?php echo 0 === $i ? ' is-active' : ''; ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>><?php echo esc_html( $message ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>

<header class="site-header<?php echo is_front_page() ? ' site-header--home' : ''; ?><?php echo $simms_is_checkout ? ' site-header--checkout' : ''; ?><?php echo $simms_is_auth ? ' site-header--auth' : ''; ?>">
	<div class="site-header__inner">
		<?php if ( ! $simms_focused ) : ?>
			<button class="site-header__toggle" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'simms-research' ); ?>" aria-expanded="false" aria-controls="simms-mobile-nav" data-simms-nav-toggle>
				<?php echo simms_inline_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		<?php endif; ?>
		<a class="site-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Simms Research home', 'simms-research' ); ?>">
			<img class="site-header__logo site-header__logo--default" src="<?php echo esc_url( SIMMS_THEME_URI . '/assets/images/simms-logo.png' ); ?>" alt="Simms Research" width="800" height="200">
			<img class="site-header__logo site-header__logo--inverse" src="<?php echo esc_url( SIMMS_THEME_URI . '/assets/images/simms-logo-inverse.png' ); ?>" alt="" width="800" height="200" aria-hidden="true">
		</a>
		<?php if ( ! $simms_focused ) : ?>
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
		<?php endif; ?>
		<?php if ( ! $simms_focused ) : ?>
			<div class="site-header__actions">
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url() ); ?>" aria-label="<?php esc_attr_e( 'Account', 'simms-research' ); ?>"><?php echo simms_inline_icon( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<a class="site-header__cart" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'simms-research' ); ?>" aria-haspopup="dialog" aria-controls="simms-cart-drawer" data-simms-cart-open>
					<?php echo simms_inline_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo simms_cart_count_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</header>

<?php if ( ! $simms_focused ) : ?>
<?php
$simms_drawer_products = new WP_Query(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_price',
				'value'   => '',
				'compare' => '!=',
			),
		),
	)
);
?>
<div id="simms-mobile-nav" class="mobile-nav">
	<div class="mobile-nav__backdrop" data-simms-nav-close></div>
	<div class="mobile-nav__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'simms-research' ); ?>">
		<button class="mobile-nav__close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'simms-research' ); ?>" data-simms-nav-close>
			<?php echo simms_inline_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<nav class="mobile-nav__menu" aria-label="<?php esc_attr_e( 'Primary', 'simms-research' ); ?>">
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
		<?php if ( $simms_drawer_products->have_posts() ) : ?>
			<div class="mobile-nav__featured">
				<ul class="mobile-nav__featured-list">
					<?php
					while ( $simms_drawer_products->have_posts() ) :
						$simms_drawer_products->the_post();
						$simms_dp = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
						?>
						<li class="mobile-nav__featured-item">
							<a class="mobile-nav__featured-link" href="<?php the_permalink(); ?>">
								<span class="mobile-nav__featured-thumb">
									<?php echo get_the_post_thumbnail( get_the_ID(), 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="mobile-nav__featured-title"><?php the_title(); ?></span>
								<?php if ( $simms_dp ) : ?>
									<span class="mobile-nav__featured-price"><?php echo wp_kses_post( simms_product_card_price_html( $simms_dp ) ); ?></span>
								<?php endif; ?>
							</a>
						</li>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<main id="main" class="site-main">
