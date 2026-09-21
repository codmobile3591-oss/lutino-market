<?php
/**
 * سیستم سفارش: ایجاد، وضعیت‌ها، تاریخچه، اسکرو.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس سفارش‌ها.
 */
class GMX_Orders {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'init', array( $this, 'handle_form_posts' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	/**
	 * پردازش فرم‌های سفارش (ایجاد، پرداخت، لغو، تغییر وضعیت).
	 */
	public function handle_form_posts() {
		if ( empty( $_POST['gmx_order_action'] ) ) {
			return;
		}

		$action = sanitize_key( $_POST['gmx_order_action'] );

		if ( ! isset( $_POST['gmx_order_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['gmx_order_nonce'] ), 'gmx_order_' . $action ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$orders_url = home_url( '/my-account/orders/' );

		if ( 'create' === $action ) {
			$product_id = (int) ( $_POST['product_id'] ?? 0 );
			$quantity   = max( 1, (int) ( $_POST['quantity'] ?? 1 ) );

			// اطلاعات اکانت خریدار (دینامیک).
			$account_info = array();
			if ( isset( $_POST['gmx_account_fields'] ) && is_array( $_POST['gmx_account_fields'] ) ) {
				foreach ( wp_unslash( $_POST['gmx_account_fields'] ) as $key => $value ) {
					$account_info[ sanitize_key( $key ) ] = sanitize_text_field( $value );
				}
			}
			if ( isset( $_POST['account_note'] ) ) {
				$account_info['note'] = sanitize_textarea_field( $_POST['account_note'] );
			}

			$order_id = self::create( $product_id, $quantity, $account_info );

			if ( is_wp_error( $order_id ) ) {
				set_transient( 'gmx_order_error_' . get_current_user_id(), $order_id->get_error_message(), 60 );
				wp_safe_redirect( get_permalink( $product_id ) );
				exit;
			}

			// اگر روش پرداخت کیف پول انتخاب شده باشد.
			if ( isset( $_POST['pay_with_wallet'] ) ) {
				$order = self::get( $order_id );
				$balance = GMX_Wallet::balance( get_current_user_id() );
				if ( $order && $balance >= (float) $order->total ) {
					GMX_Wallet::add_transaction(
						get_current_user_id(),
						-1 * (float) $order->total,
						'payment',
						'order:' . $order_id,
						sprintf( __( 'پرداخت سفارش %s از کیف پول', 'gmx-market' ), $order->order_no )
					);
					self::transition( $order_id, 'paid', get_current_user_id(), __( 'پرداخت با کیف پول', 'gmx-market' ) );

					$auto = self::get_auto_delivery( $order->product_id );
					if ( $auto && '1' === (string) get_post_meta( $order->product_id, '_gmx_auto_delivery', true ) ) {
						self::set_delivery_note( $order_id, $auto );
						self::transition( $order_id, 'processing', 0, __( 'تحویل خودکار', 'gmx-market' ) );
						self::transition( $order_id, 'delivered', 0, __( 'اطلاعات محصول ارائه شد', 'gmx-market' ) );
					}
				}
			}

			wp_safe_redirect( add_query_arg( 'order', $order_id, $orders_url ) );
			exit;
		}

		if ( 'pay' === $action ) {
			$order_id = (int) ( $_POST['order_id'] ?? 0 );
			$result   = GMX_Payments::start( $order_id );
			if ( is_wp_error( $result ) ) {
				set_transient( 'gmx_order_error_' . get_current_user_id(), $result->get_error_message(), 60 );
				wp_safe_redirect( add_query_arg( 'order', $order_id, $orders_url ) );
				exit;
			}
		}

		if ( 'cancel' === $action ) {
			$order_id = (int) ( $_POST['order_id'] ?? 0 );
			self::transition( $order_id, 'cancelled', get_current_user_id(), __( 'لغو توسط کاربر', 'gmx-market' ) );
			wp_safe_redirect( add_query_arg( 'order', $order_id, $orders_url ) );
			exit;
		}

		if ( 'status' === $action ) {
			$order_id = (int) ( $_POST['order_id'] ?? 0 );
			$status   = sanitize_key( $_POST['new_status'] ?? '' );
			$note     = sanitize_textarea_field( $_POST['status_note'] ?? '' );
			self::transition( $order_id, $status, get_current_user_id(), $note );

			// یادداشت تحویل.
			if ( isset( $_POST['delivery_note'] ) && '' !== $_POST['delivery_note'] ) {
				self::set_delivery_note( $order_id, sanitize_textarea_field( $_POST['delivery_note'] ) );
			}

			wp_safe_redirect( add_query_arg( 'order', $order_id, home_url( '/my-account/sales/' ) ) );
			exit;
		}
	}

	/**
	 * وضعیت‌های مجاز سفارش.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			'pending_payment' => __( 'در انتظار پرداخت', 'gmx-market' ),
			'paid'            => __( 'پرداخت شده', 'gmx-market' ),
			'processing'      => __( 'در حال انجام', 'gmx-market' ),
			'delivered'       => __( 'تحویل داده شده', 'gmx-market' ),
			'completed'       => __( 'تکمیل شده', 'gmx-market' ),
			'cancelled'       => __( 'لغو شده', 'gmx-market' ),
			'refunded'        => __( 'بازپرداخت شده', 'gmx-market' ),
		);
	}

	/**
	 * انتقال‌های مجاز وضعیت بر اساس نقش.
	 *
	 * @return array<string,string[]>
	 */
	public static function allowed_transitions() {
		return array(
			'pending_payment' => array( 'paid', 'cancelled' ),
			'paid'            => array( 'processing', 'cancelled', 'refunded' ),
			'processing'      => array( 'delivered', 'cancelled', 'refunded' ),
			'delivered'       => array( 'completed', 'refunded' ),
			'completed'       => array(),
			'cancelled'       => array(),
			'refunded'        => array(),
		);
	}

	/**
	 * آیا انتقال وضعیت مجاز است؟
	 *
	 * @param string $from از.
	 * @param string $to   به.
	 * @return bool
	 */
	public static function can_transition( $from, $to ) {
		$allowed = self::allowed_transitions();
		return isset( $allowed[ $from ] ) && in_array( $to, $allowed[ $from ], true );
	}

	/**
	 * نام جداول.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_orders';
	}

	/**
	 * جدول تاریخچه.
	 */
	public static function history_table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_order_history';
	}

	/**
	 * افزودن قوانین بازنویسی URL برای اسکرو.
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^checkout/pay/([0-9]+)/?$', 'index.php?gmx_pay_order=$matches[1]', 'top' );
		add_rewrite_rule( '^checkout/verify/?$', 'index.php?gmx_verify_payment=1', 'top' );
	}

	/**
	 * ثبت متغیرهای کوئری.
	 *
	 * @param array $vars متغیرها.
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'gmx_pay_order';
		$vars[] = 'gmx_verify_payment';
		return $vars;
	}

	/**
	 * تولید شماره سفارش یکتا.
	 *
	 * @return string
	 */
	public static function generate_order_no() {
		return 'GMX-' . strtoupper( wp_generate_password( 8, false, false ) );
	}

	/**
	 * ایجاد سفارش جدید از محصول.
	 *
	 * @param int   $product_id شناسه محصول.
	 * @param int   $quantity   تعداد.
	 * @param array $account_info اطلاعات اکانت خریدار (فلانس key=>value).
	 * @return int|WP_Error شناسه سفارش یا خطا.
	 */
	public static function create( $product_id, $quantity = 1, $account_info = array() ) {
		global $wpdb;

		$product = get_post( $product_id );
		if ( ! $product || 'gmx_product' !== $product->post_type || 'publish' !== $product->post_status ) {
			return new WP_Error( 'gmx_invalid_product', __( 'محصول یافت نشد.', 'gmx-market' ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'gmx_not_logged_in', __( 'برای خرید باید وارد شوید.', 'gmx-market' ) );
		}

		$quantity = max( 1, (int) $quantity );
		$price    = (float) get_post_meta( $product_id, '_gmx_price', true );
		$total    = $price * $quantity;

		if ( $total <= 0 ) {
			return new WP_Error( 'gmx_invalid_price', __( 'قیمت محصول نامعتبر است.', 'gmx-market' ) );
		}

		// فروشنده = نویسنده محصول.
		$seller_id = (int) $product->post_author;

		$types = wp_get_post_terms( $product_id, 'gmx_product_type', array( 'fields' => 'slugs' ) );
		$type  = ! empty( $types ) ? $types[0] : 'item';

		$now  = current_time( 'mysql' );
		$comm = (int) round( $total * ( (int) get_option( 'gmx_commission_percent', 5 ) / 100 ) );

		$ok = $wpdb->insert(
			self::table(),
			array(
				'order_no'      => self::generate_order_no(),
				'user_id'       => $user_id,
				'seller_id'     => $seller_id,
				'product_id'    => $product_id,
				'product_type'  => $type,
				'product_title' => $product->post_title,
				'unit_price'    => (int) $price,
				'quantity'      => $quantity,
				'total'         => (int) $total,
				'commission'    => $comm,
				'status'        => 'pending_payment',
				'account_info'  => wp_json_encode( $account_info ),
				'created_at'    => $now,
			),
			array( '%s', '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'gmx_db_error', __( 'خطا در ثبت سفارش.', 'gmx-market' ) );
		}

		$order_id = (int) $wpdb->insert_id;

		self::log_history( $order_id, $user_id, '', 'pending_payment', __( 'سفارش ایجاد شد.', 'gmx-market' ) );

		/**
		 * پس از ایجاد سفارش.
		 *
		 * @param int   $order_id شناسه سفارش.
		 * @param int   $product_id شناسه محصول.
		 * @param array $account_info اطلاعات اکانت.
		 */
		do_action( 'gmx_order_created', $order_id, $product_id, $account_info );

		return $order_id;
	}

	/**
	 * دریافت سفارش.
	 *
	 * @param int $order_id شناسه.
	 * @return object|null
	 */
	public static function get( $order_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $order_id ) );
	}

	/**
	 * دریافت سفارش با شماره.
	 *
	 * @param string $order_no شماره سفارش.
	 * @return object|null
	 */
	public static function get_by_no( $order_no ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE order_no = %s', $order_no ) );
	}

	/**
	 * آیا کاربر به سفارش دسترسی دارد؟
	 *
	 * @param object $order  سفارش.
	 * @param int    $user_id شناسه کاربر.
	 * @return bool
	 */
	public static function user_can_view( $order, $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return ( (int) $order->user_id === $user_id ) || ( (int) $order->seller_id === $user_id );
	}

	/**
	 * تغییر وضعیت سفارش + ثبت تاریخچه + نوتیفیکیشن.
	 *
	 * @param int    $order_id شناسه سفارش.
	 * @param string $to_status وضعیت جدید.
	 * @param int    $actor_id  کاربر عامل.
	 * @param string $note      یادداشت.
	 * @return bool|WP_Error
	 */
	public static function transition( $order_id, $to_status, $actor_id = 0, $note = '' ) {
		$order = self::get( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'gmx_order_not_found', __( 'سفارش یافت نشد.', 'gmx-market' ) );
		}

		$actor_id = $actor_id ? (int) $actor_id : get_current_user_id();
		$is_admin = user_can( $actor_id, 'manage_options' );
		$is_seller = ( (int) $order->seller_id === $actor_id );
		$is_buyer = ( (int) $order->user_id === $actor_id );

		// خریدار فقط می‌تواند لغو کند؛ فروشنده/مدیر طبق ماتریس.
		if ( $is_buyer && ! $is_admin ) {
			if ( 'cancelled' !== $to_status ) {
				return new WP_Error( 'gmx_forbidden', __( 'شما اجازه این تغییر وضعیت را ندارید.', 'gmx-market' ) );
			}
		} elseif ( ! $is_admin && ! $is_seller ) {
			return new WP_Error( 'gmx_forbidden', __( 'شما اجازه این تغییر وضعیت را ندارید.', 'gmx-market' ) );
		}

		if ( ! self::can_transition( $order->status, $to_status ) ) {
			return new WP_Error( 'gmx_invalid_transition', __( 'این تغییر وضعیت مجاز نیست.', 'gmx-market' ) );
		}

		global $wpdb;
		$now = current_time( 'mysql' );

		$updated = $wpdb->update(
			self::table(),
			array(
				'status'     => $to_status,
				'updated_at' => $now,
			),
			array( 'id' => (int) $order_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'gmx_db_error', __( 'خطا در به‌روزرسانی سفارش.', 'gmx-market' ) );
		}

		self::log_history( $order_id, $actor_id, $order->status, $to_status, $note );

		// اسکرو: تسویه فروشنده هنگام تکمیل.
		if ( 'completed' === $to_status ) {
			do_action( 'gmx_order_completed', $order_id, $order );
		}

		// اسکرو: بازگرداندن وجه هنگام بازپرداخت.
		if ( 'refunded' === $to_status ) {
			do_action( 'gmx_order_refunded', $order_id, $order );
		}

		// نوتیفیکیشن وضعیت.
		do_action( 'gmx_order_status_changed', $order_id, $order->status, $to_status, $actor_id );

		return true;
	}

	/**
	 * ثبت رکورد تاریخچه.
	 *
	 * @param int    $order_id شناسه سفارش.
	 * @param int    $actor_id عامل.
	 * @param string $from     از وضعیت.
	 * @param string $to       به وضعیت.
	 * @param string $note     یادداشت.
	 */
	public static function log_history( $order_id, $actor_id, $from, $to, $note = '' ) {
		global $wpdb;
		$wpdb->insert(
			self::history_table(),
			array(
				'order_id'   => (int) $order_id,
				'actor_id'   => (int) $actor_id,
				'from_status' => (string) $from,
				'to_status'  => (string) $to,
				'note'       => (string) $note,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * تاریخچه سفارش.
	 *
	 * @param int $order_id شناسه سفارش.
	 * @return array
	 */
	public static function get_history( $order_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::history_table() . ' WHERE order_id = %d ORDER BY id ASC', (int) $order_id )
		);
	}

	/**
	 * سفارش‌های کاربر.
	 *
	 * @param int   $user_id شناسه کاربر.
	 * @param int   $limit   تعداد.
	 * @param int   $offset  آفست.
	 * @param array $args    فیلترها (status, role=buyer|seller).
	 * @return object[]
	 */
	public static function get_user_orders( $user_id, $limit = 20, $offset = 0, $args = array() ) {
		global $wpdb;
		$table = self::table();
		$where = '1=1';
		$params = array();

		$role = isset( $args['role'] ) ? $args['role'] : 'buyer';
		if ( 'seller' === $role ) {
			$where .= ' AND seller_id = %d';
		} else {
			$where .= ' AND user_id = %d';
		}
		$params[] = (int) $user_id;

		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}

		$params[] = (int) $limit;
		$params[] = (int) $offset;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d",
				$params
			)
		);
	}

	/**
	 * شمارش سفارش‌های کاربر.
	 *
	 * @param int   $user_id شناسه کاربر.
	 * @param array $args    فیلترها.
	 * @return int
	 */
	public static function count_user_orders( $user_id, $args = array() ) {
		global $wpdb;
		$table = self::table();
		$where = '1=1';
		$params = array();

		$role = isset( $args['role'] ) ? $args['role'] : 'buyer';
		if ( 'seller' === $role ) {
			$where .= ' AND seller_id = %d';
		} else {
			$where .= ' AND user_id = %d';
		}
		$params[] = (int) $user_id;

		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params )
		);
	}

	/**
	 * ذخیره یادداشت تحویل (اطلاعات حساس).
	 *
	 * @param int    $order_id شناسه.
	 * @param string $note     متن.
	 */
	public static function set_delivery_note( $order_id, $note ) {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array( 'delivery_note' => sanitize_textarea_field( $note ) ),
			array( 'id' => (int) $order_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * محتوای تحویل خودکار محصول.
	 *
	 * @param int $product_id شناسه محصول.
	 * @return string
	 */
	public static function get_auto_delivery( $product_id ) {
		return (string) get_post_meta( $product_id, '_gmx_credentials', true );
	}
}
