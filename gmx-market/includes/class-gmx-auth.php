<?php
/**
 * احراز هویت با شماره موبایل + کد تایید.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس احراز هویت موبایل.
 */
class GMX_Auth {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'rest_routes' ) );
	}

	/**
	 * جدول کدهای یکبارمصرف.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_otp_codes';
	}

	/**--------------------------------------------------------------
	 * منطق OTP
	 *--------------------------------------------------------------*/

	/**
	 * نرمال‌سازی شماره موبایل ایران.
	 *
	 * @param string $raw ورودی خام.
	 * @return string شماره استاندارد 09... یا رشته خالی.
	 */
	public static function normalize_phone( $raw ) {
		$digits = str_replace(
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			(string) $raw
		);
		$digits = preg_replace( '/[^0-9]/', '', $digits );

		// 989xxxxxxxxx → 09xxxxxxxxx.
		if ( 0 === strpos( $digits, '98' ) && 12 === strlen( $digits ) ) {
			$digits = '0' . substr( $digits, 2 );
		}
		// 9xxxxxxxxx → 09xxxxxxxxx.
		if ( 0 === strpos( $digits, '9' ) && 10 === strlen( $digits ) ) {
			$digits = '0' . $digits;
		}

		if ( 1 !== preg_match( '/^09\d{9}$/', $digits ) ) {
			return '';
		}
		return $digits;
	}

	/**
	 * کاربر متصل به شماره موبایل.
	 *
	 * @param string $phone شماره.
	 * @return WP_User|null
	 */
	public static function get_user_by_phone( $phone ) {
		$users = get_users(
			array(
				'meta_key'   => 'gmx_phone',
				'meta_value' => $phone,
				'number'     => 1,
			)
		);
		return $users ? $users[0] : null;
	}

	/**
	 * ایجاد کد و ارسال.
	 *
	 * @param string $phone شماره.
	 * @return true|WP_Error
	 */
	public static function send_code( $phone ) {
		global $wpdb;

		$phone = self::normalize_phone( $phone );
		if ( ! $phone ) {
			return new WP_Error( 'gmx_bad_phone', __( 'شماره موبایل معتبر نیست (مثال: 09123456789).', 'gmx-market' ) );
		}

		// محدودیت نرخ: حداکثر ۳ کد در ۱۰ دقیقه.
		$recent = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table() . ' WHERE phone = %s AND created_at > DATE_SUB(%s, INTERVAL 10 MINUTE)',
				$phone,
				current_time( 'mysql' )
			)
		);
		if ( $recent >= 3 ) {
			return new WP_Error( 'gmx_rate_limited', __( 'تعداد درخواست زیاد است. چند دقیقه بعد تلاش کنید.', 'gmx-market' ) );
		}

		$code      = (string) wp_rand( 10000, 99999 );
		$test_mode = (bool) get_option( 'gmx_otp_test_mode', 1 );

		$wpdb->insert(
			self::table(),
			array(
				'phone'      => $phone,
				'code'       => wp_hash_password( $code ),
				'expires_at' => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) + 120 ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		if ( $test_mode ) {
			// حالت تست: کد در لاگ و پاسخ (فقط محیط توسعه).
			error_log( '[GMX OTP] ' . $phone . ' => ' . $code );
			set_transient( 'gmx_otp_last_' . get_current_user_id(), $code, 120 );
		} else {
			GMX_Notify::sms( $phone, sprintf( __( 'کد ورود شما: %s', 'gmx-market' ), $code ) );
		}

		return true;
	}

	/**
	 * بررسی کد و ورود/ثبت‌نام.
	 *
	 * @param string $phone      شماره.
	 * @param string $code       کد.
	 * @param bool   $auto_register ثبت‌نام خودکار.
	 * @return int|WP_Error شناسه کاربر
	 */
	public static function verify_code( $phone, $code, $auto_register = true ) {
		global $wpdb;

		$phone = self::normalize_phone( $phone );
		$code  = preg_replace( '/[^0-9]/', '', (string) $code );

		if ( ! $phone || 5 !== strlen( $code ) ) {
			return new WP_Error( 'gmx_invalid', __( 'شماره یا کد نامعتبر است.', 'gmx-market' ) );
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE phone = %s AND used = 0 AND expires_at > %s ORDER BY id DESC LIMIT 1',
				$phone,
				current_time( 'mysql' )
			)
		);

		if ( ! $row ) {
			return new WP_Error( 'gmx_expired', __( 'کدی یافت نشد یا منقضی شده است. کد جدید بگیرید.', 'gmx-market' ) );
		}

		// محدودیت نرخ بررسی: حداکثر ۶ تلاش برای هر کد.
		if ( (int) $row->attempts >= 6 ) {
			return new WP_Error( 'gmx_too_many', __( 'تلاش‌های زیادی انجام شده. کد جدید بگیرید.', 'gmx-market' ) );
		}

		if ( ! wp_check_password( $code, $row->code ) ) {
			$wpdb->query(
				$wpdb->prepare( 'UPDATE ' . self::table() . ' SET attempts = attempts + 1 WHERE id = %d', (int) $row->id )
			);
			return new WP_Error( 'gmx_wrong_code', __( 'کد وارد شده اشتباه است.', 'gmx-market' ) );
		}

		// مصرف کد.
		$wpdb->update(
			self::table(),
			array( 'used' => 1 ),
			array( 'id' => (int) $row->id ),
			array( '%d' ),
			array( '%d' )
		);

		// کاربر موجود؟
		$user = self::get_user_by_phone( $phone );
		if ( ! $user ) {
			if ( ! $auto_register || ! get_option( 'users_can_register' ) ) {
				return new WP_Error( 'gmx_no_user', __( 'حسابی با این شماره وجود ندارد.', 'gmx-market' ) );
			}

			$username = 'u' . substr( $phone, 2 );
			$counter  = 1;
			while ( username_exists( $username ) ) {
				$username = 'u' . substr( $phone, 2 ) . '_' . $counter++;
			}

			$user_id = wp_create_user( $username, wp_generate_password( 20 ), $username . '@gmx.local' );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
			update_user_meta( $user_id, 'gmx_phone', $phone );
			update_user_meta( $user_id, 'display_name', '۰' . substr( $phone, 1 ) );

			$user = get_userdata( $user_id );

			do_action( 'gmx_user_registered_phone', $user_id, $phone );
		} else {
			update_user_meta( $user->ID, 'gmx_phone', $phone );
		}

		do_action( 'gmx_user_logged_in_phone', $user->ID, $phone );

		return (int) $user->ID;
	}

	/**--------------------------------------------------------------
	 * REST
	 *--------------------------------------------------------------*/

	/**
	 * ثبت مسیرها.
	 */
	public function rest_routes() {
		register_rest_route(
			'gmx/v1',
			'/auth/send',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_send' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'gmx/v1',
			'/auth/verify',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_verify' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * ارسال کد.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_send( $request ) {
		$result = self::send_code( (string) $request->get_param( 'phone' ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$out = array( 'ok' => true, 'message' => __( 'کد تایید ارسال شد.', 'gmx-market' ) );

		if ( get_option( 'gmx_otp_test_mode', 1 ) ) {
			$code = get_transient( 'gmx_otp_last_' . get_current_user_id() );
			if ( $code ) {
				$out['test_code'] = $code;
			}
		}

		return rest_ensure_response( $out );
	}

	/**
	 * تایید و ورود.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_verify( $request ) {
		$user_id = self::verify_code(
			(string) $request->get_param( 'phone' ),
			(string) $request->get_param( 'code' )
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
		do_action( 'wp_login', get_userdata( $user_id )->user_login, get_userdata( $user_id ) );

		return rest_ensure_response(
			array(
				'ok'       => true,
				'user_id'  => $user_id,
				'redirect' => home_url( '/my-account/' ),
			)
		);
	}
}
