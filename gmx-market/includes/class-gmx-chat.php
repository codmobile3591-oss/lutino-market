<?php
/**
 * چت داخلی خریدار/فروشنده.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس چت.
 */
class GMX_Chat {

	/**
	 * سازنده.
	 */
	public function __construct() {
		// اتصال چت به سفارش: هنگام پرداخت موفق، گفتگو بساز.
		add_action( 'gmx_order_status_changed', array( $this, 'maybe_create_order_chat' ), 10, 4 );
	}

	/**
	 * جدول گفتگوها.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_chats';
	}

	/**
	 * جدول پیام‌ها.
	 */
	public static function messages_table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_chat_messages';
	}

	/**
	 * یافتن یا ساختن گفتگو بین دو کاربر (اختیاری متصل به سفارش).
	 *
	 * @param int      $buyer_id  خریدار.
	 * @param int      $seller_id فروشنده.
	 * @param int|null $order_id  سفارش.
	 * @return int شناسه گفتگو
	 */
	public static function get_or_create( $buyer_id, $seller_id, $order_id = null ) {
		global $wpdb;

		$buyer_id  = (int) $buyer_id;
		$seller_id = (int) $seller_id;

		if ( $order_id ) {
			$existing = $wpdb->get_var(
				$wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE order_id = %d', (int) $order_id )
			);
			if ( $existing ) {
				return (int) $existing;
			}
		} else {
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM ' . self::table() . ' WHERE order_id = 0 AND ((buyer_id = %d AND seller_id = %d) OR (buyer_id = %d AND seller_id = %d))',
					$buyer_id,
					$seller_id,
					$seller_id,
					$buyer_id
				)
			);
			if ( $existing ) {
				return (int) $existing;
			}
		}

		$wpdb->insert(
			self::table(),
			array(
				'order_id'   => (int) $order_id,
				'buyer_id'   => min( $buyer_id, $seller_id ),
				'seller_id'  => max( $buyer_id, $seller_id ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * طرف‌های گفتگو.
	 *
	 * @param int $chat_id شناسه گفتگو.
	 * @return int[] [buyer_id, seller_id]
	 */
	public static function participants( $chat_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT buyer_id, seller_id FROM ' . self::table() . ' WHERE id = %d', (int) $chat_id ) );
		if ( ! $row ) {
			return array();
		}
		return array( (int) $row->buyer_id, (int) $row->seller_id );
	}

	/**
	 * آیا کاربر عضو گفتگوست؟
	 *
	 * @param int $chat_id شناسه.
	 * @param int $user_id کاربر.
	 * @return bool
	 */
	public static function is_participant( $chat_id, $user_id ) {
		$parts = self::participants( $chat_id );
		return in_array( (int) $user_id, array_map( 'intval', $parts ), true );
	}

	/**
	 * ارسال پیام.
	 *
	 * @param int    $chat_id   گفتگو.
	 * @param int    $sender_id فرستنده.
	 * @param string $body      متن.
	 * @param int    $attachment_id پیوست.
	 * @return int|WP_Error شناسه پیام
	 */
	public static function send( $chat_id, $sender_id, $body = '', $attachment_id = 0 ) {
		global $wpdb;

		$body = trim( wp_kses_post( (string) $body ) );
		$attachment_id = (int) $attachment_id;

		if ( '' === $body && ! $attachment_id ) {
			return new WP_Error( 'gmx_empty_message', __( 'پیام خالی است.', 'gmx-market' ) );
		}

		if ( ! self::is_participant( $chat_id, $sender_id ) ) {
			return new WP_Error( 'gmx_forbidden', __( 'به این گفتگو دسترسی ندارید.', 'gmx-market' ) );
		}

		$now = current_time( 'mysql' );

		$ok = $wpdb->insert(
			self::messages_table(),
			array(
				'chat_id'    => (int) $chat_id,
				'sender_id'  => (int) $sender_id,
				'body'       => $body,
				'attachment_id' => $attachment_id,
				'seen'       => 0,
				'created_at' => $now,
			),
			array( '%d', '%d', '%s', '%d', '%d', '%s' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'gmx_db_error', __( 'خطا در ارسال پیام.', 'gmx-market' ) );
		}

		$msg_id = (int) $wpdb->insert_id;

		// به‌روزرسانی گفتگو و شمارنده خوانده‌نشده.
		$parts    = self::participants( $chat_id );
		$is_buyer = isset( $parts[0] ) && (int) $parts[0] === (int) $sender_id;

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::table() . ' SET last_message_at = %s, ' . ( $is_buyer ? 'seller_unread = seller_unread + 1' : 'buyer_unread = buyer_unread + 1' ) . ' WHERE id = %d',
				$now,
				(int) $chat_id
			)
		);

		$excerpt = $body ? wp_trim_words( wp_strip_all_tags( $body ), 12 ) : __( '📎 فایل پیوست', 'gmx-market' );

		do_action( 'gmx_chat_new_message', $chat_id, $sender_id, $excerpt );

		return $msg_id;
	}

	/**
	 * پیام‌های گفتگو.
	 *
	 * @param int $chat_id شناسه.
	 * @param int $after_id فقط پیام‌های بعد از این شناسه.
	 * @param int $limit تعداد.
	 * @return object[]
	 */
	public static function get_messages( $chat_id, $after_id = 0, $limit = 100 ) {
		global $wpdb;

		if ( $after_id ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM ' . self::messages_table() . ' WHERE chat_id = %d AND id > %d ORDER BY id ASC LIMIT %d',
					(int) $chat_id,
					(int) $after_id,
					(int) $limit
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM (SELECT * FROM ' . self::messages_table() . ' WHERE chat_id = %d ORDER BY id DESC LIMIT %d) t ORDER BY id ASC',
				(int) $chat_id,
				(int) $limit
			)
		);
	}

	/**
	 * علامت‌گذاری خوانده‌شده.
	 *
	 * @param int $chat_id گفتگو.
	 * @param int $user_id خواننده.
	 * @return int تعداد پیام خوانده‌شده
	 */
	public static function mark_seen( $chat_id, $user_id ) {
		global $wpdb;

		$parts    = self::participants( $chat_id );
		$is_buyer = isset( $parts[0] ) && (int) $parts[0] === (int) $user_id;

		// پیام‌های طرف مقابل را خوانده‌شده کن.
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::messages_table() . ' SET seen = 1 WHERE chat_id = %d AND sender_id != %d AND seen = 0',
				(int) $chat_id,
				(int) $user_id
			)
		);

		$col = $is_buyer ? 'buyer_unread' : 'seller_unread';
		$wpdb->query(
			$wpdb->prepare( 'UPDATE ' . self::table() . " SET {$col} = 0 WHERE id = %d", (int) $chat_id )
		);

		return 1;
	}

	/**
	 * ساخت گفتگو خودکار هنگام پرداخت سفارش.
	 *
	 * @param int    $order_id سفارش.
	 * @param string $from     از.
	 * @param string $to       به.
	 */
	public function maybe_create_order_chat( $order_id, $from, $to ) {
		if ( ! in_array( $to, array( 'paid', 'processing', 'delivered' ), true ) ) {
			return;
		}

		$order = GMX_Orders::get( $order_id );
		if ( ! $order || ! (int) $order->seller_id ) {
			return;
		}

		$chat_id = self::get_or_create( (int) $order->user_id, (int) $order->seller_id, (int) $order_id );

		do_action( 'gmx_order_chat_ready', $chat_id, $order_id );
	}

	/**
	 * گفتگوهای کاربر.
	 *
	 * @param int $user_id کاربر.
	 * @return object[]
	 */
	public static function get_user_chats( $user_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE buyer_id = %d OR seller_id = %d ORDER BY COALESCE(last_message_at, created_at) DESC',
				(int) $user_id,
				(int) $user_id
			)
		);
	}

	/**--------------------------------------------------------------
	 * REST API
	 *--------------------------------------------------------------*/

	/**
	 * ثبت مسیرهای REST.
	 */
	public function register_rest() {
		register_rest_route(
			'gmx/v1',
			'/chat/messages',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_messages' ),
				'permission_callback' => array( $this, 'rest_can_access_chat' ),
				'args'                => array(
					'chat_id'  => array( 'required' => true, 'type' => 'integer' ),
					'after_id' => array( 'required' => false, 'type' => 'integer', 'default' => 0 ),
				),
			)
		);

		register_rest_route(
			'gmx/v1',
			'/chat/send',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_send_message' ),
				'permission_callback' => array( $this, 'rest_can_access_chat' ),
			)
		);

		register_rest_route(
			'gmx/v1',
			'/chat/seen',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_mark_seen' ),
				'permission_callback' => array( $this, 'rest_can_access_chat' ),
			)
		);
	}

	/**
	 * دسترسی به گفتگو.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return bool|WP_Error
	 */
	public function rest_can_access_chat( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'gmx_unauthorized', __( 'وارد شوید.', 'gmx-market' ), array( 'status' => 401 ) );
		}

		$chat_id = (int) $request->get_param( 'chat_id' );
		if ( $chat_id && ! self::is_participant( $chat_id, get_current_user_id() ) ) {
			return new WP_Error( 'gmx_forbidden', __( 'دسترسی غیرمجاز.', 'gmx-market' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * دریافت پیام‌ها.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	public function rest_get_messages( $request ) {
		$chat_id  = (int) $request->get_param( 'chat_id' );
		$after_id = (int) $request->get_param( 'after_id' );
		$messages = self::get_messages( $chat_id, $after_id );

		$out = array();
		foreach ( $messages as $m ) {
			$out[] = array(
				'id'        => (int) $m->id,
				'sender'    => (int) $m->sender_id,
				'body'      => (string) $m->body,
				'attach'    => $m->attachment_id ? GMX_Uploads::attachment_html( (int) $m->attachment_id ) : '',
				'seen'      => (int) $m->seen,
				'time'      => gmx_date( $m->created_at ),
				'time_raw'  => $m->created_at,
			);
		}

		return rest_ensure_response( array( 'messages' => $out ) );
	}

	/**
	 * ارسال پیام (متن + اختیاری فایل).
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_send_message( $request ) {
		$chat_id = (int) $request->get_param( 'chat_id' );
		$body    = (string) $request->get_param( 'body' );
		$attach  = 0;

		// آپلود فایل در صورت وجود.
		$files = $request->get_file_params();
		if ( ! empty( $files['file'] ) ) {
			$stored = GMX_Uploads::store( 'chat', 'file' );
			if ( is_wp_error( $stored ) ) {
				return $stored;
			}
			global $wpdb;
			$attach = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . 'gmx_files WHERE file_key = %s', $stored['key'] )
			);
		}

		$msg_id = self::send( $chat_id, get_current_user_id(), $body, $attach );

		if ( is_wp_error( $msg_id ) ) {
			return $msg_id;
		}

		return rest_ensure_response( array( 'ok' => true, 'message_id' => $msg_id ) );
	}

	/**
	 * علامت‌گذاری خوانده‌شده.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	public function rest_mark_seen( $request ) {
		$chat_id = (int) $request->get_param( 'chat_id' );
		self::mark_seen( $chat_id, get_current_user_id() );
		return rest_ensure_response( array( 'ok' => true ) );
	}
}
