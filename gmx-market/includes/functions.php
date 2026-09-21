<?php
/**
 * توابع کمکی GMX.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * نمایش قیمت قالب‌بندی‌شده.
 *
 * @param float $amount مبلغ به تومان.
 * @return string
 */
if ( ! function_exists( 'gmx_price' ) ) {
	function gmx_price( $amount ) {
		$unit = get_option( 'gmx_currency_unit', 'تومان' );
		$dec  = (int) get_option( 'gmx_currency_decimals', 0 );
		return number_format( (float) $amount, $dec ) . ' ' . $unit;
	}
}

/**
 * تبدیل ورودی فرم به عدد.
 *
 * @param mixed $value مقدار خام.
 * @return int
 */
function gmx_to_int( $value ) {
	$value = preg_replace( '/[^0-9]/', '', (string) $value );
	return '' === $value ? 0 : (int) $value;
}

/**
 * شناسه کاربر فعلی.
 *
 * @return int
 */
function gmx_user_id() {
	return get_current_user_id();
}

/**
 * بررسی ورود کاربر؛ در غیر این‌صورت هدایت به صفحه ورود.
 *
 * @return bool
 */
function gmx_require_login() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( home_url( '/my-account/' ) ) );
		exit;
	}
	return true;
}

/**
 * نام نمایشی کاربر.
 *
 * @param int $user_id شناسه کاربر.
 * @return string
 */
function gmx_display_name( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return __( 'کاربر حذف‌شده', 'gmx-market' );
	}
	return $user->display_name ? $user->display_name : $user->user_login;
}

/**
 * نام فروشگاه فروشنده — اگر نداشت، نام خودش.
 *
 * @param int $user_id شناسه فروشنده.
 * @return string
 */
function gmx_shop_name( $user_id ) {
	$shop = get_user_meta( $user_id, 'gmx_shop_name', true );
	return $shop ? $shop : gmx_display_name( $user_id );
}

/**
 * خواندن تنظیم با مقدار پیش‌فرض (خالی = پیش‌فرض).
 *
 * @param string $key      کلید آپشن.
 * @param string $default  پیش‌فرض.
 * @return string
 */
function gmx_opt( $key, $default = '' ) {
	$value = get_option( $key, '' );
	return ( '' === $value || null === $value ) ? $default : $value;
}

/**
 * آواتار کاربر.
 *
 * @param int $user_id شناسه کاربر.
 * @param int $size    اندازه.
 * @return string
 */
function gmx_avatar( $user_id, $size = 48 ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return '';
	}
	$url = get_avatar_url( $user->user_email, array( 'size' => $size ) );
	return '<img class="gmx-avatar" src="' . esc_url( $url ) . '" width="' . (int) $size . '" height="' . (int) $size . '" alt="" loading="lazy" />';
}

/**--------------------------------------------------------------
 * تاریخ جلالی (شمسی)
 *--------------------------------------------------------------*/

/**
 * تبدیل میلادی به جلالی.
 *
 * @param int $gy سال میلادی.
 * @param int $gm ماه.
 * @param int $gd روز.
 * @return int[]
 */
function gmx_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
	$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days  = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
	$jy    = -1595 + ( 33 * (int) ( $days / 12053 ) );
	$days %= 12053;
	$jy   += 4 * (int) ( $days / 1461 );
	$days %= 1461;
	if ( $days > 365 ) {
		$jy  += (int) ( ( $days - 1 ) / 365 );
		$days = ( $days - 1 ) % 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + (int) ( $days / 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + (int) ( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}
	return array( $jy, $jm, $jd );
}

/**
 * تاریخ شمسی قالب‌بندی‌شده.
 *
 * @param int|string|null $time      زمان.
 * @param bool            $with_time نمایش ساعت.
 * @return string
 */
if ( ! function_exists( 'gmx_date' ) ) {
	function gmx_date( $time = null, $with_time = true ) {
		if ( null === $time ) {
			$ts = current_time( 'timestamp' );
		} elseif ( is_numeric( $time ) ) {
			$ts = (int) $time;
		} else {
			$ts = strtotime( (string) $time );
		}
		if ( ! $ts ) {
			return '';
		}
		$off = (float) get_option( 'gmt_offset', 3.5 ) * HOUR_IN_SECONDS;
		$lt  = $ts + $off;
		list( $jy, $jm, $jd ) = gmx_gregorian_to_jalali( (int) gmdate( 'Y', $lt ), (int) gmdate( 'n', $lt ), (int) gmdate( 'j', $lt ) );
		$out = sprintf( '%04d/%02d/%02d', $jy, $jm, $jd );
		if ( $with_time ) {
			$out .= ' - ' . gmdate( 'H:i', $lt );
		}
		return $out;
	}
}

/**
 * تفاوت زمانی خوانا.
 *
 * @param int|string $time زمان.
 * @return string
 */
function gmx_time_diff_fa( $time ) {
	if ( ! is_numeric( $time ) ) {
		$time = strtotime( (string) $time );
	}
	$diff = current_time( 'timestamp' ) - (int) $time;
	if ( $diff < 60 ) {
		return __( 'لحظه‌ای پیش', 'gmx-market' );
	}
	if ( $diff < HOUR_IN_SECONDS ) {
		return sprintf( __( '%d دقیقه پیش', 'gmx-market' ), (int) ( $diff / 60 ) );
	}
	if ( $diff < DAY_IN_SECONDS ) {
		return sprintf( __( '%d ساعت پیش', 'gmx-market' ), (int) ( $diff / HOUR_IN_SECONDS ) );
	}
	if ( $diff < 30 * DAY_IN_SECONDS ) {
		return sprintf( __( '%d روز پیش', 'gmx-market' ), (int) ( $diff / DAY_IN_SECONDS ) );
	}
	return gmx_date( $time, false );
}

/**
 * برچسب فارسی وضعیت سفارش.
 *
 * @param string $status وضعیت.
 * @return string
 */
function gmx_order_status_label( $status ) {
	$labels = array(
		'pending_payment' => __( 'در انتظار پرداخت', 'gmx-market' ),
		'paid'            => __( 'پرداخت شده', 'gmx-market' ),
		'processing'      => __( 'در حال انجام', 'gmx-market' ),
		'delivered'       => __( 'تحویل داده شده', 'gmx-market' ),
		'completed'       => __( 'تکمیل شده', 'gmx-market' ),
		'cancelled'       => __( 'لغو شده', 'gmx-market' ),
		'refunded'        => __( 'بازپرداخت شده', 'gmx-market' ),
	);
	return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
}

/**
 * کلاس رنگ وضعیت سفارش.
 *
 * @param string $status وضعیت.
 * @return string
 */
function gmx_order_status_class( $status ) {
	$map = array(
		'pending_payment' => 'warning',
		'paid'            => 'info',
		'processing'      => 'primary',
		'delivered'       => 'success',
		'completed'       => 'success',
		'cancelled'       => 'danger',
		'refunded'        => 'danger',
	);
	return isset( $map[ $status ] ) ? $map[ $status ] : 'info';
}

/**
 * برچسب وضعیت تیکت.
 *
 * @param string $status وضعیت.
 * @return string
 */
function gmx_ticket_status_label( $status ) {
	$labels = array(
		'open'     => __( 'باز', 'gmx-market' ),
		'answered' => __( 'پاسخ داده شده', 'gmx-market' ),
		'pending'  => __( 'در انتظار کاربر', 'gmx-market' ),
		'closed'   => __( 'بسته شده', 'gmx-market' ),
	);
	return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
}

/**
 * برچسب نوع محصول.
 *
 * @param string $type نوع.
 * @return string
 */
function gmx_product_type_label( $type ) {
	$labels = array(
		'item'      => __( 'آیتم', 'gmx-market' ),
		'gem'       => __( 'جم', 'gmx-market' ),
		'account'   => __( 'اکانت', 'gmx-market' ),
		'service'   => __( 'سرویس', 'gmx-market' ),
		'gift_card' => __( 'گیفت کارت', 'gmx-market' ),
	);
	return isset( $labels[ $type ] ) ? $labels[ $type ] : $type;
}

/**
 * اعداد انگلیسی به فارسی.
 *
 * @param string $str رشته.
 * @return string
 */
if ( ! function_exists( 'gmx_fa_num' ) ) {
	function gmx_fa_num( $str ) {
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			(string) $str
		);
	}
}
