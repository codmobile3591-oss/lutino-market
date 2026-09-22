<?php
/**
 * فوتر قالب لوتینو — مطابق طرح مرجع.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>

<footer class="site-footer footer-v2">
	<div class="container footer-main">
		<div class="footer-brand">
			<div class="footer-brand-row">
				<strong class="footer-brand-name"><?php bloginfo( 'name' ); ?></strong>
				<img class="brand-mark-img" src="<?php echo esc_url( GMX_THEME_URL . '/assets/img/logo.svg' ); ?>" alt="" width="36" height="36" />
			</div>
			<span class="brand-sub">LUTINO</span>
			<p class="footer-desc"><?php esc_html_e( 'فروشگاه تخصصی اکانت‌های گیمینگ', 'gmx-theme' ); ?></p>
		</div>

		<nav class="footer-nav" aria-label="<?php esc_attr_e( 'منوی فوتر', 'gmx-theme' ); ?>">
			<?php
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'footer-links',
						'depth'          => 1,
					)
				);
			} else {
				?>
				<ul class="footer-links">
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'خانه', 'gmx-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( get_post_type_archive_link( 'gmx_product' ) ); ?>"><?php esc_html_e( 'محصولات', 'gmx-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/my-account/seller/' ) ); ?>"><?php esc_html_e( 'خدمات', 'gmx-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'درباره ما', 'gmx-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/my-account/tickets/' ) ); ?>"><?php esc_html_e( 'تماس با ما', 'gmx-theme' ); ?></a></li>
				</ul>
			<?php } ?>
		</nav>

		<div class="footer-socials">
			<a href="#" aria-label="تلگرام" class="soc soc-tg">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M22 2 2.5 9.5l5.5 2 2 6 3-4.5 4.5 3.5L22 2z"/></svg>
			</a>
			<a href="#" aria-label="اینستاگرام" class="soc soc-ig">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
			</a>
			<a href="#" aria-label="دیسکورد" class="soc soc-dc">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.3 4.4A19.8 19.8 0 0 0 15.4 3l-.2.4c1.7.4 3.3 1.1 4.8 2.1a16.2 16.2 0 0 0-14 0c1.5-1 3.1-1.7 4.8-2.1L10.6 3a19.8 19.8 0 0 0-4.9 1.5A20.3 20.3 0 0 0 2.2 18.1a19.9 19.9 0 0 0 6 3l1.5-2.4c-.8-.3-1.6-.7-2.3-1.2l.6-.4a14.2 14.2 0 0 0 12 0l.6.4c-.7.5-1.5.9-2.3 1.2l1.5 2.4a19.9 19.9 0 0 0 6-3 20.3 20.3 0 0 0-3.5-13.7ZM8.7 15.3c-1 0-1.9-1-1.9-2.1s.8-2.1 1.9-2.1 1.9 1 1.9 2.1-.9 2.1-1.9 2.1Zm6.6 0c-1 0-1.9-1-1.9-2.1s.8-2.1 1.9-2.1 1.9 1 1.9 2.1-.8 2.1-1.9 2.1Z"/></svg>
			</a>
			<a href="#" aria-label="یوتیوب" class="soc soc-yt">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23 7.2s-.2-1.6-.9-2.3c-.9-.9-1.9-.9-2.3-1C16.6 3.7 12 3.7 12 3.7s-4.6 0-7.8.2c-.4.1-1.4.1-2.3 1-.7.7-.9 2.3-.9 2.3S.8 9.1.8 11v1.8c0 1.9.2 3.8.2 3.8s.2 1.6.9 2.3c.9.9 2 .9 2.5 1 1.8.2 7.6.2 7.6.2s4.6 0 7.8-.3c.4-.1 1.4-.1 2.3-1 .7-.7.9-2.3.9-2.3s.2-1.9.2-3.8V11c0-1.9-.2-3.8-.2-3.8ZM9.7 14.9V8.4l6.1 3.3-6.1 3.2Z"/></svg>
			</a>
		</div>
	</div>

	<div class="container footer-sub">
		<div class="footer-newsletter">
			<form class="nl-form" onsubmit="return false;">
				<input type="email" placeholder="<?php esc_attr_e( 'ایمیل خود را وارد کنید...', 'gmx-theme' ); ?>" />
				<button type="submit" class="btn btn-primary"><?php esc_html_e( 'عضویت', 'gmx-theme' ); ?></button>
			</form>
		</div>
		<p class="footer-copy">© <?php echo esc_html( gmx_fa_num( gmdate( 'Y' ) ) ); ?> <?php bloginfo( 'name' ); ?> | <?php esc_html_e( 'تمامی حقوق محفوظ است.', 'gmx-theme' ); ?></p>
		<a class="back-top" href="#" aria-label="<?php esc_attr_e( 'برگشت به بالا', 'gmx-theme' ); ?>">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5"/><polyline points="5 12 12 5 19 12"/></svg>
			<?php esc_html_e( 'برگشت به بالا', 'gmx-theme' ); ?>
		</a>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
