<?php
/**
 * آپلود امن و سرو فایل خصوصی.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس آپلودها.
 */
class GMX_Uploads {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_serve_file' ), 1 );
		add_filter( 'upload_mimes', array( $this, 'restrict_mimes' ), 100 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'limit_size' ) );
	}

	/**
	 * پوشه خصوصی آپلودها.
	 *
	 * @return string
	 */
	public static function private_dir() {
		$dir = wp_get_upload_dir();
		return trailingslashit( $dir['basedir'] ) . 'gmx-private';
	}

	/**
	 * قوانین بازنویسی.
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^gmx-file/([a-z0-9]+)$', 'index.php?gmx_file=$matches[1]', 'top' );
	}

	/**
	 * متغیرهای کوئری.
	 *
	 * @param array $vars متغیرها.
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'gmx_file';
		return $vars;
	}

	/**
	 * ذخیره امن فایل آپلودی کاربر.
	 *
	 * @param string $context  chat|ticket.
	 * @param string $field    نام فیلد فایل در $_FILES.
	 * @return array|WP_Error [id, url, name, size, mime]
	 */
	public static function store( $context, $field ) {
		if ( empty( $_FILES[ $field ] ) || ! isset( $_FILES[ $field ]['tmp_name'] ) ) {
			return new WP_Error( 'gmx_no_file', __( 'فایلی ارسال نشده است.', 'gmx-market' ) );
		}

		$file = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( ! empty( $file['error'] ) ) {
			return new WP_Error( 'gmx_upload_error', __( 'خطا در آپلود فایل.', 'gmx-market' ) );
		}

		// بررسی نوع مجاز.
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		$mime  = isset( $check['type'] ) ? $check['type'] : '';
		$name  = sanitize_file_name( $file['name'] );

		$allowed = array(
			'image/jpeg', 'image/png', 'image/gif', 'image/webp',
			'application/pdf', 'application/zip',
			'text/plain',
		);

		if ( ! $mime || ! in_array( $mime, $allowed, true ) ) {
			return new WP_Error( 'gmx_bad_type', __( 'فرمت فایل مجاز نیست (فقط تصویر، PDF، ZIP و TXT).', 'gmx-market' ) );
		}

		// محدودیت حجم.
		$max_mb = (int) get_option( 'gmx_chat_upload_max', 4 );
		if ( (int) $file['size'] > $max_mb * 1024 * 1024 ) {
			return new WP_Error( 'gmx_too_large', sprintf( __( 'حداکثر حجم فایل %d مگابایت است.', 'gmx-market' ), $max_mb ) );
		}

		// انتقال به پوشه خصوصی.
		$dir = self::private_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'gmx_mkdir', __( 'خطا در ساخت پوشه آپلود.', 'gmx-market' ) );
		}

		$ext      = pathinfo( $name, PATHINFO_EXTENSION );
		$filename = gmdate( 'Ymd' ) . '-' . strtolower( wp_generate_password( 16, false, false ) ) . '.' . $ext;
		$dest     = $dir . '/' . $filename;

		if ( ! @move_uploaded_file( $file['tmp_name'], $dest ) ) {
			return new WP_Error( 'gmx_move', __( 'ذخیره فایل ناموفق بود.', 'gmx-market' ) );
		}
		@chmod( $dest, 0640 );

		// ثبت در جدول فایل‌ها.
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'gmx_files',
			array(
				'file_key'   => pathinfo( $filename, PATHINFO_FILENAME ),
				'user_id'    => get_current_user_id(),
				'context'    => sanitize_key( $context ),
				'orig_name'  => $name,
				'mime'       => $mime,
				'size'       => (int) $file['size'],
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return array(
			'key'  => pathinfo( $filename, PATHINFO_FILENAME ),
			'name' => $name,
			'mime' => $mime,
			'size' => (int) $file['size'],
		);
	}

	/**
	 * سرو فایل خصوصی با کنترل دسترسی.
	 */
	public function maybe_serve_file() {
		$key = get_query_var( 'gmx_file' );
		if ( ! $key ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'gmx-market' ), 403 );
		}

		global $wpdb;
		$file = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'gmx_files WHERE file_key = %s', $key )
		);

		if ( ! $file ) {
			wp_die( esc_html__( 'فایل یافت نشد.', 'gmx-market' ), 404 );
		}

		// کنترل دسترسی: مالک، طرف گفتگو/تیکت، یا مدیر.
		$can = ( (int) $file->user_id === get_current_user_id() ) || current_user_can( 'manage_options' );

		if ( ! $can ) {
			// مالکیت غیرمستقیم: پیوست چت یا تیکت.
			$chat = $wpdb->get_var(
				$wpdb->prepare( 'SELECT chat_id FROM ' . $wpdb->prefix . 'gmx_chat_messages WHERE attachment_id = %d LIMIT 1', (int) $file->id )
			);
			if ( $chat ) {
				$can = in_array( (int) get_current_user_id(), array_map( 'intval', GMX_Chat::participants( (int) $chat ) ), true );
			}
			if ( ! $can ) {
				$ticket = $wpdb->get_var(
					$wpdb->prepare( 'SELECT ticket_id FROM ' . $wpdb->prefix . 'gmx_ticket_messages WHERE attachment_id = %d LIMIT 1', (int) $file->id )
				);
				if ( $ticket ) {
					$t = GMX_Tickets::get( (int) $ticket );
					$can = $t && ( ( (int) $t->user_id === get_current_user_id() ) || current_user_can( 'gmx_manage_tickets' ) );
				}
			}
		}

		if ( ! $can ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'gmx-market' ), 403 );
		}

		$path = self::private_dir() . '/' . $file->file_key . '.' . pathinfo( $file->orig_name, PATHINFO_EXTENSION );
		if ( ! file_exists( $path ) ) {
			wp_die( esc_html__( 'فایل یافت نشد.', 'gmx-market' ), 404 );
		}

		$in_browser = 0 === strpos( (string) $file->mime, 'image/' ) || 'application/pdf' === $file->mime;

		header( 'Content-Type: ' . $file->mime );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Disposition: ' . ( $in_browser ? 'inline' : 'attachment' ) . '; filename="' . rawurlencode( $file->orig_name ) . '"' );
		header( 'Cache-Control: private, max-age=86400' );
		readfile( $path );
		exit;
	}

	/**
	 * خروجی HTML پیوست.
	 *
	 * @param int $attachment_id شناسه فایل.
	 * @return string
	 */
	public static function attachment_html( $attachment_id ) {
		global $wpdb;
		$file = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'gmx_files WHERE id = %d', (int) $attachment_id ) );
		if ( ! $file ) {
			return '';
		}

		$url = home_url( '/gmx-file/' . $file->file_key );

		if ( 0 === strpos( (string) $file->mime, 'image/' ) ) {
			return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener"><img class="gmx-attachment-image" src="' . esc_url( $url ) . '" alt="' . esc_attr( $file->orig_name ) . '" loading="lazy" /></a>';
		}

		$size_kb = round( (int) $file->size / 1024 );
		return '<a class="gmx-attachment-file" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">📎 ' . esc_html( $file->orig_name ) . ' <span>(' . esc_html( gmx_fa_num( $size_kb ) ) . ' KB)</span></a>';
	}

	/**
	 * محدود کردن فرمت‌های رسانه.
	 *
	 * @param array $mimes فرمت‌ها.
	 * @return array
	 */
	public function restrict_mimes( $mimes ) {
		return array(
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'gif'      => 'image/gif',
			'webp'     => 'image/webp',
			'pdf'      => 'application/pdf',
			'zip'      => 'application/zip',
			'txt'      => 'text/plain',
		);
	}

	/**
	 * محدودیت حجم کلی.
	 *
	 * @param array $file فایل.
	 * @return array
	 */
	public function limit_size( $file ) {
		$max = (int) get_option( 'gmx_chat_upload_max', 4 ) * 1024 * 1024;
		if ( isset( $file['size'] ) && $file['size'] > $max ) {
			$file['error'] = sprintf( __( 'حداکثر حجم فایل %d مگابایت است.', 'gmx-market' ), (int) get_option( 'gmx_chat_upload_max', 4 ) );
		}
		return $file;
	}
}
