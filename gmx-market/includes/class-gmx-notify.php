<?php
/**
 * نوتیفیکیشن: پیامک، ایمیل، پیام داخلی.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس نوتیفیکیشن.
 */
class GMX_Notify {

	/**
	 * سازنده.
	 */
	public function __construct() {
		// تغییر وضعیت سفارش → پیامک و ایمیل به خریدار.
		add_action( 'gmx_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 4 );
		// پیام چت جدید → ایمیل به گیرنده.
		add_action( 'gmx_chat_new_message', array( $this, 'on_chat_message' ), 10, 3 );
		// پاسخ تیکت → ایمیل به کاربر.
		add_action( 'gmx_ticket_replied', array( $this, 'on_ticket_reply' ), 10, 3 );
	}

	/**
	 * جدول نوتیفیکیشن‌های داخلی.
	 */
	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_notifications';
	}

	/**--------------------------------------------------------------
	 * پیامک
	 *--------------------------------------------------------------*/

	/**
	 * ارسال پیامک.
	 *
	 * @param string $to   شماره مقصد.
	 * @param string $text متن پیام.
	 * @return bool
	 */
	public static function sms( $to, $text ) {
		$to = preg_replace( '/[^0-9+]/', '', (string) $to );
		if ( ! $to ) {
			return false;
		}

		$provider = get_option( 'gmx_sms_provider', 'kavenegar' );
		$result   = false;

		if ( 'melipayamak' === $provider ) {
			$user = get_option( 'gmx_melipayamak_user' );
			$pass = get_option( 'gmx_melipayamak_pass' );
			$from = get_option( 'gmx_melipayamak_from' );
			if ( $user && $pass && $from ) {
				$res = wp_remote_get(
					sprintf(
						'https://rest.payamak-panel.com/api/SendSMS/SendSMS?username=%s&password=%s&to=%s&from=%s&text=%s',
						rawurlencode( $user ),
						rawurlencode( $pass ),
						rawurlencode( $to ),
						rawurlencode( $from ),
						rawurlencode( $text )
					),
					array( 'timeout' => 20 )
				);
				$result = ! is_wp_error( $res ) && ( 200 === wp_remote_retrieve_response_code( $res ) );
			}
		} else {
			$key = get_option( 'gmx_kavenegar_key' );
			if ( $key ) {
				$res = wp_remote_get(
					sprintf(
						'https://api.kavenegar.com/v1/%s/sms/send.json?receptor=%s&message=%s',
						rawurlencode( $key ),
						rawurlencode( $to ),
						rawurlencode( $text )
					),
					array( 'timeout' => 20 )
				);
				$result = ! is_wp_error( $res ) && ( 200 === wp_remote_retrieve_response_code( $res ) );
			}
		}

		do_action( 'gmx_sms_sent', $to, $text, $result );

		return $result;
	}

	/**--------------------------------------------------------------
	 * ایمیل
	 *--------------------------------------------------------------*/

	/**
	 * ارسال ایمیل HTML ساده.
	 *
	 * @param string $to      مقصد.
	 * @param string $subject موضوع.
	 * @param string $heading عنوان بزرگ.
	 * @param string $body    متن.
	 * @return bool
	 */
	public static function email( $to, $subject, $heading, $body ) {
		$heading = (string) $heading;
		$body    = (string) $body;

		$html = '<!DOCTYPE html><html dir="rtl" lang="fa"><body style="font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right;background:#0d1017;color:#e6e8ee;padding:24px;">';
		$html .= '<div style="max-width:520px;margin:0 auto;background:#161a24;border:1px solid #262c3a;border-radius:12px;padding:28px;">';
		$html .= '<h2 style="color:#8b5cf6;margin:0 0 12px;font-size:18px;">' . esc_html( $heading ) . '</h2>';
		$html .= '<p style="line-height:1.9;margin:0 0 16px;">' . esc_html( $body ) . '</p>';
		$html .= '<p style="margin:0;"><a href="' . esc_url( home_url( '/my-account/' ) ) . '" style="display:inline-block;background:#8b5cf6;color:#fff;text-decoration:none;padding:10px 22px;border-radius:8px;">' . esc_html__( 'مشاهده داشبورد', 'gmx-market' ) . '</a></p>';
		$html .= '<p style="color:#8a91a5;font-size:12px;margin:18px 0 0;">' . esc_html( get_bloginfo( 'name' ) ) . '</p>';
		$html .= '</div></body></html>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		return wp_mail( $to, $subject, $html, $headers );
	}

	/**--------------------------------------------------------------
	 * نوتیف داخلی
	 *--------------------------------------------------------------*/

	/**
	 * ثبت نوتیفیکیشن داخلی.
	 *
	 * @param int    $user_id کاربر.
	 * @param string $title   عنوان.
	 * @param string $link    لینک.
	 */
	public static function push( $user_id, $title, $link = '' ) {
		global $wpdb;

		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::table() ) ) ) {
			return;
		}

		$wpdb->insert(
			self::table(),
			array(
				'user_id'    => (int) $user_id,
				'title'      => (string) $title,
				'link'       => (string) $link,
				'is_read'    => 0,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * نوتیف‌های کاربر.
	 *
	 * @param int $user_id کاربر.
	 * @param int $limit   تعداد.
	 * @return object[]
	 */
	public static function get_notifications( $user_id, $limit = 15 ) {
		global $wpdb;
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::table() ) ) ) {
			return array();
		}
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d', (int) $user_id, (int) $limit )
		);
	}

	/**--------------------------------------------------------------
	 * هوک‌ها
	 *--------------------------------------------------------------*/

	/**
	 * تغییر وضعیت سفارش.
	 *
	 * @param int    $order_id شناسه.
	 * @param string $from     از.
	 * @param string $to       به.
	 * @param int    $actor_id عامل.
	 */
	public function on_order_status_changed( $order_id, $from, $to, $actor_id ) {
		$order = GMX_Orders::get( $order_id );
		if ( ! $order ) {
			return;
		}

		$buyer      = get_userdata( $order->user_id );
		$seller_id  = (int) $order->seller_id;
		$status_lab = gmx_order_status_label( $to );

		// پیام به خریدار.
		if ( $buyer && (int) $buyer->ID !== (int) $actor_id ) {
			$title = sprintf( __( 'وضعیت سفارش %s: %s', 'gmx-market' ), $order->order_no, $status_lab );

			self::push( $buyer->ID, $title, home_url( '/my-account/orders/' ) );

			$mobile = get_user_meta( $buyer->ID, 'billing_phone', true );
			if ( $mobile ) {
				self::sms( $mobile, sprintf( '%s — %s', get_bloginfo( 'name' ), $title ) );
			}
			if ( $buyer->user_email ) {
				self::email( $buyer->user_email, $title, $status_lab, sprintf( __( 'وضعیت سفارش %s به «%s» تغییر کرد.', 'gmx-market' ), $order->order_no, $status_lab ) );
			}
		}

		// پیام به فروشنده.
		if ( $seller_id && $seller_id !== (int) $actor_id ) {
			$title2 = sprintf( __( 'سفارش %s: %s', 'gmx-market' ), $order->order_no, $status_lab );
			self::push( $seller_id, $title2, home_url( '/my-account/sales/' ) );
		}

		// پیام به مدیر.
		$admin_mobile = get_option( 'gmx_admin_mobile' );
		if ( $admin_mobile ) {
			self::sms( $admin_mobile, sprintf( '%s — سفارش %s: %s', get_bloginfo( 'name' ), $order->order_no, $status_lab ) );
		}
	}

	/**
	 * پیام چت جدید.
	 *
	 * @param int    $chat_id   شناسه گفتگو.
	 * @param int    $sender_id فرستنده.
	 * @param string $excerpt   خلاصه پیام.
	 */
	public function on_chat_message( $chat_id, $sender_id, $excerpt ) {
		$recipients = GMX_Chat::participants( $chat_id );
		foreach ( $recipients as $uid ) {
			if ( (int) $uid === (int) $sender_id ) {
				continue;
			}
			self::push( (int) $uid, sprintf( __( 'پیام جدید در گفتگو #%d', 'gmx-market' ), (int) $chat_id ), home_url( '/my-account/chat/' ) );
			$user = get_userdata( $uid );
			if ( $user && $user->user_email ) {
				self::email( $user->user_email, __( 'پیام جدید', 'gmx-market' ), __( 'پیام جدید', 'gmx-market' ), $excerpt );
			}
		}
	}

	/**
	 * پاسخ تیکت.
	 *
	 * @param int    $ticket_id شناسه تیکت.
	 * @param int    $sender_id فرستنده.
	 * @param string $excerpt   خلاصه.
	 */
	public function on_ticket_reply( $ticket_id, $sender_id, $excerpt ) {
		$ticket = GMX_Tickets::get( $ticket_id );
		if ( ! $ticket ) {
			return;
		}
		if ( (int) $ticket->user_id === (int) $sender_id ) {
			return; // پاسخ کاربر → نوتیف به پشتیبانی نمی‌خواهد.
		}
		$user = get_userdata( $ticket->user_id );
		if ( $user ) {
			self::push( $user->ID, sprintf( __( 'پاسخ جدید به تیکت #%d', 'gmx-market' ), (int) $ticket_id ), home_url( '/my-account/tickets/' ) );
			if ( $user->user_email ) {
				self::email( $user->user_email, __( 'پاسخ تیکت', 'gmx-market' ), __( 'پاسخ پشتیبانی', 'gmx-market' ), $excerpt );
			}
		}
	}
}
