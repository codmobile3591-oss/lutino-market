<?php
/**
 * کیف پول: موجودی، تراکنش‌ها، تسویه فروشنده.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس کیف پول.
 */
class GMX_Wallet {

	/**
	 * سازنده.
	 */
	public function __construct() {
		// اسکروها.
		add_action( 'gmx_order_completed', array( $this, 'credit_seller' ), 10, 2 );
		add_action( 'gmx_order_refunded', array( $this, 'refund_order' ), 10, 2 );
		add_action( 'init', array( $this, 'handle_form_posts' ) );
	}

	/**
	 * پردازش فرم‌های شارژ و برداشت کیف پول.
	 */
	public function handle_form_posts() {
		if ( empty( $_POST['gmx_wallet_action'] ) ) {
			return;
		}

		$action = sanitize_key( $_POST['gmx_wallet_action'] );
		$nonce  = 'charge' === $action ? 'gmx_wallet_charge' : 'gmx_wallet_withdraw';

		if ( ! isset( $_POST['gmx_wallet_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['gmx_wallet_nonce'] ), $nonce ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$back = home_url( '/my-account/wallet/' );

		if ( 'charge' === $action ) {
			$amount = gmx_to_int( $_POST['amount'] ?? 0 );
			$result = GMX_Payments::start_wallet_charge( $user_id, $amount );
			if ( is_wp_error( $result ) ) {
				set_transient( 'gmx_wallet_error_' . $user_id, $result->get_error_message(), 60 );
				wp_safe_redirect( $back );
				exit;
			}
		}

		if ( 'withdraw' === $action && GMX_Seller::is_seller( $user_id ) ) {
			$amount = gmx_to_int( $_POST['amount'] ?? 0 );
			$iban   = sanitize_text_field( $_POST['iban'] ?? '' );
			$result = self::request_withdrawal( $user_id, $amount, $iban );
			if ( is_wp_error( $result ) ) {
				set_transient( 'gmx_wallet_error_' . $user_id, $result->get_error_message(), 60 );
			}
			wp_safe_redirect( $back );
			exit;
		}
	}

	/**
	 * جدول تراکنش‌ها.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_transactions';
	}

	/**
	 * جدول برداشت‌ها.
	 */
	public static function withdrawals_table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_withdrawals';
	}

	/**
	 * موجودی کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return float
	 */
	public static function balance( $user_id ) {
		global $wpdb;
		return (float) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . self::table() . ' WHERE user_id = %d', (int) $user_id )
		);
	}

	/**
	 * ثبت تراکنش (اتمیک با قفل ردیف کاربر).
	 *
	 * @param int    $user_id شناسه کاربر.
	 * @param float  $amount  مبلغ (مثبت= واریز، منفی= برداشت).
	 * @param string $type    نوع.
	 * @param string $ref     ارجاع.
	 * @param string $desc    توضیح.
	 * @return int|false شناسه تراکنش.
	 */
	public static function add_transaction( $user_id, $amount, $type, $ref = '', $desc = '' ) {
		global $wpdb;

		$ok = $wpdb->insert(
			self::table(),
			array(
				'user_id'    => (int) $user_id,
				'amount'     => (float) $amount,
				'type'       => (string) $type,
				'ref'        => (string) $ref,
				'description'=> (string) $desc,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( ! $ok ) {
			return false;
		}
		return (int) $wpdb->insert_id;
	}

	/**
	 * تراکنش‌های کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $limit   تعداد.
	 * @param int $offset  آفست.
	 * @return object[]
	 */
	public static function get_transactions( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d OFFSET %d',
				(int) $user_id,
				(int) $limit,
				(int) $offset
			)
		);
	}

	/**
	 * تسویه فروشنده پس از تکمیل سفارش.
	 *
	 * @param int    $order_id شناسه سفارش.
	 * @param object $order    سفارش.
	 */
	public function credit_seller( $order_id, $order ) {
		$seller_id = (int) $order->seller_id;
		if ( ! $seller_id ) {
			return;
		}

		$amount = (float) $order->total - (float) $order->commission;

		// جلوگیری از تسویه تکراری.
		if ( self::has_transaction( $order_id, 'settlement' ) ) {
			return;
		}

		self::add_transaction(
			$seller_id,
			$amount,
			'settlement',
			'order:' . $order_id,
			sprintf( __( 'تسویه فروش سفارش %s (پس از کسر کمیسیون)', 'gmx-market' ), $order->order_no )
		);
	}

	/**
	 * آیا تراکنش وجود دارد؟ (جلوگیری از دوباره‌کاری)
	 *
	 * @param int    $order_id شناسه سفارش.
	 * @param string $type     نوع.
	 * @return bool
	 */
	public static function has_transaction( $order_id, $type ) {
		global $wpdb;
		$ref = 'order:' . (int) $order_id;
		return (bool) $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE ref = %s AND type = %s', $ref, $type )
		);
	}

	/**
	 * بازپرداخت سفارش به کیف پول خریدار (هوک gmx_order_refunded).
	 *
	 * @param int    $order_id شناسه سفارش.
	 * @param object $order    سفارش.
	 */
	public function refund_order( $order_id, $order ) {
		self::refund_buyer( $order_id, $order );
	}

	/**
	 * بازپرداخت به خریدار.
	 *
	 * @param int    $order_id شناسه سفارش.
	 * @param object $order    سفارش.
	 */
	public static function refund_buyer( $order_id, $order ) {
		if ( self::has_transaction( $order_id, 'refund' ) ) {
			return;
		}
		self::add_transaction(
			(int) $order->user_id,
			(float) $order->total,
			'refund',
			'order:' . (int) $order_id,
			sprintf( __( 'بازپرداخت سفارش %s', 'gmx-market' ), $order->order_no )
		);
	}

	/**
	 * درخواست برداشت فروشنده.
	 *
	 * @param int    $user_id شناسه کاربر.
	 * @param float  $amount  مبلغ.
	 * @param string $iban    شبا.
	 * @return true|WP_Error
	 */
	public static function request_withdrawal( $user_id, $amount, $iban ) {
		global $wpdb;

		$amount = (float) $amount;
		$min    = (float) get_option( 'gmx_min_withdrawal', 100000 );

		if ( $amount < $min ) {
			return new WP_Error( 'gmx_min_withdrawal', sprintf( __( 'حداقل مبلغ برداشت %s است.', 'gmx-market' ), gmx_price( $min ) ) );
		}

		if ( self::balance( $user_id ) < $amount ) {
			return new WP_Error( 'gmx_insufficient', __( 'موجودی کافی نیست.', 'gmx-market' ) );
		}

		$ok = $wpdb->insert(
			self::withdrawals_table(),
			array(
				'user_id' => (int) $user_id,
				'amount'  => (int) $amount,
				'iban'    => sanitize_text_field( $iban ),
				'status'  => 'pending',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'gmx_db_error', __( 'خطا در ثبت درخواست.', 'gmx-market' ) );
		}

		// مسدود کردن وجه: تراکنش منفی.
		self::add_transaction(
			(int) $user_id,
			-1 * $amount,
			'withdrawal_hold',
			'withdrawal:' . (int) $wpdb->insert_id,
			__( 'درخواست برداشت', 'gmx-market' )
		);

		return true;
	}

	/**
	 * درخواست‌های برداشت کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return object[]
	 */
	public static function get_withdrawals( $user_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::withdrawals_table() . ' WHERE user_id = %d ORDER BY id DESC', (int) $user_id )
		);
	}

	/**
	 * مدیریت درخواست برداشت (تایید/رد) توسط مدیر.
	 *
	 * @param int    $withdrawal_id شناسه.
	 * @param string $decision      approved|rejected.
	 * @return true|WP_Error
	 */
	public static function decide_withdrawal( $withdrawal_id, $decision ) {
		global $wpdb;
		$table = self::withdrawals_table();

		$w = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $withdrawal_id ) );
		if ( ! $w || 'pending' !== $w->status ) {
			return new WP_Error( 'gmx_not_pending', __( 'این درخواست قابل بررسی نیست.', 'gmx-market' ) );
		}

		if ( 'approved' === $decision ) {
			$wpdb->update( $table, array( 'status' => 'approved' ), array( 'id' => (int) $withdrawal_id ), array( '%s' ), array( '%d' ) );
			do_action( 'gmx_withdrawal_approved', (int) $withdrawal_id, $w );
		} elseif ( 'rejected' === $decision ) {
			$wpdb->update( $table, array( 'status' => 'rejected' ), array( 'id' => (int) $withdrawal_id ), array( '%s' ), array( '%d' ) );
			// بازگرداندن وجه مسدودشده.
			self::add_transaction(
				(int) $w->user_id,
				(float) $w->amount,
				'withdrawal_refund',
				'withdrawal:' . (int) $withdrawal_id,
				__( 'رد درخواست برداشت — بازگشت وجه', 'gmx-market' )
			);
		}

		return true;
	}
}
