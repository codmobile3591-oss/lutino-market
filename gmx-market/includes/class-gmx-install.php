<?php
/**
 * نصب و راه‌اندازی: جداول، نقش‌ها، صفحات.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس نصب.
 */
class GMX_Install {

	/**
	 * اجرای نصب.
	 */
	public static function activate() {
		self::create_tables();
		self::create_roles();
		self::create_pages();
		self::default_options();
		update_option( 'gmx_version', GMX_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * ساخت جداول دیتابیس.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$orders = $wpdb->prefix . 'gmx_orders';
		$history = $wpdb->prefix . 'gmx_order_history';
		$chats = $wpdb->prefix . 'gmx_chats';
		$messages = $wpdb->prefix . 'gmx_chat_messages';
		$tickets = $wpdb->prefix . 'gmx_tickets';
		$ticket_msgs = $wpdb->prefix . 'gmx_ticket_messages';
		$transactions = $wpdb->prefix . 'gmx_transactions';
		$withdrawals = $wpdb->prefix . 'gmx_withdrawals';
		$notifications = $wpdb->prefix . 'gmx_notifications';
		$files = $wpdb->prefix . 'gmx_files';
		$otp = $wpdb->prefix . 'gmx_otp_codes';

		// سفارش‌ها.
		dbDelta(
			"CREATE TABLE {$orders} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				order_no VARCHAR(32) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				seller_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				product_type VARCHAR(20) NOT NULL DEFAULT 'item',
				product_title VARCHAR(191) NOT NULL DEFAULT '',
				unit_price BIGINT NOT NULL DEFAULT 0,
				quantity INT NOT NULL DEFAULT 1,
				total BIGINT NOT NULL DEFAULT 0,
				commission BIGINT NOT NULL DEFAULT 0,
				status VARCHAR(32) NOT NULL DEFAULT 'pending_payment',
				account_info TEXT NULL,
				delivery_note TEXT NULL,
				payment_tx VARCHAR(64) NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY order_no (order_no),
				KEY user_id (user_id),
				KEY seller_id (seller_id),
				KEY status (status)
			) {$charset};"
		);

		// تاریخچه سفارش.
		dbDelta(
			"CREATE TABLE {$history} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				order_id BIGINT UNSIGNED NOT NULL,
				actor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				from_status VARCHAR(32) NOT NULL DEFAULT '',
				to_status VARCHAR(32) NOT NULL DEFAULT '',
				note TEXT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY order_id (order_id)
			) {$charset};"
		);

		// گفتگوها.
		dbDelta(
			"CREATE TABLE {$chats} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				buyer_id BIGINT UNSIGNED NOT NULL,
				seller_id BIGINT UNSIGNED NOT NULL,
				last_message_at DATETIME NULL,
				buyer_unread INT NOT NULL DEFAULT 0,
				seller_unread INT NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY order_id (order_id),
				KEY buyer_id (buyer_id),
				KEY seller_id (seller_id)
			) {$charset};"
		);

		// پیام‌های چت.
		dbDelta(
			"CREATE TABLE {$messages} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				chat_id BIGINT UNSIGNED NOT NULL,
				sender_id BIGINT UNSIGNED NOT NULL,
				body TEXT NULL,
				attachment_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				seen TINYINT NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY chat_id (chat_id),
				KEY sender_id (sender_id)
			) {$charset};"
		);

		// تیکت‌ها.
		dbDelta(
			"CREATE TABLE {$tickets} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				ticket_no VARCHAR(32) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				category VARCHAR(32) NOT NULL DEFAULT 'general',
				subject VARCHAR(191) NOT NULL DEFAULT '',
				status VARCHAR(20) NOT NULL DEFAULT 'open',
				updated_at DATETIME NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY ticket_no (ticket_no),
				KEY user_id (user_id),
				KEY status (status)
			) {$charset};"
		);

		// پیام‌های تیکت.
		dbDelta(
			"CREATE TABLE {$ticket_msgs} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				ticket_id BIGINT UNSIGNED NOT NULL,
				sender_id BIGINT UNSIGNED NOT NULL,
				body TEXT NULL,
				attachment_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY ticket_id (ticket_id)
			) {$charset};"
		);

		// تراکنش‌های کیف پول.
		dbDelta(
			"CREATE TABLE {$transactions} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NOT NULL,
				amount BIGINT NOT NULL,
				type VARCHAR(24) NOT NULL DEFAULT 'deposit',
				ref VARCHAR(64) NULL,
				description VARCHAR(255) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id)
			) {$charset};"
		);

		// کدهای یکبارمصرف ورود با موبایل.
		dbDelta(
			"CREATE TABLE {$otp} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				phone VARCHAR(20) NOT NULL,
				code VARCHAR(255) NOT NULL,
				attempts INT NOT NULL DEFAULT 0,
				used TINYINT NOT NULL DEFAULT 0,
				expires_at DATETIME NOT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY phone (phone)
			) {$charset};"
		);

		// فایل‌های خصوصی.
		dbDelta(
			"CREATE TABLE {$files} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				file_key VARCHAR(64) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				context VARCHAR(20) NOT NULL DEFAULT '',
				orig_name VARCHAR(255) NOT NULL DEFAULT '',
				mime VARCHAR(80) NOT NULL DEFAULT '',
				size BIGINT NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY file_key (file_key),
				KEY user_id (user_id)
			) {$charset};"
		);

		// نوتیفیکیشن‌های داخلی.
		dbDelta(
			"CREATE TABLE {$notifications} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				link VARCHAR(255) NOT NULL DEFAULT '',
				is_read TINYINT NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id)
			) {$charset};"
		);

		// درخواست‌های برداشت.
		dbDelta(
			"CREATE TABLE {$withdrawals} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NOT NULL,
				amount BIGINT NOT NULL,
				iban VARCHAR(32) NOT NULL DEFAULT '',
				status VARCHAR(20) NOT NULL DEFAULT 'pending',
				note TEXT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id)
			) {$charset};"
		);
	}

	/**
	 * ساخت نقش فروشنده.
	 */
	public static function create_roles() {
		add_role(
			'gmx_seller',
			__( 'فروشنده گیمینگ', 'gmx-market' ),
			array(
				'read'                  => true,
				'upload_files'          => true,
				'edit_posts'            => true,
				'edit_published_posts'  => true,
				'publish_posts'         => true,
				'edit_others_gmx_order' => false,
			)
		);
		// مدیر کل همه دسترسی‌ها را دارد.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'gmx_manage_orders' );
			$admin->add_cap( 'gmx_manage_tickets' );
		}
	}

	/**
	 * ساخت صفحات پیش‌فرض.
	 */
	public static function create_pages() {
		$pages = array(
			'my-account' => array(
				'title'   => __( 'داشبورد', 'gmx-market' ),
				'content' => '[gmx_dashboard]',
			),
			'checkout'   => array(
				'title'   => __( 'تسویه حساب', 'gmx-market' ),
				'content' => '[gmx_checkout]',
			),
		);
		foreach ( $pages as $slug => $data ) {
			$existing = get_page_by_path( $slug );
			if ( ! $existing ) {
				$post_id = wp_insert_post(
					array(
						'post_title'   => $data['title'],
						'post_name'    => $slug,
						'post_content' => $data['content'],
						'post_status'  => 'publish',
						'post_type'    => 'page',
					)
				);
				update_option( 'gmx_page_' . str_replace( '-', '_', $slug ), (int) $post_id );
			}
		}
	}

	/**
	 * مقادیر پیش‌فرض تنظیمات.
	 */
	public static function default_options() {
		$defaults = array(
			'gmx_currency_unit'     => 'تومان',
			'gmx_currency_decimals' => 0,
			'gmx_commission_percent' => 5,
			'gmx_gateway'           => 'zarinpal',
			'gmx_zarinpal_merchant' => '',
			'gmx_idpay_api'         => '',
			'gmx_payping_api'       => '',
			'gmx_sms_provider'      => 'kavenegar',
			'gmx_kavenegar_key'     => '',
			'gmx_melipayamak_user'  => '',
			'gmx_melipayamak_pass'  => '',
			'gmx_melipayamak_from'  => '',
			'gmx_admin_mobile'      => '',
			'gmx_notify_email'      => get_option( 'admin_email' ),
			'gmx_ticket_categories' => 'general|عمومی,order|سفارشات,payment|پرداخت,technical|فنی,report|گزارش تخلف',
			'gmx_chat_upload_max'   => 4,
			'gmx_otp_test_mode'     => 1,
		);
		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
