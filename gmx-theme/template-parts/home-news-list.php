<?php
/**
 * پارت فقط-لیست اخبار برای REST (تازه‌سازی زنده بدون جعبه و دکمه).
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// بازسازی همان داده پارت اصلی (بدون جعبه).
$news_items = array();
$news_posts = get_posts(
	array(
		'numberposts'      => 3,
		'post_type'        => 'post',
		'post_status'      => 'publish',
		'suppress_filters' => true,
	)
);

foreach ( $news_posts as $np ) {
	// پست‌های نمونه پیش‌فرض وردپرس نمایش داده نمی‌شوند.
	if ( 'سلام دنیا!' === $np->post_title ) {
		continue;
	}
	$thumb = get_the_post_thumbnail_url( $np, 'medium' );
	$terms = get_the_terms( $np, 'category' );
	$news_items[] = array(
		't'    => get_the_title( $np ),
		'd'    => wp_trim_words( wp_strip_all_tags( $np->post_content ), 12, '…' ),
		'img'  => $thumb ? $thumb : GMX_THEME_URL . '/assets/img/ref/news-1.jpg',
		'time' => gmx_fa_num( gmx_date( get_post_timestamp( $np ), false ) ),
		'url'  => get_permalink( $np ),
		'k'    => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : __( 'خبر', 'gmx-theme' ),
	);
}

$need = 3 - count( $news_items );
if ( $need > 0 && post_type_exists( 'gmx_product' ) ) {
	$latest = get_posts(
		array(
			'numberposts' => $need,
			'post_type'   => 'gmx_product',
			'post_status' => 'publish',
			'orderby'     => 'date',
			'order'       => 'DESC',
		)
	);
	foreach ( $latest as $lp ) {
		if ( count( $news_items ) >= 3 ) {
			break;
		}
		$thumb = get_the_post_thumbnail_url( $lp, 'medium' );
		$price = (float) get_post_meta( $lp->ID, '_gmx_price', true );
		$auto  = get_post_meta( $lp->ID, '_gmx_auto_delivery', true );
		$terms = get_the_terms( $lp, 'gmx_product_type' );
		$cat_fa = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
		$news_items[] = array(
			't'    => sprintf( __( 'آگهی جدید: %s', 'gmx-theme' ), get_the_title( $lp ) ),
			'd'    => $price > 0 ? sprintf( __( 'قیمت %s تومان', 'gmx-theme' ), gmx_fa_num( number_format( $price ) ) ) : '',
			'img'  => $thumb ? $thumb : GMX_THEME_URL . '/assets/img/ref/news-2.jpg',
			'time' => gmx_fa_num( gmx_date( get_post_timestamp( $lp ), false ) ),
			'url'  => get_permalink( $lp ),
			'k'    => $auto ? __( 'تحویل آنی', 'gmx-theme' ) : ( $cat_fa ? $cat_fa : __( 'آگهی', 'gmx-theme' ) ),
		);
	}
}

if ( ! $news_items ) {
	esc_html_e( 'فعلاً خبری نیست.', 'gmx-theme' );
	return;
}
?>
<?php foreach ( $news_items as $n ) : ?>
	<article class="news-item">
		<div class="news-body">
			<?php if ( ! empty( $n['k'] ) ) : ?>
				<span class="news-tag"><?php echo esc_html( $n['k'] ); ?></span>
			<?php endif; ?>
			<h3><a href="<?php echo esc_url( $n['url'] ); ?>"><?php echo esc_html( $n['t'] ); ?></a></h3>
			<p><?php echo esc_html( $n['d'] ); ?></p>
			<time><?php echo esc_html( $n['time'] ); ?></time>
		</div>
		<span class="news-thumb">
			<img src="<?php echo esc_url( $n['img'] ); ?>" alt="" loading="lazy" />
		</span>
	</article>
<?php endforeach; ?>
