<?php
/**
 * صفحه ۴۰۴.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="container page-section">
	<div class="error-404">
		<div class="error-code">۴۰۴</div>
		<h1><?php esc_html_e( 'Game Over — صفحه پیدا نشد!', 'gmx-theme' ); ?></h1>
		<p><?php esc_html_e( 'صفحه‌ای که دنبالش بودید وجود ندارد یا جابه‌جا شده است.', 'gmx-theme' ); ?></p>
		<div class="hero-actions">
			<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'بازگشت به خانه', 'gmx-theme' ); ?></a>
			<a class="btn btn-ghost" href="<?php echo esc_url( get_post_type_archive_link( 'gmx_product' ) ); ?>"><?php esc_html_e( 'مشاهده محصولات', 'gmx-theme' ); ?></a>
		</div>
	</div>
</section>

<?php
get_footer();
