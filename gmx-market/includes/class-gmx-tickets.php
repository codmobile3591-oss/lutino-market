<?php
/**
 * سیستم تیکت پشتیبانی.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس تیکت.
 */
class GMX_Tickets {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_form_posts' ) );
	}

	/**
	 * جدول تیکت‌ها.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_tickets';
	}

	/**
	 * جدول پیام‌های تیکت.
	 */
	public static function messages_table() {
		global $wpdb;
		return $wpdb->prefix . 'gmx_ticket_messages';
	}

	/**
	 * دسته‌بندی‌های تیکت.
	 *
	 * @return array<string,string> slug => عنوان
	 */
	public static function categories() {
		$raw = (string) get_option( 'gmx_ticket_categories', 'general|عمومی,order|سفارشات,payment|پرداخت,technical|فنی,report|گزارش تخلف' );
		$out = array();
		foreach ( explode( ',', $raw ) as $pair ) {
			$parts = explode( '|', trim( $pair ) );
			if ( 2 === count( $parts ) ) {
				$out[ sanitize_key( $parts[0] ) ] = sanitize_text_field( $parts[1] );
			}
		}
		return $out;
	}

	/**
	 * وضعیت‌های تیکت.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			'open'     => __( 'باز', 'gmx-market' ),
			'answered' => __( 'پاسخ داده شده', 'gmx-market' ),
			'pending'  => __( 'در انتظار کاربر', 'gmx-market' ),
			'closed'   => __( 'بسته شده', 'gmx-market' ),
		);
	}

	/**
	 * ایجاد تیکت.
	 *
	 * @param int    $user_id  کاربر.
	 * @param string $category دسته.
	 * @param string $subject  موضوع.
	 * @param string $body     متن.
	 * @param int    $order_id سفارش مرتبط.
	 * @param int    $attachment_id پیوست.
	 * @return int|WP_Error
	 */
	public static function create( $user_id, $category, $subject, $body, $order_id = 0, $attachment_id = 0 ) {
		global $wpdb;

		$subject = sanitize_text_field( $subject );
		$body    = trim( wp_kses_post( $body ) );

		if ( ! $subject || ! $body ) {
			return new WP_Error( 'gmx_ticket_fields', __( 'موضوع و متن تیکت الزامی است.', 'gmx-market' ) );
		}

		$cats = array_keys( self::categories() );
		if ( ! in_array( $category, $cats, true ) ) {
			$category = 'general';
		}

		$now = current_time( 'mysql' );

		$ok = $wpdb->insert(
			self::table(),
			array(
				'ticket_no' => 'TK-' . strtoupper( wp_generate_password( 6, false, false ) ),
				'user_id'   => (int) $user_id,
				'order_id'  => (int) $order_id,
				'category'  => sanitize_key( $category ),
				'subject'   => $subject,
				'status'    => 'open',
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'gmx_db_error', __( 'خطا در ثبت تیکت.', 'gmx-market' ) );
		}

		$ticket_id = (int) $wpdb->insert_id;

		self::add_message( $ticket_id, $user_id, $body, $attachment_id );

		do_action( 'gmx_ticket_created', $ticket_id );

		return $ticket_id;
	}

	/**
	 * افزودن پیام به تیکت.
	 *
	 * @param int    $ticket_id تیکت.
	 * @param int    $sender_id فرستنده.
	 * @param string $body      متن.
	 * @param int    $attachment_id پیوست.
	 * @return int|WP_Error
	 */
	public static function add_message( $ticket_id, $sender_id, $body, $attachment_id = 0 ) {
		global $wpdb;

		$ticket = self::get( $ticket_id );
		if ( ! $ticket ) {
			return new WP_Error( 'gmx_not_found', __( 'تیکت یافت نشد.', 'gmx-market' ) );
		}

		$is_staff = current_user_can( 'gmx_manage_tickets' ) || current_user_can( 'manage_options' );
		$is_owner = ( (int) $ticket->user_id === (int) $sender_id );

		if ( ! $is_staff && ! $is_owner ) {
			return new WP_Error( 'gmx_forbidden', __( 'دسترسی غیرمجاز.', 'gmx-market' ) );
		}

		if ( 'closed' === $ticket->status && ! $is_staff ) {
			return new WP_Error( 'gmx_closed', __( 'این تیکت بسته شده است.', 'gmx-market' ) );
		}

		$body = trim( wp_kses_post( (string) $body ) );
		if ( ! $body && ! $attachment_id ) {
			return new WP_Error( 'gmx_empty_message', __( 'پیام خالی است.', 'gmx-market' ) );
		}

		$ok = $wpdb->insert(
			self::messages_table(),
			array(
				'ticket_id'   => (int) $ticket_id,
				'sender_id'   => (int) $sender_id,
				'body'        => $body,
				'attachment_id' => (int) $attachment_id,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%s' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'gmx_db_error', __( 'خطا در ثبت پیام.', 'gmx-market' ) );
		}

		// وضعیت: پاسخ کاربر → باز؛ پاسخ پشتیبان → پاسخ داده شده.
		$new_status = $is_staff ? 'answered' : 'open';
		$wpdb->update(
			self::table(),
			array( 'status' => $new_status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $ticket_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$excerpt = $body ? wp_trim_words( wp_strip_all_tags( $body ), 15 ) : __( '📎 فایل پیوست', 'gmx-market' );

		do_action( 'gmx_ticket_replied', $ticket_id, $sender_id, $excerpt );

		return (int) $wpdb->insert_id;
	}

	/**
	 * تغییر وضعیت تیکت.
	 *
	 * @param int    $ticket_id تیکت.
	 * @param string $status    وضعیت جدید.
	 * @return true|WP_Error
	 */
	public static function set_status( $ticket_id, $status ) {
		global $wpdb;
		if ( ! array_key_exists( $status, self::statuses() ) ) {
			return new WP_Error( 'gmx_bad_status', __( 'وضعیت نامعتبر است.', 'gmx-market' ) );
		}
		$wpdb->update(
			self::table(),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $ticket_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		return true;
	}

	/**
	 * دریافت تیکت.
	 *
	 * @param int $ticket_id شناسه.
	 * @return object|null
	 */
	public static function get( $ticket_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $ticket_id ) );
	}

	/**
	 * پیام‌های تیکت.
	 *
	 * @param int $ticket_id تیکت.
	 * @return object[]
	 */
	public static function get_messages( $ticket_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::messages_table() . ' WHERE ticket_id = %d ORDER BY id ASC', (int) $ticket_id )
		);
	}

	/**
	 * تیکت‌های کاربر.
	 *
	 * @param int $user_id کاربر.
	 * @return object[]
	 */
	public static function get_user_tickets( $user_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE user_id = %d ORDER BY id DESC', (int) $user_id )
		);
	}

	/**
	 * همه تیکت‌ها (مدیریت).
	 *
	 * @param string $status فیلتر وضعیت.
	 * @return object[]
	 */
	public static function get_all( $status = '' ) {
		global $wpdb;
		if ( $status ) {
			return $wpdb->get_results(
				$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE status = %s ORDER BY id DESC', $status )
			);
		}
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC' );
	}

	/**
	 * آیا کاربر به تیکت دسترسی دارد؟
	 *
	 * @param object $ticket  تیکت.
	 * @param int    $user_id کاربر.
	 * @return bool
	 */
	public static function user_can_view( $ticket, $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		return ( (int) $ticket->user_id === $user_id ) || current_user_can( 'gmx_manage_tickets' ) || current_user_can( 'manage_options' );
	}

	/**--------------------------------------------------------------
	 * فرم‌های POST ساده (غیر REST)
	 *--------------------------------------------------------------*/

	/**
	 * پردازش فرم‌های تیکت.
	 */
	public function handle_form_posts() {
		if ( empty( $_POST['gmx_ticket_action'] ) ) {
			return;
		}

		$action = sanitize_key( $_POST['gmx_ticket_action'] );

		if ( ! isset( $_POST['gmx_ticket_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['gmx_ticket_nonce'] ), 'gmx_ticket_' . $action ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$redirect = home_url( '/my-account/tickets/' );

		if ( 'new' === $action ) {
			$attach = 0;
			$files  = $_FILES;
			if ( ! empty( $files['gmx_ticket_file']['name'] ) ) {
				$stored = GMX_Uploads::store( 'ticket', 'gmx_ticket_file' );
				if ( ! is_wp_error( $stored ) ) {
					$attach = (int) $GLOBALS['wpdb']->get_var(
						$GLOBALS['wpdb']->prepare( 'SELECT id FROM ' . $GLOBALS['wpdb']->prefix . 'gmx_files WHERE file_key = %s', $stored['key'] )
					);
				}
			}

			$result = self::create(
				get_current_user_id(),
				sanitize_key( $_POST['category'] ?? 'general' ),
				$_POST['subject'] ?? '',
				$_POST['body'] ?? '',
				(int) ( $_POST['order_id'] ?? 0 ),
				$attach
			);

			if ( is_wp_error( $result ) ) {
				set_transient( 'gmx_ticket_error_' . get_current_user_id(), $result->get_error_message(), 60 );
			} else {
				$redirect = add_query_arg( 'ticket', $result, home_url( '/my-account/tickets/' ) );
			}
		}

		if ( 'reply' === $action ) {
			$ticket_id = (int) ( $_POST['ticket_id'] ?? 0 );
			$ticket    = self::get( $ticket_id );

			if ( $ticket && self::user_can_view( $ticket ) ) {
				$attach = 0;
				$files  = $_FILES;
				if ( ! empty( $files['gmx_ticket_file']['name'] ) ) {
					$stored = GMX_Uploads::store( 'ticket', 'gmx_ticket_file' );
					if ( ! is_wp_error( $stored ) ) {
						$attach = (int) $GLOBALS['wpdb']->get_var(
							$GLOBALS['wpdb']->prepare( 'SELECT id FROM ' . $GLOBALS['wpdb']->prefix . 'gmx_files WHERE file_key = %s', $stored['key'] )
						);
					}
				}

				self::add_message( $ticket_id, get_current_user_id(), $_POST['body'] ?? '', $attach );
			}

			$redirect = add_query_arg( 'ticket', $ticket_id, home_url( '/my-account/tickets/' ) );
		}

		wp_safe_redirect( $redirect );
		exit;
	}
}
