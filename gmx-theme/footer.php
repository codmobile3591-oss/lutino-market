<?php
/**
 * فوتر قالب.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>

<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-brand">
			<img class="brand-mark-img" src="<?php echo esc_url( GMX_THEME_URL . '/assets/img/logo.svg' ); ?>" alt="" width="34" height="34" /> <strong><?php bloginfo( 'name' ); ?></strong>
			<p><?php esc_html_e( 'مارکت‌پلیس امن خرید و فروش آیتم، جم، اکانت و خدمات گیمینگ با پشتیبانی ۲۴ ساعته.', 'gmx-theme' ); ?></p>
		</div>
		<div class="footer-col">
			<h4><?php esc_html_e( 'دسترسی سریع', 'gmx-theme' ); ?></h4>
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
			}
			?>
		</div>
		<div class="footer-col">
			<h4><?php esc_html_e( 'حساب کاربری', 'gmx-theme' ); ?></h4>
			<ul class="footer-links">
				<li><a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'داشبورد', 'gmx-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/my-account/orders/' ) ); ?>"><?php esc_html_e( 'سفارش‌های من', 'gmx-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/my-account/tickets/' ) ); ?>"><?php esc_html_e( 'پشتیبانی', 'gmx-theme' ); ?></a></li>
			</ul>
		</div>
	</div>
	<div class="footer-trust">
		<div class="container footer-trust-row">
			<span>🛡️ <?php esc_html_e( 'پرداخت امانی لوتینو', 'gmx-theme' ); ?></span>
			<span>🔒 <?php esc_html_e( 'درگاه امن بانکی', 'gmx-theme' ); ?></span>
			<span>📞 <?php esc_html_e( 'پشتیبانی ۲۴ ساعته', 'gmx-theme' ); ?></span>
		</div>
	</div>
	<div class="footer-bottom">
		<div class="container">
			<p>© <?php echo esc_html( gmx_fa_num( gmdate( 'Y' ) ) ); ?> <?php bloginfo( 'name' ); ?> — <?php esc_html_e( 'همه حقوق محفوظ است.', 'gmx-theme' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
