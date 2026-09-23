<?php
/**
 * پرداخت: زرین‌پال، IDPay، PayPing.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس پرداخت.
 */
class GMX_Payments {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'catch_gateway_requests' ), 20 );
	}

	/**
	 * جدول تراکنش‌ها.
	 */
	private static function table() {
		return GMX_Wallet::table();
	}

	/**
	 * گرفتن درگاه فعال.
	 *
	 * @return string
	 */
	public static function active_gateway() {
		return get_option( 'gmx_gateway', 'zarinpal' );
	}

	/**
	 * لیست درگاه‌های ثبت‌شده (هوک گسترش‌پذیر).
	 *
	 * @return array<string,string> slug => برچسب
	 */
	public static function gateways() {
		$gateways = array(
			'zarinpal' => __( 'زرین‌پال', 'gmx-market' ),
			'idpay'    => __( 'IDPay', 'gmx-market' ),
			'payping'  => __( 'PayPing', 'gmx-market' ),
		);

		/**
		 * افزودن درگاه سفارشی.
		 *
		 * @param array $gateways درگاه‌ها.
		 */
		return apply_filters( 'gmx_gateways', $gateways );
	}

	/**
	 * شروع پرداخت سفارش و ریدایرکت به درگاه.
	 *
	 * @param int $order_id شناسه سفارش.
	 * @return true|WP_Error
	 */
	public static function start( $order_id ) {
		$order = GMX_Orders::get( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'gmx_order_not_found', __( 'سفارش یافت نشد.', 'gmx-market' ) );
		}
		if ( 'pending_payment' !== $order->status ) {
			return new WP_Error( 'gmx_already_paid', __( 'این سفارش قابل پرداخت نیست.', 'gmx-market' ) );
		}

		$gateway = self::active_gateway();
		$amount  = (float) $order->total;
		$cb      = home_url( '/checkout/verify/?order=' . $order_id );

		/**
		 * ریدایرکت سفارشی برای درگاه‌های افزودنی.
		 * خروجی: URL پرداخت یا null برای استفاده از درگاه‌های داخلی.
		 */
		$custom = apply_filters( 'gmx_gateway_start', null, $gateway, $amount, $cb, $order, 'order' );
		if ( $custom && ! is_wp_error( $custom ) ) {
			wp_safe_redirect( $custom );
			exit;
		}

		switch ( $gateway ) {
			case 'idpay':
				$res = self::idpay_request( $amount, $cb, $order );
				break;
			case 'payping':
				$res = self::payping_request( $amount, $cb, $order );
				break;
			default:
				$res = self::zarinpal_request( $amount, $cb, $order );
		}

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		wp_safe_redirect( $res );
		exit;
	}

	/**
	 * شروع پرداخت شارژ کیف پول.
	 *
	 * @param int   $user_id کاربر.
	 * @param float $amount  مبلغ.
	 * @return true|WP_Error
	 */
	public static function start_wallet_charge( $user_id, $amount ) {
		$amount = (float) $amount;
		if ( $amount < 10000 ) {
			return new WP_Error( 'gmx_min_amount', __( 'حداقل مبلغ شارژ ۱۰,۰۰۰ تومان است.', 'gmx-market' ) );
		}

		$gateway = self::active_gateway();
		$cb      = add_query_arg(
			array(
				'wallet_charge' => 1,
				'user'          => (int) $user_id,
				'amount'        => (int) $amount,
			),
			home_url( '/checkout/verify/' )
		);

		/**
		 * ریدایرکت سفارشی برای درگاه‌های افزودنی (شارژ کیف پول).
		 */
		$custom = apply_filters( 'gmx_gateway_start', null, $gateway, $amount, $cb, null, 'wallet' );
		if ( $custom && ! is_wp_error( $custom ) ) {
			wp_safe_redirect( $custom );
			exit;
		}

		switch ( $gateway ) {
			case 'idpay':
				$res = self::idpay_request( $amount, $cb, null, 'wallet', (int) $user_id );
				break;
			case 'payping':
				$res = self::payping_request( $amount, $cb, null, 'wallet', (int) $user_id );
				break;
			default:
				$res = self::zarinpal_request( $amount, $cb, null, 'wallet', (int) $user_id );
		}

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		wp_safe_redirect( $res );
		exit;
	}

	/**--------------------------------------------------------------
	 * درخواست‌ها به درگاه‌ها
	 *--------------------------------------------------------------*/

	/**
	 * درخواست پرداخت زرین‌پال.
	 *
	 * @param float      $amount مبلغ.
	 * @param string     $callback آدرس بازگشت.
	 * @param object|null $order   سفارش.
	 * @param string      $kind    order|wallet.
	 * @param int         $user_id شناسه کاربر (برای شارژ کیف پول).
	 * @return string|WP_Error
	 */
	private static function zarinpal_request( $amount, $callback, $order = null, $kind = 'order', $user_id = 0 ) {
		$merchant = get_option( 'gmx_zarinpal_merchant' );
		if ( ! $merchant ) {
			return new WP_Error( 'gmx_no_merchant', __( 'مرچنت زرین‌پال تنظیم نشده است.', 'gmx-market' ) );
		}

		// واحد پول سایت تومان است؛ زرین‌پال ریال می‌خواهد.
		$rial = (int) ( $amount * 10 );

		$body = array(
			'merchant_id'  => $merchant,
			'amount'       => $rial,
			'callback_url' => $callback,
			'description'  => $order ? sprintf( 'پرداخت سفارش %s', $order->order_no ) : 'شارژ کیف پول',
		);

		$res = wp_remote_post(
			'https://api.zarinpal.com/pg/v4/payment/request.json',
			array(
				'timeout' => 30,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( empty( $data['data']['authority'] ) || 100 !== (int) $data['data']['code'] ) {
			$code = isset( $data['data']['code'] ) ? $data['data']['code'] : ( isset( $data['errors']['code'] ) ? $data['errors']['code'] : 'unknown' );
			return new WP_Error( 'gmx_gateway_error', sprintf( __( 'خطای زرین‌پال: %s', 'gmx-market' ), $code ) );
		}

		$authority = $data['data']['authority'];
		$ref_id    = $order ? (int) $order->id : (int) $user_id;

		self::record_pending(
			$kind,
			$ref_id,
			$authority,
			$amount
		);

		return 'https://www.zarinpal.com/pg/StartPay/' . rawurlencode( $authority );
	}

	/**
	 * درخواست پرداخت IDPay.
	 *
	 * @param float      $amount مبلغ.
	 * @param string     $callback آدرس بازگشت.
	 * @param object|null $order   سفارش.
	 * @param string      $kind    order|wallet.
	 * @param int         $user_id شناسه کاربر (برای شارژ کیف پول).
	 * @return string|WP_Error
	 */
	private static function idpay_request( $amount, $callback, $order = null, $kind = 'order', $user_id = 0 ) {
		$api_key = get_option( 'gmx_idpay_api' );
		if ( ! $api_key ) {
			return new WP_Error( 'gmx_no_api', __( 'کلید IDPay تنظیم نشده است.', 'gmx-market' ) );
		}

		$body = array(
			'order_id' => $order ? $order->order_no : 'wallet-' . time(),
			'amount'   => (int) ( $amount * 10 ), // ریال.
			'callback' => $callback,
		);

		$res = wp_remote_post(
			'https://api.idpay.ir/v1.1/payment',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'X-API-KEY'     => $api_key,
					'X-SANDBOX'     => '0',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( empty( $data['link'] ) ) {
			$msg = isset( $data['error_message'] ) ? $data['error_message'] : __( 'خطای نامشخص', 'gmx-market' );
			return new WP_Error( 'gmx_gateway_error', sprintf( __( 'خطای IDPay: %s', 'gmx-market' ), $msg ) );
		}

		$ref_id = $order ? (int) $order->id : (int) $user_id;

		self::record_pending(
			$kind,
			$ref_id,
			$data['id'],
			$amount
		);

		return $data['link'];
	}

	/**
	 * درخواست پرداخت PayPing.
	 *
	 * @param float      $amount مبلغ.
	 * @param string     $callback آدرس بازگشت.
	 * @param object|null $order   سفارش.
	 * @param string      $kind    order|wallet.
	 * @param int         $user_id شناسه کاربر (برای شارژ کیف پول).
	 * @return string|WP_Error
	 */
	private static function payping_request( $amount, $callback, $order = null, $kind = 'order', $user_id = 0 ) {
		$api_key = get_option( 'gmx_payping_api' );
		if ( ! $api_key ) {
			return new WP_Error( 'gmx_no_api', __( 'کلید PayPing تنظیم نشده است.', 'gmx-market' ) );
		}

		$payer_name = '';
		if ( $order ) {
			$payer_name = gmx_display_name( $order->user_id );
		} elseif ( $user_id ) {
			$payer_name = gmx_display_name( $user_id );
		}

		$body = array(
			'amount'      => (int) round( $amount * 10 ), // ریال.
			'payerName'   => $payer_name,
			'description' => $order ? 'سفارش ' . $order->order_no : 'شارژ کیف پول',
			'returnUrl'   => $callback,
			'clientRefId' => $order ? $order->order_no : 'wallet-' . time(),
		);

		$res = wp_remote_post(
			'https://api.payping.ir/v2/pay',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( empty( $data['code'] ) ) {
			return new WP_Error( 'gmx_gateway_error', __( 'خطای PayPing', 'gmx-market' ) );
		}

		$ref_id = $order ? (int) $order->id : (int) $user_id;

		self::record_pending(
			$kind,
			$ref_id,
			$data['code'],
			$amount
		);

		return 'https://api.payping.ir/v2/pay/gotoipg/' . rawurlencode( $data['code'] );
	}

	/**--------------------------------------------------------------
	 * ثبت و وریفای
	 *--------------------------------------------------------------*/

	/**
	 * ثبت تراکنش در انتظار.
	 *
	 * @param string $kind    order|wallet.
	 * @param int    $ref_id  شناسه سفارش یا کاربر.
	 * @param string $token   توکن درگاه.
	 * @param float  $amount  مبلغ تومان.
	 */
	private static function record_pending( $kind, $ref_id, $token, $amount ) {
		global $wpdb;
		$wpdb->insert(
			self::table(),
			array(
				'user_id'    => ( 'wallet' === $kind ) ? (int) $ref_id : 0,
				'amount'     => (float) $amount,
				'type'       => 'gateway_pending',
				'ref'        => $kind . ':' . $token,
				'description' => ( 'wallet' === $kind ) ? 'شارژ کیف پول' : 'سفارش #' . (int) $ref_id,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%f', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * یافتن تراکنش معلق با توکن.
	 *
	 * @param string $token توکن.
	 * @return object|null
	 */
	private static function find_pending( $token ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM " . self::table() . " WHERE ref LIKE %s AND type = 'gateway_pending' ORDER BY id DESC LIMIT 1",
				'%' . $wpdb->esc_like( $token ) . '%'
			)
		);
	}

	/**
	 * دریافت درخواست‌های بازگشت از درگاه.
	 */
	public function catch_gateway_requests() {
		// پرداخت مستقیم سفارش: /checkout/pay/{id}.
		if ( $order_id = get_query_var( 'gmx_pay_order' ) ) {
			$result = self::start( (int) $order_id );
			if ( is_wp_error( $result ) ) {
				wp_die( esc_html( $result->get_error_message() ) );
			}
		}

		// بازگشت از درگاه: /checkout/verify/.
		if ( get_query_var( 'gmx_verify_payment' ) ) {
			self::handle_verify();
		}
	}

	/**
	 * وریفای پرداخت.
	 */
	private static function handle_verify() {
		$token = '';
		$paid  = false;
		$gw    = self::active_gateway();

		if ( 'idpay' === $gw ) {
			$token  = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
			$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
			$paid   = ( '100' === $status || '10' === $status );
		} elseif ( 'payping' === $gw ) {
			$token  = isset( $_GET['refid'] ) ? sanitize_text_field( $_GET['refid'] ) : '';
			$paid   = ! empty( $_GET['code'] );
		} else {
			$token  = isset( $_GET['Authority'] ) ? sanitize_text_field( $_GET['Authority'] ) : '';
			$status = isset( $_GET['Status'] ) ? sanitize_text_field( $_GET['Status'] ) : '';
			$paid   = ( 'OK' === $status );
		}

		$pending = $token ? self::find_pending( $token ) : null;

		if ( ! $pending || ! $paid ) {
			wp_safe_redirect( add_query_arg( 'payment', 'failed', home_url( '/my-account/orders/' ) ) );
			exit;
		}

		// وریفای نزد درگاه.
		$verified = self::verify_with_gateway( $gw, $token, (float) $pending->amount );
		if ( ! $verified ) {
			wp_safe_redirect( add_query_arg( 'payment', 'failed', home_url( '/my-account/orders/' ) ) );
			exit;
		}

		$desc = (string) $pending->description;
		if ( preg_match( '/#(\d+)$/', $desc, $m ) ) {
			// پرداخت سفارش.
			$order_id = (int) $m[1];
			$order    = GMX_Orders::get( $order_id );
			if ( $order && 'pending_payment' === $order->status ) {
				global $wpdb;
				$wpdb->update(
					self::table(),
					array(
						'type' => 'payment',
						'user_id' => (int) $order->user_id,
					),
					array( 'id' => (int) $pending->id ),
					array( '%s', '%d' ),
					array( '%d' )
				);

				GMX_Orders::transition( $order_id, 'paid', 0, __( 'پرداخت آنلاین موفق', 'gmx-market' ) );

				// تحویل خودکار.
				$auto = GMX_Orders::get_auto_delivery( $order->product_id );
				if ( $auto && '1' === (string) get_post_meta( $order->product_id, '_gmx_auto_delivery', true ) ) {
					GMX_Orders::set_delivery_note( $order_id, $auto );
					GMX_Orders::transition( $order_id, 'processing', 0, __( 'تحویل خودکار', 'gmx-market' ) );
					GMX_Orders::transition( $order_id, 'delivered', 0, __( 'اطلاعات محصول ارائه شد', 'gmx-market' ) );
				}
			}
		} elseif ( preg_match( '/^wallet:/', (string) $pending->ref ) ) {
			// شارژ کیف پول.
			if ( (int) $pending->user_id ) {
				GMX_Wallet::add_transaction(
					(int) $pending->user_id,
					(float) $pending->amount,
					'deposit',
					'charge:' . $token,
					__( 'شارژ کیف پول', 'gmx-market' )
				);
				$wpdb2 = $GLOBALS['wpdb'];
				$wpdb2->update(
					self::table(),
					array( 'type' => 'charge' ),
					array( 'id' => (int) $pending->id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}

		wp_safe_redirect( add_query_arg( 'payment', 'success', home_url( '/my-account/orders/' ) ) );
		exit;
	}

	/**
	 * وریفای نزد درگاه.
	 *
	 * @param string $gw     درگاه.
	 * @param string $token  توکن.
	 * @param float  $amount مبلغ تومان.
	 * @return bool
	 */
	private static function verify_with_gateway( $gw, $token, $amount ) {
		$rial = (int) ( $amount * 10 );

		if ( 'idpay' === $gw ) {
			$api_key = get_option( 'gmx_idpay_api' );
			$res = wp_remote_post(
				'https://api.idpay.ir/v1.1/payment/verify',
				array(
					'timeout' => 30,
					'headers' => array(
						'Content-Type' => 'application/json',
						'X-API-KEY'    => $api_key,
					),
					'body'    => wp_json_encode(
						array(
							'id'     => $token,
							'order_id' => isset( $_GET['order_id'] ) ? sanitize_text_field( $_GET['order_id'] ) : '',
						)
					),
				)
			);
			if ( is_wp_error( $res ) ) {
				return false;
			}
			$data = json_decode( wp_remote_retrieve_body( $res ), true );
			return isset( $data['status'] ) && in_array( (string) $data['status'], array( '100', '200' ), true );
		}

		if ( 'payping' === $gw ) {
			$api_key = get_option( 'gmx_payping_api' );
			$res = wp_remote_post(
				'https://api.payping.ir/v2/pay/verify',
				array(
					'timeout' => 30,
					'headers' => array(
						'Content-Type' => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					),
					'body'    => wp_json_encode(
						array(
							'refId'  => $token,
							'amount' => $rial,
						)
					),
				)
			);
			if ( is_wp_error( $res ) ) {
				return false;
			}
			return ( 200 === wp_remote_retrieve_response_code( $res ) );
		}

		// زرین‌پال.
		$merchant = get_option( 'gmx_zarinpal_merchant' );
		$res = wp_remote_post(
			'https://api.zarinpal.com/pg/v4/payment/verify.json',
			array(
				'timeout' => 30,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'merchant_id' => $merchant,
						'amount'      => $rial,
						'authority'   => $token,
					)
				),
			)
		);
		if ( is_wp_error( $res ) ) {
			return false;
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		return isset( $data['data']['code'] ) && in_array( (int) $data['data']['code'], array( 100, 101 ), true );
	}
}
