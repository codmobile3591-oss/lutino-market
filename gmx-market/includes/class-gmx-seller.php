<?php
/**
 * ماژول فروشنده: آمار و مدیریت محصول از فرانت‌اند.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس فروشنده.
 */
class GMX_Seller {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_apply_form' ) );
		add_action( 'init', array( $this, 'handle_product_form' ) );
		add_action( 'init', array( $this, 'handle_profile_form' ) );
	}

	/**
	 * پردازش فرم ویرایش اطلاعات حساب.
	 */
	public function handle_profile_form() {
		if ( empty( $_POST['gmx_profile_action'] ) ) {
			return;
		}

		if ( ! isset( $_POST['gmx_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['gmx_profile_nonce'] ), 'gmx_profile_save' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// نام نمایشی.
		$display = sanitize_text_field( $_POST['display_name'] ?? '' );
		if ( $display ) {
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $display,
				)
			);
		}

		// ایمیل (با بررسی یکتا بودن).
		$email = sanitize_email( $_POST['user_email'] ?? '' );
		if ( $email && is_email( $email ) ) {
			$exists = email_exists( $email );
			if ( ! $exists || (int) $exists === $user_id ) {
				wp_update_user(
					array(
						'ID'         => $user_id,
						'user_email' => $email,
					)
				);
			}
		}

		// موبایل.
		$phone = preg_replace( '/[^0-9]/', '', (string) ( $_POST['billing_phone'] ?? '' ) );
		update_user_meta( $user_id, 'billing_phone', $phone );

		// رمز عبور (اختیاری).
		if ( ! empty( $_POST['user_pass'] ) ) {
			wp_set_password( (string) $_POST['user_pass'], $user_id );
			wp_safe_redirect( wp_login_url( home_url( '/my-account/profile/' ) ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'saved', 1, home_url( '/my-account/profile/' ) ) );
		exit;
	}

	/**
	 * آمار فروش فروشنده.
	 *
	 * @param int $seller_id شناسه فروشنده.
	 * @return object {count, revenue, pending}
	 */
	public static function stats( $seller_id ) {
		global $wpdb;
		$table = GMX_Orders::table();

		$seller_id = (int) $seller_id;

		$count = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE seller_id = %d AND status IN ('paid','processing','delivered','completed')", $seller_id )
		);

		$revenue = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(total - commission),0) FROM {$table} WHERE seller_id = %d AND status = 'completed'",
				$seller_id
			)
		);

		$pending = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE seller_id = %d AND status IN ('paid','processing')", $seller_id )
		);

		return (object) compact( 'count', 'revenue', 'pending' );
	}

	/**
	 * آیا کاربر فروشنده است؟
	 *
	 * @param int $user_id کاربر.
	 * @return bool
	 */
	public static function is_seller( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		$user = get_userdata( $user_id );
		return $user && ( in_array( 'gmx_seller', (array) $user->roles, true ) || in_array( 'administrator', (array) $user->roles, true ) );
	}

	/**
	 * پردازش درخواست فروشنده شدن.
	 */
	public function handle_apply_form() {
		if ( empty( $_POST['gmx_seller_apply'] ) ) {
			return;
		}

		if ( ! isset( $_POST['gmx_seller_apply_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['gmx_seller_apply_nonce'] ), 'gmx_seller_apply' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id || self::is_seller( $user_id ) ) {
			return;
		}

		$shop = sanitize_text_field( $_POST['shop_name'] ?? '' );

		$user = new WP_User( $user_id );
		$user->add_role( 'gmx_seller' );
		if ( $shop ) {
			update_user_meta( $user_id, 'gmx_shop_name', $shop );
		}
		if ( ! empty( $_POST['shop_phone'] ) ) {
			update_user_meta( $user_id, 'gmx_shop_phone', preg_replace( '/[^0-9]/', '', (string) $_POST['shop_phone'] ) );
		}

		set_transient(
			'gmx_seller_ok_' . $user_id,
			$shop
				? sprintf( __( 'فروشگاه «%s» فعال شد! همین حالا اولین آگهی‌ات را ثبت کن.', 'gmx-market' ), $shop )
				: __( 'حساب فروشنده‌ات فعال شد! همین حالا اولین آگهی‌ات را ثبت کن.', 'gmx-market' ),
			60
		);
		wp_safe_redirect( home_url( '/my-account/seller/' ) );
		exit;
	}

	/**
	 * پردازش فرم ثبت/ویرایش آگهی.
	 */
	public function handle_product_form() {
		if ( empty( $_POST['gmx_seller_product'] ) ) {
			return;
		}

		if ( ! isset( $_POST['gmx_seller_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['gmx_seller_nonce'] ), 'gmx_seller_product' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id || ! self::is_seller( $user_id ) ) {
			return;
		}

		$title    = sanitize_text_field( $_POST['title'] ?? '' );
		$desc     = wp_kses_post( $_POST['description'] ?? '' );
		$price    = gmx_to_int( $_POST['price'] ?? 0 );
		$type     = sanitize_key( $_POST['product_type'] ?? 'item' );
		$creds    = sanitize_textarea_field( $_POST['credentials'] ?? '' );
		$stock    = gmx_to_int( $_POST['stock'] ?? 0 );
		$dtime    = sanitize_text_field( $_POST['delivery_time'] ?? '' );
		$game_id  = gmx_to_int( $_POST['game'] ?? 0 );
		$edit_id  = gmx_to_int( $_POST['edit_id'] ?? 0 );
		$editpost = $edit_id ? get_post( $edit_id ) : null;

		// فقط آگهی خودش قابل ویرایش است.
		if ( $editpost && (int) $editpost->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
			$editpost = null;
			$edit_id  = 0;
		}

		if ( ! $title || $price <= 0 ) {
			set_transient( 'gmx_seller_err_' . $user_id, __( 'عنوان و قیمت الزامی است.', 'gmx-market' ), 60 );
			wp_safe_redirect( home_url( '/my-account/seller/' ) );
			exit;
		}

		$status = 'pending'; // آگهی جدید نیاز به تایید مدیر دارد.
		if ( $editpost ) {
			$status = $editpost->post_status;
			if ( 'publish' === $status ) {
				$status = 'pending'; // ویرایش مجدد نیاز به تایید دارد.
			}
		}

		$post_id = wp_insert_post(
			array(
				'ID'           => $editpost ? $editpost->ID : 0,
				'post_title'   => $title,
				'post_content' => $desc,
				'post_status'  => $status,
				'post_type'    => 'gmx_product',
				'post_author'  => $user_id,
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_gmx_price', $price );
			update_post_meta( $post_id, '_gmx_stock', $stock );
			update_post_meta( $post_id, '_gmx_delivery_time', $dtime );
			update_post_meta( $post_id, '_gmx_auto_delivery', $creds ? '1' : '' );
			update_post_meta( $post_id, '_gmx_credentials', $creds );				if ( in_array( $type, array( 'item', 'gem', 'account', 'service', 'gift_card' ), true ) ) {
				wp_set_object_terms( $post_id, $type, 'gmx_product_type' );
			}
			if ( $game_id ) {
				wp_set_object_terms( $post_id, array( (int) $game_id ), 'gmx_game' );
			}

			// تصویر شاخص.
			if ( ! empty( $_FILES['image']['name'] ) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$att_id = media_handle_upload( 'image', $post_id );
				if ( ! is_wp_error( $att_id ) ) {
					set_post_thumbnail( $post_id, $att_id );
				}
			}

			set_transient(
				'gmx_seller_ok_' . $user_id,
				$editpost
					? __( 'آگهی ویرایش شد و پس از تایید مجدد مدیر منتشر می‌شود.', 'gmx-market' )
					: __( 'آگهی ثبت شد و پس از تایید مدیر نمایش داده می‌شود.', 'gmx-market' ),
				60
			);
		}

		wp_safe_redirect( home_url( '/my-account/seller/' ) );
		exit;
	}
}
