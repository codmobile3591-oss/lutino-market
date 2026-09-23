<?php
/**
 * ایمپورت اسکرین‌شات کتابخانه Steam به‌عنوان تصویر محصول «اکانت Steam قدیمی — کتابخانه پر» (ID 8).
 * اجرا:
 *   C:/xampp/php/php.exe -d extension_dir="C:/xampp/php/ext" -d browscap="C:/xampp/php/extras/browscap.ini" tools/import-steam-library.php
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

$src    = 'C:/Users/aliem/Downloads/steam.png';
$upload = wp_upload_dir();

// ۱) افزودن فایل به رسانه (PNG با پس‌زمینه شفاف حفظ می‌شود).
$att_id = media_handle_sideload(
	array(
		'name'     => 'steam-account-library.png',
		'type'     => 'image/png',
		'tmp_name' => $src,
	),
	8,
	'اکانت Steam قدیمی — کتابخانه پر'
);
if ( is_wp_error( $att_id ) ) {
	exit( 'SIDELOAD ERR: ' . $att_id->get_error_message() . "\n" );
}
echo "پیوست جدید: #{$att_id}\n";

// ۲) تصویر شاخص محصول ۸.
set_post_thumbnail( 8, $att_id );
echo 'شاخص محصول ۸: #' . get_post_thumbnail_id( 8 ) . "\n";

$meta = wp_get_attachment_metadata( $att_id );
echo 'ابعاد اصلی: ' . $meta['width'] . 'x' . $meta['height'] . "\n";
foreach ( $meta['sizes'] as $name => $s ) {
	echo "  - {$name}: {$s['width']}x{$s['height']}\n";
}
