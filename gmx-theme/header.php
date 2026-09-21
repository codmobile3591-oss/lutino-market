<?php
/**
 * هدر قالب.
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
					<img class="brand-mark-img" src="<?php echo esc_url( GMX_THEME_URL . '/assets/img/logo.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="40" height="40" />
					<span class="brand-text"><?php bloginfo( 'name' ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<nav class="main-nav" aria-label="<?php esc_attr_e( 'منوی اصلی', 'gmx-theme' ); ?>">
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

		<div class="header-actions">
			<form class="header-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="search" name="s" placeholder="<?php esc_attr_e( 'جستجوی آیتم، جم، اکانت...', 'gmx-theme' ); ?>" value="<?php echo get_search_query(); ?>" />
				<button type="submit" aria-label="<?php esc_attr_e( 'جستجو', 'gmx-theme' ); ?>">🔍</button>
			</form>

			<?php if ( is_user_logged_in() ) : ?>
				<a class="header-btn header-account" href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">
					<span class="header-btn-icon">👤</span>
					<span class="header-btn-text"><?php esc_html_e( 'داشبورد', 'gmx-theme' ); ?></span>
					<?php $unread = gmx_theme_unread_chats(); ?>
					<?php if ( $unread ) : ?><i class="header-dot"><?php echo esc_html( gmx_fa_num( $unread ) ); ?></i><?php endif; ?>
				</a>
			<?php else : ?>
				<a class="header-btn header-btn-primary" href="<?php echo esc_url( wp_login_url( home_url( '/my-account/' ) ) ); ?>">
					<span class="header-btn-icon">🎮</span>
					<span class="header-btn-text"><?php esc_html_e( 'ورود / ثبت‌نام', 'gmx-theme' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<main class="site-main">
