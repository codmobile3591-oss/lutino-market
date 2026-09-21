<?php
/**
 * رندر مستقل داشبورد.
 *
 * @package GMX
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
	<title><?php echo esc_html( sprintf( '%s — %s', get_bloginfo( 'name' ), __( 'داشبورد', 'gmx-market' ) ) ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'gmx-dashboard-page' ); ?>>
	<div class="gmx-dash-shell">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo GMX_Dashboard::render_dashboard_html();
		?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
