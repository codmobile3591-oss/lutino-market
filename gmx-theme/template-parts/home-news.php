<?php
/**
 * بخش اخبار صفحه اصلی — تلاش برای داده واقعی و سپس fallback نمایشی.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ۱) آخرین پست‌های بلاگ واقعی (در صورت وجود).
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
	// پست‌های نمونه پیش‌فرض وردپرس در صفحه اصلی نمایش داده نمی‌شوند.
	if ( in_array( $np->post_name, array( 'hello-world', '%d8%b3%d9%84%d8%a7%d9%85-%d8%af%d9%86%db%8c%d8%a7' ), true ) || 'سلام دنیا!' === $np->post_title ) {
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

// ۲) اگر پست کم بود: تکمیل با تازه‌ترین آگهی‌های واقعی مارکت‌پلیس.
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
		$thumb  = get_the_post_thumbnail_url( $lp, 'medium' );
		$price  = (float) get_post_meta( $lp->ID, '_gmx_price', true );
		$auto   = get_post_meta( $lp->ID, '_gmx_auto_delivery', true );
		$terms  = get_the_terms( $lp, 'gmx_product_type' );
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

// ۳) اگر سایت هنوز خالی بود: آیتم‌های نمایشی با تاریخ و لینک واقعی.
if ( ! $news_items ) {
	$demo = array(
		array(
			't' => __( 'راهنمای امن خرید اکانت در لوتینو', 'gmx-theme' ),
			'd' => __( 'همه‌چیز درباره پرداخت امانی و تحویل امن', 'gmx-theme' ),
			'k' => __( 'راهنما', 'gmx-theme' ),
		),
		array(
			't' => __( 'تخفیف ویژه اولین خرید', 'gmx-theme' ),
			'd' => __( 'برای سفارش‌های زیر ۵۰۰ هزار تومان', 'gmx-theme' ),
			'k' => __( 'تخفیف', 'gmx-theme' ),
		),
		array(
			't' => __( 'تحویل آنی برای محصولات دیجیتال', 'gmx-theme' ),
			'd' => __( 'محصولات با برچسب تحویل فوری در کمتر از ۵ دقیقه', 'gmx-theme' ),
			'k' => __( 'امکانات', 'gmx-theme' ),
		),
	);
	foreach ( $demo as $i => $d ) {
		$news_items[] = array(
			't'    => $d['t'],
			'd'    => $d['d'],
			'img'  => GMX_THEME_URL . '/assets/img/ref/news-' . ( $i + 1 ) . '.jpg',
			'time' => gmx_fa_num( gmx_date( null, false ) ),
			'url'  => home_url( '/shop/' ),
			'k'    => $d['k'],
		);
	}
}

$news_archive = ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_for_posts' ) ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/?post_type=post' );
?>
<div class="news-box">
	<div class="section-head">
		<h2 class="section-title"><span class="sec-ic">📢</span> <?php esc_html_e( 'آخرین اخبار و تخفیف‌ها', 'gmx-theme' ); ?></h2>
		<span class="news-live-dot"><span class="live-pulse"></span> <?php esc_html_e( 'زنده', 'gmx-theme' ); ?></span>
	</div>
	<div class="news-list" id="gmx-news-list" data-gmx-news-nonce="<?php echo esc_attr( wp_create_nonce( 'gmx_news_refresh' ) ); ?>">
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
	</div>
	<a class="btn btn-outline news-all-btn" href="<?php echo esc_url( $news_archive ); ?>"><?php esc_html_e( 'مشاهده همه اخبار', 'gmx-theme' ); ?> <span class="btn-arrow">←</span></a>
</div>