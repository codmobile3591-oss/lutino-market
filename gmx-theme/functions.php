<?php
/**
 * راه‌اندازی قالب GMX Gaming.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GMX_THEME_VERSION', '1.4.0' );
define( 'GMX_THEME_DIR', get_template_directory() );
define( 'GMX_THEME_URL', get_template_directory_uri() );

/*--------------------------------------------------------------
 * محافظ وابستگی: اگر افزونه GMX Market فعال نباشد، توابع جایگزین
 * حداقلی تعریف می‌شوند تا قالب دچار خطای مهلک نشود.
 *--------------------------------------------------------------*/
if ( ! function_exists( 'gmx_price' ) ) {
	/**
	 * نمایش ساده قیمت بدون افزونه.
	 *
	 * @param float $amount مبلغ.
	 * @return string
	 */
	function gmx_price( $amount ) {
		return number_format( (float) $amount ) . ' تومان';
	}
}

if ( ! function_exists( 'gmx_fa_num' ) ) {
	/**
	 * تبدیل اعداد به فارسی.
	 *
	 * @param string $str رشته.
	 * @return string
	 */
	function gmx_fa_num( $str ) {
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			(string) $str
		);
	}
}

if ( ! function_exists( 'gmx_opt' ) ) {
	/**
	 * خواندن تنظیم با پیش‌فرض (fallback در نبود افزونه).
	 *
	 * @param string $key     کلید.
	 * @param string $default پیش‌فرض.
	 * @return string
	 */
	function gmx_opt( $key, $default = '' ) {
		$value = function_exists( 'get_option' ) ? get_option( $key, '' ) : '';
		return ( '' === $value || null === $value ) ? $default : $value;
	}
}

if ( ! function_exists( 'gmx_date' ) ) {
	/**
	 * تاریخ با wp_date.
	 *
	 * @param int|string|null $time      زمان.
	 * @param bool            $with_time نمایش ساعت.
	 * @return string
	 */
	function gmx_date( $time = null, $with_time = true ) {
		$format = $with_time ? 'Y/m/d H:i' : 'Y/m/d';
		return wp_date( $format, is_numeric( $time ) ? (int) $time : null );
	}
}

/**
 * پشتیبانی‌های قالب.
 */
function gmx_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	register_nav_menus(
		array(
			'primary' => __( 'منوی اصلی', 'gmx-theme' ),
			'footer'  => __( 'منوی فوتر', 'gmx-theme' ),
		)
	);

	add_image_size( 'gmx-card', 800, 450, true );
	add_image_size( 'gmx-hero', 960, 520, true );
}
add_action( 'after_setup_theme', 'gmx_theme_setup' );

/**
 * دارایی‌های قالب.
 */
function gmx_theme_assets() {
	wp_enqueue_style( 'gmx-theme-fonts', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css', array(), GMX_THEME_VERSION );
	wp_enqueue_style( 'gmx-theme-style', get_stylesheet_uri(), array( 'gmx-theme-fonts' ), GMX_THEME_VERSION );
	wp_enqueue_style( 'gmx-theme-main', GMX_THEME_URL . '/assets/css/main.css', array( 'gmx-theme-style' ), GMX_THEME_VERSION );
	wp_enqueue_script( 'gmx-theme-core', GMX_THEME_URL . '/assets/js/theme.js', array(), GMX_THEME_VERSION, true );

	if ( is_singular() && comments_open() ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// افکت‌های ویژه صفحه اصلی.
	if ( is_front_page() ) {
		wp_enqueue_style( 'gmx-theme-front', GMX_THEME_URL . '/assets/css/front.css', array( 'gmx-theme-main' ), GMX_THEME_VERSION );
		wp_enqueue_script( 'gmx-theme-front', GMX_THEME_URL . '/assets/js/front.js', array(), GMX_THEME_VERSION, true );
		wp_localize_script(
			'gmx-theme-front',
			'gmxThemeRest',
			array(
				'url'   => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'gmx_theme_assets' );

/**
 * کلاس body: صفحه دارک.
 */
function gmx_theme_body_class( $classes ) {
	$classes[] = 'gmx-dark';
	return $classes;
}
add_filter( 'body_class', 'gmx_theme_body_class' );

/**
 * فاوآیکون برند (تا وقتی مدیر آیکون سایت را در تنظیمات انتخاب نکرده).
 */
function gmx_theme_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( GMX_THEME_URL . '/assets/img/logo.svg' ) . '" />' . "\n";
}
add_action( 'wp_head', 'gmx_theme_favicon', 2 );
add_action( 'login_head', 'gmx_theme_favicon', 2 );

/**
 * طول خلاصه.
 */
function gmx_theme_excerpt_length() {
	return 22;
}
add_filter( 'excerpt_length', 'gmx_theme_excerpt_length' );

/**
 * شمارنده‌های هدر: سفارش‌های کاربر.
 */
function gmx_theme_user_orders_count() {
	if ( ! is_user_logged_in() || ! class_exists( 'GMX_Orders' ) ) {
		return 0;
	}
	return GMX_Orders::count_user_orders( get_current_user_id() );
}

/**
 * شمارنده پیام‌های خوانده‌نشده.
 */
function gmx_theme_unread_chats() {
	if ( ! is_user_logged_in() || ! class_exists( 'GMX_Chat' ) ) {
		return 0;
	}
	global $wpdb;
	$user_id = get_current_user_id();
	$count   = (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COALESCE(SUM(CASE WHEN buyer_id = %d THEN seller_unread ELSE buyer_unread END),0) FROM ' . $wpdb->prefix . 'gmx_chats WHERE buyer_id = %d OR seller_id = %d',
			$user_id,
			$user_id,
			$user_id
		)
	);
	return $count;
}

/**
 * REST: تازه‌سازی زنده اخبار صفحه اصلی (بازگشت HTML لیست).
 */
function gmx_theme_news_rest() {
	register_rest_route(
		'gmx/v1',
		'/news',
		array(
			'methods'             => 'GET',
			'callback'            => 'gmx_theme_news_rest_cb',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'gmx_theme_news_rest' );

/**
 * خروجی HTML لیست اخبار برای polling.
 *
 * @return WP_REST_Response
 */
function gmx_theme_news_rest_cb() {
	ob_start();
	include GMX_THEME_DIR . '/template-parts/home-news-list.php';
	$html = ob_get_clean();
	return rest_ensure_response( array( 'html' => $html ) );
}

/**
 * قیمت محصول گیمینگ (اگر افزونه فعال باشد).
 */
function gmx_theme_product_price( $product_id ) {
	$price = (float) get_post_meta( $product_id, '_gmx_price', true );
	return $price > 0 ? gmx_price( $price ) : '';
}

/**
 * برچسب نوع محصول.
 */
function gmx_theme_product_types() {
	if ( ! taxonomy_exists( 'gmx_product_type' ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'gmx_product_type',
			'hide_empty' => false,
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}
