<?php
/**
 * Plugin Name: GMX Sandbox Gateway
 * Description: درگاه پرداخت آزمایشی برای تست محلی مارکت گیمینگ — فقط برای توسعه. جایگزین درگاه واقعی.
 * Version:     1.0.0
 * Requires PHP: 7.4
 * Text Domain: gmx-sandbox
 *
 * @package GMX_Sandbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * درگاه سندباکس.
 */
final class GMX_Sandbox_Gateway {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_filter( 'gmx_gateways', array( $this, 'add_gateway' ) );
		add_filter( 'gmx_gateway_start', array( $this, 'start' ), 10, 6 );
		add_action( 'init', array( $this, 'catch_pay_page' ), 20 );
		add_action( 'wp_footer', array( $this, 'sandbox_styles' ) );
	}

	/**
	 * افزودن به لیست درگاه‌ها.
	 *
	 * @param array $gateways درگاه‌ها.
	 * @return array
	 */
	public function add_gateway( $gateways ) {
		$gateways['sandbox'] = 'درگاه آزمایشی (سندباکس)';
		return $gateways;
	}

	/**
	 * شروع پرداخت: ریدایرکت به صفحه سندباکس.
	 *
	 * @param string|null $redirect خروجی پیش‌فرض.
	 * @param string      $gateway  درگاه فعال.
	 * @param float       $amount   مبلغ.
	 * @param string      $callback آدرس بازگشت.
	 * @param object|null $order    سفارش (null برای شارژ کیف پول).
	 * @param string      $kind     order|wallet.
	 * @return string|null
	 */
	public function start( $redirect, $gateway, $amount, $callback, $order, $kind, $user_id = 0 ) {
		if ( 'sandbox' !== $gateway ) {
			return $redirect;
		}

		// ثبت رکورد در انتظار همانند درگاه واقعی.
		global $wpdb;
		$token = 'SBX-' . strtoupper( wp_generate_password( 12, false, false ) );

		// ref یکتا: kind:TOKEN[-orderID] — شناسه سفارش در خود ref ذخیره می‌شود.
		$ref = ( 'wallet' === $kind ? 'wallet:' : 'order:' ) . $token;
		if ( 'order' === $kind && $order ) {
			$ref .= '-' . (int) $order->id;
		}

		$wpdb->insert(
			$wpdb->prefix . 'gmx_transactions',
			array(
				'user_id'     => ( 'wallet' === $kind ) ? (int) $user_id : ( $order ? (int) $order->user_id : 0 ),
				'amount'      => (float) $amount,
				'type'        => 'gateway_pending',
				'ref'         => $ref,
				'description' => ( 'wallet' === $kind ) ? 'شارژ کیف پول' : 'سفارش #' . ( $order ? (int) $order->id : 0 ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%f', '%s', '%s', '%s', '%s' )
		);

		return add_query_arg(
			array(
				'gmx_sbx'  => rawurlencode( $ref ),
				'amount'   => (int) $amount,
				'back'     => rawurlencode( $callback ),
			),
			home_url( '/' )
		);
	}

	/**
	 * نمایش صفحه پرداخت سندباکس در صورت وجود پارامتر.
	 */
	public function catch_pay_page() {
		if ( empty( $_GET['gmx_sbx'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$ref = sanitize_text_field( wp_unslash( $_GET['gmx_sbx'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! preg_match( '/^(order|wallet):SBX-[A-Z0-9]+$/', $ref ) ) {
			wp_die( 'تراکنش سندباکس نامعتبر است.' );
		}

		$amount = isset( $_GET['amount'] ) ? (int) $_GET['amount'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$back   = isset( $_GET['back'] ) ? esc_url_raw( wp_unslash( $_GET['back'] ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// لغو خروجی قالب و رندر صفحه مستقل.
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/html; charset=UTF-8' );

		?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>درگاه آزمایشی — GMX Sandbox</title>
<style>
	body{font-family:Tahoma,sans-serif;background:#0d1017;color:#e6e8ee;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
	.box{background:#161a24;border:1px solid #262c3a;border-radius:16px;padding:36px;max-width:420px;width:92%;text-align:center}
	.logo{font-size:40px}
	h1{font-size:18px;margin:12px 0 4px}
	.amount{font-size:28px;color:#22d3ee;font-weight:800;margin:18px 0}
	.muted{color:#8a91a5;font-size:12px;line-height:1.9}
	.row{display:flex;gap:10px;margin-top:24px}
	.btn{flex:1;padding:13px;border:none;border-radius:10px;font-size:14px;cursor:pointer;font-family:inherit;font-weight:700}
	.ok{background:#22c55e;color:#fff}
	.fail{background:transparent;border:1px solid #ef4444;color:#ef4444}
	.token{background:#0d1017;border-radius:8px;padding:8px;font-size:11px;color:#8a91a5;direction:ltr;margin-top:14px;word-break:break-all}
</style>
</head>
<body>
<div class="box">
	<div class="logo">🧪</div>
	<h1>درگاه پرداخت آزمایشی</h1>
	<p class="muted">این صفحه فقط برای تست محلی است — هیچ پول واقعی جابه‌جا نمی‌شود.</p>
	<div class="amount"><?php echo esc_html( number_format( $amount ) ); ?> تومان</div>
	<form method="post" action="<?php echo esc_url( home_url( '/?gmx_sbx_verify=1' ) ); ?>">
		<input type="hidden" name="sbx_ref" value="<?php echo esc_attr( $ref ); ?>" />
		<input type="hidden" name="sbx_back" value="<?php echo esc_url( $back ); ?>" />
		<div class="row">
			<button class="btn ok" name="sbx_result" value="ok" type="submit">✅ پرداخت موفق</button>
			<button class="btn fail" name="sbx_result" value="fail" type="submit">✖ انصراف</button>
		</div>
	</form>
	<div class="token"><?php echo esc_html( $ref ); ?></div>
</div>
</body>
</html>
		<?php
		exit;
	}

	/**
	 * پردازش نتیجه سندباکس — در فوتر این فایل (هوک init مستقل) انجام می‌شود.
	 */

	/**
	 * استایل کوچک (رزرو).
	 */
	public function sandbox_styles() {}
}

new GMX_Sandbox_Gateway();

/**
 * پردازش نتیجه: جدا از کلاس تا قبل از قالب اجرا شود.
 */
add_action(
	'init',
	function () {
		if ( empty( $_REQUEST['gmx_sbx_verify'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$ref    = sanitize_text_field( $_POST['sbx_ref'] ?? '' );
		$back   = esc_url_raw( $_POST['sbx_back'] ?? '' );
		$result = sanitize_key( $_POST['sbx_result'] ?? '' );

		if ( ! preg_match( '/^(order|wallet):SBX-[A-Z0-9]+$/', $ref ) ) {
			wp_die( 'تراکنش نامعتبر.' );
		}
		if ( ! $back ) {
			$back = home_url( '/my-account/orders/' );
		}

		global $wpdb;

		$pending = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}gmx_transactions WHERE ref = %s AND type = 'gateway_pending' ORDER BY id DESC LIMIT 1",
				$ref
			)
		);

		if ( ! $pending ) {
			wp_safe_redirect( add_query_arg( 'payment', 'failed', home_url( '/my-account/orders/' ) ) );
			exit;
		}

		if ( 'ok' !== $result ) {
			// حذف رکورد معلق.
			$wpdb->delete( $wpdb->prefix . 'gmx_transactions', array( 'id' => (int) $pending->id ), array( '%d' ) );
			wp_safe_redirect( add_query_arg( 'payment', 'failed', home_url( '/my-account/orders/' ) ) );
			exit;
		}

		// موفق: شناسه سفارش مستقیم از ref استخراج می‌شود (مقاوم به charset).
		if ( preg_match( '/^order:SBX-[A-Z0-9]+-(\d+)$/', (string) $pending->ref, $m ) ) {
			$order_id = (int) $m[1];
			$order    = GMX_Orders::get( $order_id );
			if ( $order && 'pending_payment' === $order->status ) {
				$wpdb->update(
					$wpdb->prefix . 'gmx_transactions',
					array( 'type' => 'payment', 'user_id' => (int) $order->user_id ),
					array( 'id' => (int) $pending->id ),
					array( '%s', '%d' ),
					array( '%d' )
				);

				GMX_Orders::transition( $order_id, 'paid', 0, 'پرداخت آزمایشی موفق' );

				$auto = GMX_Orders::get_auto_delivery( $order->product_id );
				if ( $auto && '1' === (string) get_post_meta( $order->product_id, '_gmx_auto_delivery', true ) ) {
					GMX_Orders::set_delivery_note( $order_id, $auto );
					GMX_Orders::transition( $order_id, 'processing', 0, 'تحویل خودکار' );
					GMX_Orders::transition( $order_id, 'delivered', 0, 'اطلاعات محصول ارائه شد' );
				}
			}
		} elseif ( preg_match( '/^wallet:/', $ref ) && (int) $pending->user_id ) {
			GMX_Wallet::add_transaction(
				(int) $pending->user_id,
				(float) $pending->amount,
				'deposit',
				'charge:' . substr( $ref, 7 ),
				'شارژ کیف پول (سندباکس)'
			);
			$wpdb->update(
				$wpdb->prefix . 'gmx_transactions',
				array( 'type' => 'charge' ),
				array( 'id' => (int) $pending->id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		wp_safe_redirect( add_query_arg( 'payment', 'success', home_url( '/my-account/orders/' ) ) );
		exit;
	},
	30
);
