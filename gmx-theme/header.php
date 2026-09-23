<?php
/**
 * هدر قالب لوتینو — نسخه مارکت‌پلیس.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="container header-inner">
		<div class="brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="brand-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img class="brand-mark-img" src="<?php echo esc_url( GMX_THEME_URL . '/assets/img/logo.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="44" height="44" />
					<span class="brand-text-wrap">
						<span class="brand-text"><?php bloginfo( 'name' ); ?></span>
						<span class="brand-sub">LUTINO</span>
					</span>
				</a>
			<?php endif; ?>
		</div>

		<button class="nav-toggle" id="gmx-nav-toggle" aria-label="<?php esc_attr_e( 'باز و بسته کردن منو', 'gmx-theme' ); ?>" aria-expanded="false" aria-controls="gmx-mobile-nav">
			<span></span><span></span><span></span>
		</button>

		<nav class="main-nav nav-pills" aria-label="<?php esc_attr_e( 'منوی اصلی', 'gmx-theme' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'nav-list',
						'depth'          => 2,
					)
				);
			}
			?>
		</nav>

		<div class="mobile-nav" id="gmx-mobile-nav" hidden>
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'mobile-nav-list',
						'depth'          => 1,
					)
				);
			}
			?>
			<?php if ( ! is_user_logged_in() ) : ?>
				<a class="mobile-nav-cta" href="<?php echo esc_url( wp_login_url( home_url( '/my-account/' ) ) ); ?>">🔐 <?php esc_html_e( 'ورود / ثبت نام', 'gmx-theme' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="header-actions">
			<form class="header-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="search" name="s" placeholder="<?php esc_attr_e( 'جستجوی آگهی...', 'gmx-theme' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" />
				<button type="submit" aria-label="<?php esc_attr_e( 'جستجو', 'gmx-theme' ); ?>">🔍</button>
			</form>
			<?php if ( is_user_logged_in() ) : ?>
				<a class="header-pill header-cart" href="<?php echo esc_url( home_url( '/my-account/orders/' ) ); ?>">
					<span class="hp-ic">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
					</span>
					<span class="hp-txt"><?php esc_html_e( 'سبد خرید', 'gmx-theme' ); ?></span>
					<i class="header-badge"><?php echo esc_html( gmx_fa_num( 0 ) ); ?></i>
				</a>
				<a class="header-pill header-account" href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">
					<span class="hp-ic">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</span>
					<span class="hp-txt"><?php echo esc_html( gmx_display_name( get_current_user_id() ) ); ?></span>
					<?php $unread = gmx_theme_unread_chats(); ?>
					<?php if ( $unread ) : ?><i class="header-dot"><?php echo esc_html( gmx_fa_num( $unread ) ); ?></i><?php endif; ?>
				</a>
			<?php else : ?>
				<a class="header-pill header-account" href="<?php echo esc_url( wp_login_url( home_url( '/my-account/' ) ) ); ?>">
					<span class="hp-ic">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</span>
					<span class="hp-txt"><?php esc_html_e( 'ورود / ثبت نام', 'gmx-theme' ); ?></span>
				</a>
				<a class="header-pill header-cart" href="<?php echo esc_url( home_url( '/my-account/orders/' ) ); ?>">
					<span class="hp-ic">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
					</span>
					<span class="hp-txt"><?php esc_html_e( 'سبد خرید', 'gmx-theme' ); ?></span>
					<i class="header-badge"><?php echo esc_html( gmx_fa_num( 0 ) ); ?></i>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<main class="site-main">
