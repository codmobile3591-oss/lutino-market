<?php
/**
 * ابزار یک‌باره: ساخت بنر دسته «اکانت‌ها» از تصویر جدید (برش مرکزی ~۲.۴۵:۱).
 * اجرا: C:/xampp/php/php.exe -d extension_dir="C:/xampp/php/ext" tools/make-cat-accounts.php
 */

$src = 'C:/Users/aliem/Downloads/ChatGPT Image Sep 23, 2026, 02_30_11 AM.png';
$dst = __DIR__ . '/../gmx-theme/assets/img/ref/cat-accounts.jpg';

if ( ! is_file( $src ) ) {
	exit( "منبع پیدا نشد\n" );
}

$png = imagecreatefrompng( $src );
if ( ! $png ) {
	exit( "خواندن PNG ناموفق\n" );
}

$w    = imagesx( $png );
$h    = imagesy( $png );
$ch   = (int) round( $w / 2.45 );       // نوار مرکزی هم‌نسبت کارت‌های دسته (۲۲۸×۹۳).
$cy   = (int) ( ( $h - $ch ) / 2 );     // وسط تصویر: لوگو و تیتر.
$crop = imagecreatetruecolor( $w, $ch );
imagecopy( $crop, $png, 0, 0, 0, $cy, $w, $ch );
imagejpeg( $crop, $dst, 88 );
echo 'ساخته شد: ' . realpath( $dst ) . ' (' . $w . 'x' . $ch . ")\n";
imagedestroy( $png );
imagedestroy( $crop );
