<?php
/**
 * بازتولید متادیتا و برش‌های تصویر (gmx-card، gmx-hero و...) برای پیوست‌ها.
 *
 * استفاده:
 *   php tools/regen-image-sizes.php            ← همه پیوست‌های تصویری
 *   php tools/regen-image-sizes.php 81 45      ← فقط پیوست ۸۱
 *
 * نکته XAMPP (وقتی از درایو دیگری اجرا می‌شود):
 *   C:/xampp/php/php.exe -d extension_dir="C:/xampp/php/ext" -d browscap="C:/xampp/php/extras/browscap.ini" tools/regen-image-sizes.php
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
require_once ABSPATH . 'wp-admin/includes/image.php';

if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/jpeg' ) ) ) {
	exit( "خطا: ویرایشگر تصویر (GD/Imagick) در دسترس نیست — extension=gd را در php.ini فعال کنید.\n" );
}

// شناسه‌های مشخص از آرگومان‌ها، وگرنه همه پیوست‌های تصویری.
$ids = array_filter( array_map( 'intval', array_slice( $argv, 1 ) ) );

if ( $ids ) {
	$attachments = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'include'     => $ids,
			'numberposts' => -1,
		)
	);
} else {
	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => -1,
			'fields'         => 'ids',
		)
	);
}

if ( ! $attachments ) {
	exit( "پیوست تصویری پیدا نشد.\n" );
}

$done = $skip = 0;

foreach ( $attachments as $att ) {
	$att_id = is_object( $att ) ? $att->ID : (int) $att;
	$file   = get_attached_file( $att_id );

	if ( ! $file || ! file_exists( $file ) ) {
		echo "  #{$att_id}: فایل پیدا نشد — رد شد\n";
		++$skip;
		continue;
	}

	$meta = wp_generate_attachment_metadata( $att_id, $file );
	wp_update_attachment_metadata( $att_id, $meta );

	$count = isset( $meta['sizes'] ) ? count( $meta['sizes'] ) : 0;
	echo "  #{$att_id}: " . basename( $file ) . " — {$count} برش تولید شد\n";
	++$done;
}

echo "تمام: {$done} پیوست پردازش شد، {$skip} رد شد.\n";
