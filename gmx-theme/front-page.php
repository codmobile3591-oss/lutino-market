<?php
/**
 * صفحه اصلی لوتینو — بازطراحی مطابق طرح مرجع (مارکت‌پلیس گیمینگ).
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$types   = gmx_theme_product_types();
$can_qry = class_exists( 'GMX_Orders' );

global $wpdb;

// خریدهای اخیر واقعی برای بخش اخبار/همین الان‌ها.
$recent = array();
if ( $can_qry ) {
	$recent = $wpdb->get_results(
		"SELECT product_title, total, created_at FROM {$wpdb->prefix}gmx_orders
		 WHERE status IN ('paid','processing','delivered','completed')
		 ORDER BY created_at DESC LIMIT 6"
	);
}

// فروشندگان برتر واقعی.
$top_sellers = array();
if ( $can_qry ) {
	$top_sellers = $wpdb->get_results(
		"SELECT seller_id, COUNT(*) AS sales, SUM(total) AS revenue
		 FROM {$wpdb->prefix}gmx_orders
		 WHERE status IN ('paid','processing','delivered','completed') AND seller_id > 0
		 GROUP BY seller_id ORDER BY sales DESC LIMIT 4"
	);
}

// پایان پیشنهاد ویژه: نیمه‌شب.
$deal_deadline = strtotime( 'today +1 day', current_time( 'timestamp' ) );

// آمار.
$stats_products = (int) wp_count_posts( 'gmx_product' )->publish;
$stats_orders   = $can_qry ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gmx_orders" ) : 0;
$stats_users    = count_users();
$stats_users    = isset( $stats_users['total_users'] ) ? (int) $stats_users['total_users'] : 0;

// تب‌های محصولات.
$product_tabs = array(
	'latest'  => array(
		'label' => 'جدیدترین',
		'args'  => array( 'orderby' => 'date', 'order' => 'DESC' ),
	),
	'cheap'   => array(
		'label' => 'ارزان‌ترین',
		'args'  => array( 'meta_key' => '_gmx_price', 'orderby' => 'meta_value_num', 'order' => 'ASC' ),
	),
	'instant' => array(
		'label' => 'تحویل آنی',
		'args'  => array( 'meta_query' => array( array( 'key' => '_gmx_auto_delivery', 'value' => '1' ) ) ),
	),
);

// هنر و زیرعنوان دسته‌بندی‌ها — مطابق طرح مرجع (به ترتیب نمایش).
$cat_art = array(
	'gem'     => array( 'img' => GMX_THEME_URL . '/assets/img/ref/cat-gems.jpg',     'sub' => 'خرید جم و ارز انواع بازی‌ها',       'tint' => 'tint-gem',     'order' => 1 ),
	'account' => array( 'img' => GMX_THEME_URL . '/assets/img/ref/cat-valorant.jpg', 'sub' => 'اکانت‌های آماده و ممتاز',           'tint' => 'tint-account', 'order' => 2 ),
	'item'    => array( 'img' => GMX_THEME_URL . '/assets/img/ref/cat-cod.jpg',      'sub' => 'آیتم‌های سطلی بالا و حرفه‌ای',      'tint' => 'tint-cod',     'order' => 3 ),
	'service' => array( 'img' => GMX_THEME_URL . '/assets/img/ref/cat-steam.jpg',    'sub' => 'اکانت‌های استیم با اعتبار بالا',    'tint' => 'tint-steam',   'order' => 4 ),
	'steam'   => array( 'img' => GMX_THEME_URL . '/assets/img/ref/cat-steam.jpg',    'sub' => 'اکانت‌های استیم با اعتبار بالا',    'tint' => 'tint-steam',   'order' => 4 ),
);

// اخبار نمایشی.
$news_items = array(
	array(
		't'    => 'تخفیف ویژه برای اکانت‌های کالاف دیوتی',
		'd'    => 'تا ۴۰٪ تخفیف روی آگهی‌های منتخب',
		'img'  => GMX_THEME_URL . '/assets/img/ref/news-1.jpg',
		'time' => '۱۴۰۵/۰۶/۲۱',
	),
	array(
		't'    => 'افزایش موجودی اکانت‌های استیم',
		'd'    => 'اکانت‌های جدید با سطح بالا اضافه شدند',
		'img'  => GMX_THEME_URL . '/assets/img/ref/news-2.jpg',
		'time' => '۱۴۰۵/۰۶/۱۲',
	),
	array(
		't'    => 'روش‌های امن خرید و برداخت',
		'd'    => 'راهنمای کامل گامرین برای معاملات امن',
		'img'  => GMX_THEME_URL . '/assets/img/ref/news-3.jpg',
		'time' => '۱۴۰۵/۰۶/۰۵',
	),
);

// نظرات نمایشی.
$reviews = array(
	array( 'name' => 'امیرحسین', 'tag' => 'جم فری فایر', 'text' => 'کمتر از ۵ دقیقه جم رسید؛ پشتیبانی هم تا آخر همراهم بود. عالی بود.' ),
	array( 'name' => 'سارا', 'tag' => 'اکانت Valorant', 'text' => 'اولش شک داشتم ولی سیستم امانی واقعاً خیال‌راحت‌کن بود. اکانت دقیقاً مثل توضیحات بود.' ),
	array( 'name' => 'محمد', 'tag' => 'آیتم PUBG', 'text' => 'قیمت‌ها از همه‌جا بهتره و چت با فروشنده خیلی راحت حل‌کننده بود.' ),
	array( 'name' => 'رضا', 'tag' => 'گیفت کارت', 'text' => 'دو بار خرید کردم، هر بار سریع و بدون دردسر. به دوستهامم معرفی کردم.' ),
);
?>

<!-- هیرو آرت‌ورک -->
<section class="hero-art">
	<div class="hero-art-bg" role="img" aria-label="گیمر سایبری"></div>
	<div class="container hero-art-grid">
		<div class="hero-art-content">
			<span class="hero-eyebrow"><i class="pulse-dot"></i> لوتینو <?php echo esc_html( gmx_opt( 'gmx_hero_eyebrow', '| فروشگاه تخصصی اکانت‌های گیمینگ' ) ); ?></span>
			<h1 class="hero-art-title">
				<?php echo esc_html( gmx_opt( 'gmx_hero_title_l1', 'خرید اکانت بازی' ) ); ?><br />
				<span class="grad-text"><?php echo esc_html( gmx_opt( 'gmx_hero_title_l2', 'با خیال راحت' ) ); ?></span>
			</h1>
			<p class="hero-art-desc"><?php echo esc_html( gmx_opt( 'gmx_hero_desc', 'اکانت‌های قانونی، تحویل سریع، پشتیبانی دائمی' ) ); ?></p>
			<div class="hero-actions">
				<a class="btn btn-primary btn-lg btn-glow" href="<?php echo esc_url( get_post_type_archive_link( 'gmx_product' ) ); ?>">
					<?php echo esc_html( gmx_opt( 'gmx_hero_btn', 'مشاهده محصولات' ) ); ?> <span class="btn-arrow">←</span>
				</a>
			</div>
		</div>
		<aside class="hero-side-trust">
			<div class="hst-item"><span class="hst-ic">⚡</span><div><strong><?php esc_html_e( 'تحویل فوری', 'gmx-theme' ); ?></strong><small><?php esc_html_e( 'کمتر از ۵ دقیقه', 'gmx-theme' ); ?></small></div></div>
			<div class="hst-item"><span class="hst-ic">🛡️</span><div><strong><?php esc_html_e( 'امن و مطمئن', 'gmx-theme' ); ?></strong><small><?php esc_html_e( 'پرداخت امانی', 'gmx-theme' ); ?></small></div></div>
			<div class="hst-item"><span class="hst-ic">🎧</span><div><strong><?php esc_html_e( 'پشتیبانی ۲۴/۷', 'gmx-theme' ); ?></strong><small><?php esc_html_e( 'همیشه در دسترس', 'gmx-theme' ); ?></small></div></div>
		</aside>
	</div>
</section>

<!-- دسته‌بندی‌های اصلی -->
<?php if ( $types ) : ?>
<section class="container home-section reveal">
	<div class="section-head">
		<h2 class="section-title"><span class="sec-ic">🎮</span> <?php esc_html_e( 'دسته‌بندی‌های اصلی', 'gmx-theme' ); ?></h2>
		<a class="see-all" href="<?php echo esc_url( get_post_type_archive_link( 'gmx_product' ) ); ?>"><?php esc_html_e( 'مشاهده همه', 'gmx-theme' ); ?> ←</a>
	</div>
	<div class="cat-cards-row">
		<?php
		$types_sorted = $types;
		usort(
			$types_sorted,
			function ( $a, $b ) use ( $cat_art ) {
				$ao = isset( $cat_art[ $a->slug ]['order'] ) ? $cat_art[ $a->slug ]['order'] : 99;
				$bo = isset( $cat_art[ $b->slug ]['order'] ) ? $cat_art[ $b->slug ]['order'] : 99;
				return $ao - $bo;
			}
		);
		foreach ( $types_sorted as $term ) :
			if ( ! isset( $cat_art[ $term->slug ] ) ) {
				continue;
			}
			$art = $cat_art[ $term->slug ];
			?>
			<a class="cat-card <?php echo esc_attr( $art['tint'] ); ?>" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
				<span class="cat-card-img"><img src="<?php echo esc_url( $art['img'] ); ?>" alt="" loading="lazy" /></span>
				<span class="cat-card-body">
					<span class="cat-card-row">
						<span class="cat-card-txt">
							<strong class="cat-card-name"><?php echo esc_html( $term->name ); ?></strong>
							<span class="cat-card-sub"><?php echo esc_html( $art['sub'] ); ?></span>
						</span>
						<span class="cat-card-arrow">←</span>
					</span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<!-- محصولات با تب -->
<section class="container home-section reveal">
	<div class="section-head">
		<h2 class="section-title"><span class="sec-ic">🔥</span> <?php esc_html_e( 'محبوب‌ترین آگهی‌ها', 'gmx-theme' ); ?></h2>
		<div class="product-tabs" role="tablist">
			<?php $first = true; foreach ( $product_tabs as $key => $tab ) : ?>
				<button type="button" class="ptab<?php echo $first ? ' is-active' : ''; ?>" data-ptab="<?php echo esc_attr( $key ); ?>" role="tab"><?php echo esc_html( $tab['label'] ); ?></button>
			<?php $first = false; endforeach; ?>
		</div>
	</div>

	<?php foreach ( $product_tabs as $key => $tab ) : ?>
		<?php
		$q = new WP_Query(
			array_merge(
				array(
					'post_type'      => 'gmx_product',
					'posts_per_page' => 8,
					'post_status'    => 'publish',
					'no_found_rows'  => true,
				),
				$tab['args']
			)
		);
		?>
		<div class="ptab-panel<?php echo 'latest' === $key ? ' is-active' : ''; ?>" data-ptab-panel="<?php echo esc_attr( $key ); ?>">
			<?php if ( $q->have_posts() ) : ?>
				<div class="posts-grid products-grid">
					<?php
					while ( $q->have_posts() ) :
						$q->the_post();
						get_template_part( 'template-parts/card', 'product' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			<?php else : ?>
				<div class="empty-state"><p><?php esc_html_e( 'فعلاً آگهی‌ای در این دسته نیست.', 'gmx-theme' ); ?></p></div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</section>

<!-- CTA فروشنده -->
<section class="container home-section reveal">
	<div class="seller-cta">
		<div class="seller-cta-art" role="img" aria-label="گیم‌پد نئونی"></div>
		<div class="seller-cta-body">
			<h2><?php esc_html_e( 'به دنیای بازی‌ها، یک قدم جلوتر باش!', 'gmx-theme' ); ?></h2>
			<p><?php esc_html_e( 'لوتینو، انتخاب حرفه‌ای گیمرها', 'gmx-theme' ); ?></p>
		</div>
		<div class="seller-cta-side">
			<div class="seller-cta-badges">
				<span>✓ <?php esc_html_e( 'قیمت‌های رقابتی', 'gmx-theme' ); ?></span>
				<span>✓ <?php esc_html_e( 'تخفیف بالا از رمزولوت', 'gmx-theme' ); ?></span>
				<span>✓ <?php esc_html_e( 'بستاندی واقعی', 'gmx-theme' ); ?></span>
			</div>
			<a class="btn btn-primary btn-lg" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'درباره ما', 'gmx-theme' ); ?> <span class="btn-arrow">←</span></a>
		</div>
	</div>
</section>

<!-- چرا لوتینو + اخبار -->
<section class="container home-section reveal">
	<div class="two-col">
		<div class="why-lutino">
			<h2 class="section-title"><span class="sec-ic">💠</span> <?php esc_html_e( 'چرا لوتینو؟', 'gmx-theme' ); ?></h2>
			<div class="why-grid">
				<div class="why-item"><span class="why-ic ic-1">⚡</span><strong><?php esc_html_e( 'تحویل فوری', 'gmx-theme' ); ?></strong><p><?php esc_html_e( 'در کمتر از ۵ دقیقه', 'gmx-theme' ); ?></p></div>
				<div class="why-item"><span class="why-ic ic-2">🎧</span><strong><?php esc_html_e( 'پشتیبانی ۲۴/۷', 'gmx-theme' ); ?></strong><p><?php esc_html_e( 'همیشه در دسترس', 'gmx-theme' ); ?></p></div>
				<div class="why-item"><span class="why-ic ic-3">🛡️</span><strong><?php esc_html_e( 'امن و مطمئن', 'gmx-theme' ); ?></strong><p><?php esc_html_e( 'پرداخت امانی لوتینو', 'gmx-theme' ); ?></p></div>
				<div class="why-item"><span class="why-ic ic-4">🏷️</span><strong><?php esc_html_e( 'قیمت مناسب', 'gmx-theme' ); ?></strong><p><?php esc_html_e( 'به‌صرفه برای همه', 'gmx-theme' ); ?></p></div>
				<div class="why-item"><span class="why-ic ic-5">🎮</span><strong><?php esc_html_e( 'بازی‌های روز', 'gmx-theme' ); ?></strong><p><?php esc_html_e( 'آخرین نسخه‌ها', 'gmx-theme' ); ?></p></div>
				<div class="why-item"><span class="why-ic ic-6">💳</span><strong><?php esc_html_e( 'پرداخت آسان', 'gmx-theme' ); ?></strong><p><?php esc_html_e( 'درگاه‌های امن بانکی', 'gmx-theme' ); ?></p></div>
			</div>
		</div>
		<div class="news-box">
			<div class="section-head">
				<h2 class="section-title"><span class="sec-ic">📢</span> <?php esc_html_e( 'آخرین اخبار و تخفیف‌ها', 'gmx-theme' ); ?></h2>
			</div>
			<div class="news-list">
				<?php foreach ( $news_items as $n ) : ?>
					<article class="news-item">
						<div class="news-body">
							<h3><?php echo esc_html( $n['t'] ); ?></h3>
							<p><?php echo esc_html( $n['d'] ); ?></p>
							<time><?php echo esc_html( $n['time'] ); ?></time>
						</div>
						<span class="news-thumb">
							<img src="<?php echo esc_url( $n['img'] ); ?>" alt="" loading="lazy" />
						</span>
					</article>
				<?php endforeach; ?>
			</div>
			<a class="btn btn-outline news-all-btn" href="#"><?php esc_html_e( 'مشاهده همه اخبار', 'gmx-theme' ); ?> <span class="btn-arrow">←</span></a>
		</div>
	</div>
</section>

<!-- نظرات -->
<section class="container home-section reveal">
	<h2 class="section-title"><span class="sec-ic">💬</span> <?php esc_html_e( 'گیمرها چی می‌گویند؟', 'gmx-theme' ); ?></h2>
	<div class="reviews-grid">
		<?php foreach ( $reviews as $r ) : ?>
			<figure class="review-card">
				<span class="review-stars">★★★★★</span>
				<blockquote><?php echo esc_html( $r['text'] ); ?></blockquote>
				<figcaption>
					<span class="review-avatar"><?php echo esc_html( mb_substr( $r['name'], 0, 1 ) ); ?></span>
					<span><strong><?php echo esc_html( $r['name'] ); ?></strong><small><?php echo esc_html( $r['tag'] ); ?></small></span>
				</figcaption>
			</figure>
		<?php endforeach; ?>
	</div>
</section>

<!-- فروشندگان برتر -->
<?php if ( $top_sellers ) : ?>
<section class="container home-section reveal">
	<div class="section-head">
		<h2 class="section-title"><span class="sec-ic">🏅</span> <?php esc_html_e( 'فروشندگان برتر', 'gmx-theme' ); ?></h2>
	</div>
	<div class="sellers-grid">
		<?php $rank = 1; foreach ( $top_sellers as $ts ) : $u = get_userdata( $ts->seller_id ); if ( ! $u ) { continue; } $name = gmx_display_name( $ts->seller_id ); ?>
			<div class="seller-card">
				<span class="seller-rank r<?php echo esc_attr( $rank ); ?>"><?php echo esc_html( gmx_fa_num( $rank ) ); ?></span>
				<span class="seller-avatar"><?php echo esc_html( mb_substr( $name, 0, 1 ) ); ?></span>
				<strong class="seller-name"><?php echo esc_html( $name ); ?></strong>
				<span class="seller-stat"><?php echo esc_html( gmx_fa_num( $ts->sales ) ); ?> <?php esc_html_e( 'فروش موفق', 'gmx-theme' ); ?></span>
				<span class="seller-stars" aria-label="امتیاز">★★★★★</span>
			</div>
		<?php $rank++; endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
