<?php
/**
 * Plugin Name:       GMX Market — Gaming Marketplace Engine
 * Description:       موتور اختصاصی مارکت‌پلیس گیمینگ: سفارش، چت داخلی، تیکت، کیف پول، کمیسیون، درگاه بانکی و پیامک. بدون هیچ افزونه ثالث.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            GMX
 * Text Domain:       gmx-market
 * Domain Path:       /languages
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GMX_VERSION', '1.0.0' );
define( 'GMX_PLUGIN_FILE', __FILE__ );
define( 'GMX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GMX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GMX_PLUGIN_DIR . 'includes/functions.php';

/**
 * بارگذاری هوشمند کلاس‌ها از پوشه includes.
 *
 * @param string $class_name نام کلاس.
 */
function gmx_autoload( $class_name ) {
	if ( 0 !== strpos( $class_name, 'GMX_' ) ) {
		return;
	}
	$file = GMX_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}
spl_autoload_register( 'gmx_autoload' );

/**
 * کلاس اصلی افزونه.
 */
final class GMX_Plugin {

	/**
	 * نمونه یکتا.
	 *
	 * @var GMX_Plugin|null
	 */
	private static $instance = null;

	/**
	 * ماژول‌های بارگذاری‌شده.
	 *
	 * @var array<string,object>
	 */
	public $modules = array();

	/**
	 * دریافت نمونه یکتا.
	 *
	 * @return GMX_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * سازنده — ثبت هوک‌ها.
	 */
	private function __construct() {
		register_activation_hook( GMX_PLUGIN_FILE, array( 'GMX_Install', 'activate' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'boot_modules' ), 5 );
		add_action( 'rest_api_init', array( $this, 'boot_rest' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'template_redirect', array( $this, 'maybe_dashboard_router' ) );
	}

	/**
	 * بارگذاری ترجمه‌ها.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'gmx-market', false, dirname( plugin_basename( GMX_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * راه‌اندازی ماژول‌های اصلی.
	 */
	public function boot_modules() {
		$this->modules['post_types'] = new GMX_Post_Types();
		$this->modules['settings']   = new GMX_Settings();
		$this->modules['orders']     = new GMX_Orders();
		$this->modules['wallet']     = new GMX_Wallet();
		$this->modules['payments']   = new GMX_Payments();
		$this->modules['notify']     = new GMX_Notify();
		$this->modules['chat']       = new GMX_Chat();
		$this->modules['tickets']    = new GMX_Tickets();
		$this->modules['uploads']    = new GMX_Uploads();
		$this->modules['dashboard']  = new GMX_Dashboard();
		$this->modules['seller']     = new GMX_Seller();
		$this->modules['auth']       = new GMX_Auth();
		$this->modules['login_page'] = new GMX_Login_Page();

		do_action( 'gmx_modules_booted', $this->modules );
	}

	/**
	 * ثبت مسیرهای REST.
	 */
	public function boot_rest() {
		foreach ( array( 'chat', 'tickets', 'orders' ) as $slug ) {
			if ( isset( $this->modules[ $slug ] ) && method_exists( $this->modules[ $slug ], 'register_rest' ) ) {
				$this->modules[ $slug ]->register_rest();
			}
		}
	}

	/**
	 * دارایی‌های سمت کاربر (فقط در صفحات دارای شورت‌کد داشبورد).
	 */
	public function enqueue_frontend() {
		wp_register_style( 'gmx-app', GMX_PLUGIN_URL . 'assets/css/app.css', array(), GMX_VERSION );
		wp_register_script( 'gmx-app', GMX_PLUGIN_URL . 'assets/js/app.js', array(), GMX_VERSION, true );

		// اگر برگه‌ای شورت‌کد داشبورد دارد، دارایی‌ها را همان‌جا هم لود کن.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && ( has_shortcode( $post->post_content, 'gmx_dashboard' ) || has_shortcode( $post->post_content, 'gmx_checkout' ) ) ) {
				wp_enqueue_style( 'gmx-app' );
				wp_enqueue_script( 'gmx-app' );
			}
		}

		wp_localize_script(
			'gmx-app',
			'GMX',
			array(
				'restUrl' => esc_url_raw( rest_url( 'gmx/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'homeUrl' => home_url( '/' ),
				'dashUrl' => home_url( '/my-account/' ),
				'userId'  => get_current_user_id(),
				'i18n'    => array(
					'sending'      => __( 'در حال ارسال…', 'gmx-market' ),
					'newMsg'       => __( 'پیام جدید', 'gmx-market' ),
					'confirm'      => __( 'مطمئن هستید؟', 'gmx-market' ),
					'uploadFail'   => __( 'آپلود فایل ناموفق بود.', 'gmx-market' ),
					'pollInterval' => 4000,
				),
			)
		);
	}

	/**
	 * روتر داشبورد.
	 */
	public function maybe_dashboard_router() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		GMX_Dashboard::maybe_render();
	}
}

/**
 * دسترسی سراسری به افزونه.
 *
 * @return GMX_Plugin
 */
function gmx() {
	return GMX_Plugin::instance();
}
gmx();
