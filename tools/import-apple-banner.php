<?php
/**
 * ایمپورت بنر جدید گیفت کارت اپل: برش ۱۶:۹ (مرکزی) + افزودن به رسانه + تصویر شاخص محصول ۴۷.
 * اجرا:
 *   C:/xampp/php/php.exe -d extension_dir="C:/xampp/php/ext" -d browscap="C:/xampp/php/extras/browscap.ini" tools/import-apple-banner.php
 */

if ( 'cli' !== php_sapi_name() ) {
	exit( "فقط از خط فرمان اجرا شود.\n" );
}

$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME']    = 'localhost';
$_SERVER['SERVER_PORT']    = '80';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['PHP_SELF']       = '/index.php';

require 'C:/xampp/htdocs/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$src    = 'C:/Users/aliem/Downloads/ChatGPT Image Sep 22, 2026, 09_16_17 PM.png';
$upload = wp_upload_dir();

// ۱) برش مرکزی به ۱۶:۹ (۱۴۴۸×۸۱۵ — هم‌نسبت با بقیه بنرهای gmx-card).
$ed = wp_get_image_editor( $src );
if ( is_wp_error( $ed ) ) {
	exit( 'EDITOR ERR: ' . $ed->get_error_message() . "\n" );
}

$size   = $ed->get_size();
$target = (int) round( $size['width'] * 9 / 16 ); // 815
$y      = (int) ( ( $size['height'] - $target ) / 2 );
$ed->crop( 0, $y, $size['width'], $target );
$ed->set_quality( 90 );

$tmp = $upload['basedir'] . '/tmp-apple-banner.jpg';
$res = $ed->save( $tmp, 'image/jpeg' );
if ( is_wp_error( $res ) ) {
	exit( 'CROP ERR: ' . $res->get_error_message() . "\n" );
}
echo 'برش: ' . $res['width'] . 'x' . $res['height'] . "\n";

// ۲) افزودن به رسانه.
$att_id = media_handle_sideload(
	array(
		'name'     => 'apple-gift-banner-169.jpg',
		'type'     => 'image/jpeg',
		'tmp_name' => $tmp,
	),
	47,
	'گیفت کارت اپل — App Store و iTunes (۱۶:۹)'
);
if ( is_wp_error( $att_id ) ) {
	exit( 'SIDELOAD ERR: ' . $att_id->get_error_message() . "\n" );
}
@unlink( $tmp );
echo "پیوست جدید: #{$att_id}\n";

// ۳) تصویر شاخص محصول ۴۷.
set_post_thumbnail( 47, $att_id );
echo 'شاخص محصول ۴۷: #' . get_post_thumbnail_id( 47 ) . "\n";

$meta = wp_get_attachment_metadata( $att_id );
foreach ( $meta['sizes'] as $name => $s ) {
	echo "  - {$name}: {$s['width']}x{$s['height']}\n";
}
