<?php
/**
 * کنترلر داشبورد کاربر.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس داشبورد.
 */
class GMX_Dashboard {

	/**
	 * بخش فعلی داشبورد.
	 *
	 * @var string
	 */
	private static $section = 'overview';

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_shortcode( 'gmx_dashboard', array( $this, 'shortcode_dashboard' ) );
		add_shortcode( 'gmx_checkout', array( $this, 'shortcode_checkout' ) );
	}

	/**
	 * قوانین بازنویسی داشبورد.
	 */
	public function add_rewrite_rules() {
		$tops = array( 'overview', 'orders', 'sales', 'chat', 'tickets', 'wallet', 'profile', 'seller' );
		foreach ( $tops as $top ) {
			add_rewrite_rule( '^my-account/' . $top . '(/.*)?$', 'index.php?gmx_dash=' . $top . '&gmx_dash_rest=$matches[1]', 'top' );
		}
		add_rewrite_rule( '^my-account/?$', 'index.php?gmx_dash=overview', 'top' );
	}

	/**
	 * متغیرهای کوئری.
	 *
	 * @param array $vars متغیرها.
	 * @return array
	 */		public function query_vars( $vars ) {
		$vars[] = 'gmx_dash';
		$vars[] = 'gmx_dash_rest';
		return $vars;
	}

	/**
	 * شورت‌کد داشبورد.
	 */
	public function shortcode_dashboard() {
		return self::render_dashboard_html();
	}

	/**
	 * شورت‌کد تسویه.
	 */
	public function shortcode_checkout() {
		return '<div class="gmx-checkout-notice">' . esc_html__( 'پرداخت از طریق صفحه سفارش انجام می‌شود.', 'gmx-market' ) . '</div>';
	}

	/**
	 * رندر داشبورد از طریق روتر.
	 */
	public static function maybe_render() {
		$dash = get_query_var( 'gmx_dash' );
		if ( ! $dash ) {
			return;
		}

		gmx_require_login();

		wp_enqueue_style( 'gmx-app' );
		wp_enqueue_script( 'gmx-app' );

		self::$section = sanitize_key( $dash );

		status_header( 200 );
		nocache_headers();

		require GMX_PLUGIN_DIR . 'templates/dashboard-wrapper.php';
		exit;
	}

	/**
	 * بخش فعلی.
	 *
	 * @return string
	 */
	public static function current_section() {
		return self::$section;
	}

	/**
	 * URL بخش داشبورد.
	 *
	 * @param string $section بخش.
	 * @param string $extra   مسیر اضافه.
	 * @return string
	 */
	public static function section_url( $section, $extra = '' ) {
		return home_url( '/my-account/' . trim( $section, '/' ) . '/' . $extra );
	}

	/**
	 * آیتم‌های منوی داشبورد.
	 *
	 * @return array
	 */
	public static function menu_items() {
		$items = array(
			'overview' => array( 'label' => __( 'نمای کلی', 'gmx-market' ), 'icon' => '🏠' ),
			'orders'   => array( 'label' => __( 'سفارش‌های من', 'gmx-market' ), 'icon' => '🛒' ),
			'chat'     => array( 'label' => __( 'پیام‌ها', 'gmx-market' ), 'icon' => '💬' ),
			'tickets'  => array( 'label' => __( 'تیکت پشتیبانی', 'gmx-market' ), 'icon' => '🎫' ),
			'wallet'   => array( 'label' => __( 'کیف پول', 'gmx-market' ), 'icon' => '💳' ),
			'profile'  => array( 'label' => __( 'اطلاعات حساب', 'gmx-market' ), 'icon' => '⚙️' ),
		);

		if ( GMX_Seller::is_seller() ) {
			$items['sales']  = array( 'label' => __( 'فروش‌های من', 'gmx-market' ), 'icon' => '📈' );
			$items['seller'] = array( 'label' => __( 'آگهی‌های من', 'gmx-market' ), 'icon' => '📦' );
		} else {
			$items['seller'] = array( 'label' => __( 'فروشنده شو', 'gmx-market' ), 'icon' => '🚀' );
		}

		return $items;
	}

	/**
	 * رندر HTML داشبورد.
	 *
	 * @return string
	 */
	public static function render_dashboard_html() {
		$section = self::$section;
		$menu    = self::menu_items();
		$user    = wp_get_current_user();

		// اسکریپت/استایل.
		wp_enqueue_style( 'gmx-app' );
		wp_enqueue_script( 'gmx-app' );

		ob_start();
		?>
		<div class="gmx-dash" id="gmx-dash">
			<aside class="gmx-dash-sidebar">
				<div class="gmx-dash-user">
					<?php echo gmx_avatar( $user->ID, 44 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div>
						<strong><?php echo esc_html( gmx_display_name( $user->ID ) ); ?></strong>
						<span><?php echo GMX_Seller::is_seller() ? esc_html__( 'فروشنده', 'gmx-market' ) : esc_html__( 'خریدار', 'gmx-market' ); ?></span>
					</div>
					<span class="gmx-bell" id="gmx-notif-bell">🔔<i id="gmx-notif-count" class="gmx-badge" hidden></i></span>
				</div>
				<nav class="gmx-dash-nav">
					<?php foreach ( $menu as $slug => $item ) : ?>
						<a class="gmx-dash-nav-item<?php echo $section === $slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( self::section_url( $slug ) ); ?>">
							<span class="gmx-nav-icon"><?php echo esc_html( $item['icon'] ); ?></span>
							<?php echo esc_html( $item['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
				<div class="gmx-dash-logout">
					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">🚪 <?php esc_html_e( 'خروج', 'gmx-market' ); ?></a>
				</div>
			</aside>
			<main class="gmx-dash-main">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput -- هر بخش خودش escape می‌کند.
				echo self::render_section( $section );
				?>
			</main>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * رندر بخش فعال.
	 *
	 * @param string $section بخش.
	 * @return string
	 */
	public static function render_section( $section ) {
		$file = GMX_PLUGIN_DIR . 'templates/sections/' . sanitize_file_name( $section ) . '.php';

		if ( ! file_exists( $file ) ) {
			return '<div class="gmx-alert gmx-alert-info">' . esc_html__( 'این بخش به‌زودی...', 'gmx-market' ) . '</div>';
		}

		ob_start();
		include $file;
		return (string) ob_get_clean();
	}
}
