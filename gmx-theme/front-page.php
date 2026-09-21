<?php
/**
 * صفحه اصلی حرفه‌ای لوتینو — به سبک مارکت‌پلیس‌های جهانی.
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

// خریدهای اخیر واقعی برای تیکر زنده.
$recent = array();
if ( $can_qry ) {
	$recent = $wpdb->get_results(
		"SELECT product_title, total, created_at FROM {$wpdb->prefix}gmx_orders
		 WHERE status IN ('paid','processing','delivered','completed')
		 ORDER BY created_at DESC LIMIT 8"
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

// بازی‌های محبوب.
$games = get_terms(
	array(
		'taxonomy'   => 'gmx_game',
		'hide_empty' => false,
		'number'     => 12,
	)
);
$games = is_wp_error( $games ) ? array() : $games;
$game_icons = array(
	'fortnite'     => '🪂',
	'valorant'     => '🎯',
	'steam'        => '💠',
	'call-of-duty' => '🪖',
);

// پایان پیشنهاد ویژه: نیمه‌شب.
$deal_deadline = strtotime( 'today +1 day', current_time( 'timestamp' ) );

// آمار.
$stats_products = (int) wp_count_posts( 'gmx_product' )->publish;
$stats_orders   = $can_qry ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gmx_orders" ) : 0;
$stats_users    = count_users();
$stats_users    = isset( $stats_users['total_users'] ) ? (int) $stats_users['total_users'] : 0;// اسلایدهای بنر: از تنظیمات پنل + لینک هوشمند تکسونومی.
$gem_term     = get_term_by( 'slug', 'gem', 'gmx_product_type' );
$account_term = get_term_by( 'slug', 'account', 'gmx_product_type' );
$gem_link     = ( $gem_term && ! is_wp_error( $gem_term ) ) ? get_term_link( $gem_term ) : get_post_type_archive_link( 'gmx_product' );
$account_link = ( $account_term && ! is_wp_error( $account_term ) ) ? get_term_link( $account_term ) : get_post_type_archive_link( 'gmx_product' );
$slides       = array(
	array(
		'emoji'  => '💎',
		'tag'    => gmx_opt( 'gmx_slide1_tag', 'جم و ارز درون‌بازی' ),
		'title'  => gmx_opt( 'gmx_slide1_title', 'جم فوری با بهترین نرخ بازار' ),
		'text'   => gmx_opt( 'gmx_slide1_text', 'تحویل زیر ۵ دقیقه روی بازی‌های محبوب — اولین خرید با ۱۵٪ تخفیف.' ),
		'btn'    => gmx_opt( 'gmx_slide1_btn', 'خرید جم' ),
		'link'   => gmx_opt( 'gmx_slide1_link', $gem_link ),
		'theme'  => 'slide-gem',
	),
	array(
		'emoji'  => '🛡️',
		'tag'    => gmx_opt( 'gmx_slide2_tag', 'معامله امانی' ),
		'title'  => gmx_opt( 'gmx_slide2_title', 'اکانت بخر، خیالت راحت' ),
		'text'   => gmx_opt( 'gmx_slide2_text', 'پول تا تایید تحویل نزد سایت امانت می‌ماند؛ اسکم ممنوع.' ),
		'btn'    => gmx_opt( 'gmx_slide2_btn', 'دیدن اکانت‌ها' ),
		'link'   => gmx_opt( 'gmx_slide2_link', $account_link ),
		'theme'  => 'slide-account',
	),
	array(
		'emoji'  => '🚀',
		'tag'    => gmx_opt( 'gmx_slide3_tag', 'کسب درآمد' ),
		'title'  => gmx_opt( 'gmx_slide3_title', 'فروشنده لوتینو شو' ),
		'text'   => gmx_opt( 'gmx_slide3_text', 'آگهی‌ات را بگذار، ما امنیت پرداخت و مشتری را می‌آوریم.' ),
		'btn'    => gmx_opt( 'gmx_slide3_btn', 'ثبت آگهی' ),
		'link'   => gmx_opt( 'gmx_slide3_link', home_url( '/my-account/seller/' ) ),
		'theme'  => 'slide-seller',
	),
);

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

// نظرات نمایشی.
$reviews = array(
	array( 'name' => 'امیرحسین', 'tag' => 'جم فری فایر', 'text' => 'کمتر از ۵ دقیقه جم رسید؛ پشتیبانی هم تا آخر همراهم بود. عالی بود.' ),
	array( 'name' => 'سارا', 'tag' => 'اکانت Valorant', 'text' => 'اولش شک داشتم ولی سیستم امانی واقعاً خیال‌راحت‌کن بود. اکانت دقیقاً مثل توضیحات بود.' ),
	array( 'name' => 'محمد', 'tag' => 'آیتم PUBG', 'text' => 'قیمت‌ها از همه‌جا بهتره و چت با فروشنده خیلی راحت حل‌کننده بود.' ),
	array( 'name' => 'رضا', 'tag' => 'گیفت کارت', 'text' => 'دو بار خرید کردم، هر بار سریع و بدون دردسر. به دوستهامم معرفی کردم.' ),
);

// پرسش‌های متداول.
$faqs = array(
	array( 'q' => 'پرداخت امانی چطور کار می‌کند؟', 'a' => 'مبلغ خرید شما تا زمان تایید تحویل نزد لوتینو امانت می‌ماند و فقط بعد از تایید شما به فروشنده پرداخت می‌شود؛ در غیر این صورت کامل برمی‌گردد.' ),
	array( 'q' => 'چقدر طول می‌کشد سفارشم تحویل شود؟', 'a' => 'محصولات دارای برچسب «تحویل آنی» به‌صورت خودکار بلافاصله تحویل می‌شوند. بقیه سفارش‌ها طبق زمان اعلامی آگهی و با پیگیری چت انجام می‌شود.' ),
	array( 'q' => 'اگر خریدم به مشکل خورد چه کنم؟', 'a' => 'داخل هر سفارش دکمه گفتگو و تیکت پشتیبانی داری. تیم پشتیبانی ۲۴ ساعته رسیدگی می‌کند و در صورت مشکله، وجه به کیف پول‌ات برمی‌گردد.' ),
	array( 'q' => 'چطور فروشنده شوم؟', 'a' => 'کافیست ثبت‌نام کنی و از بخش «فروش‌های من» آگهی بگذاری. بعد از تایید، آگهی‌ات در مارکت نمایش داده می‌شود.' ),
);
?>

<!-- هیرو + اسلایدر -->
<section class="hero hero-v3">
	<canvas class="hero-grid-bg" aria-hidden="true"></canvas>
	<div class="container hero-v3-grid">				<div class="hero-v3-content">
					<span class="hero-eyebrow"><i class="pulse-dot"></i> <?php echo esc_html( gmx_opt( 'gmx_hero_eyebrow', 'مارکت امن گیمرهای ایران' ) ); ?></span>
					<h1 class="hero-title">
						<?php echo esc_html( gmx_opt( 'gmx_hero_title_l1', 'خرید و فروش امن' ) ); ?><br />
						<span class="grad-text"><?php echo esc_html( gmx_opt( 'gmx_hero_title_l2', 'آیتم، جم و اکانت' ) ); ?></span>
					</h1>
					<p class="hero-desc"><?php echo esc_html( gmx_opt( 'gmx_hero_desc', 'پرداخت امانی، چت مستقیم با فروشنده و تحویل سریع — همه‌چیز در یک مارکت حرفه‌ای.' ) ); ?></p>
			<form class="hero-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="search" name="s" placeholder="<?php esc_attr_e( 'دنبال چی هستی؟ جم، اکانت، آیتم...', 'gmx-theme' ); ?>" />
				<button type="submit" class="btn btn-primary"><?php esc_html_e( 'جستجو', 'gmx-theme' ); ?></button>
			</form>
			<div class="hero-mini-trust">
				<span>✅ <?php esc_html_e( 'ضمانت بازگشت وجه', 'gmx-theme' ); ?></span>
				<span>⚡ <?php esc_html_e( 'تحویل زیر ۵ دقیقه', 'gmx-theme' ); ?></span>
				<span>🎧 <?php esc_html_e( 'پشتیبانی ۲۴/۷', 'gmx-theme' ); ?></span>
			</div>					<div class="hero-stats">
						<div><strong data-gmx-count="<?php echo esc_attr( $stats_products ); ?>"><?php echo esc_html( gmx_fa_num( $stats_products ) ); ?></strong><span><?php echo esc_html( gmx_opt( 'gmx_hero_stat1_l', 'آگهی فعال' ) ); ?></span></div>
						<div><strong data-gmx-count="<?php echo esc_attr( $stats_orders ); ?>"><?php echo esc_html( gmx_fa_num( $stats_orders ) ); ?></strong><span><?php echo esc_html( gmx_opt( 'gmx_hero_stat2_l', 'سفارش موفق' ) ); ?></span></div>
						<div><strong data-gmx-count="<?php echo esc_attr( $stats_users ); ?>"><?php echo esc_html( gmx_fa_num( $stats_users ) ); ?></strong><span><?php echo esc_html( gmx_opt( 'gmx_hero_stat3_l', 'گیمر عضو' ) ); ?></span></div>
					</div>
		</div>
		<div class="hero-v3-slider">
			<div class="gmx-slider" data-gmx-slider>
				<div class="gmx-slides">
					<?php foreach ( $slides as $i => $s ) : ?>
						<div class="gmx-slide <?php echo esc_attr( $s['theme'] ); ?>" data-slide="<?php echo esc_attr( $i ); ?>">
							<div class="slide-body">
								<span class="slide-tag"><?php echo esc_html( $s['tag'] ); ?></span>
								<h3 class="slide-title"><?php echo esc_html( $s['title'] ); ?></h3>
								<p class="slide-text"><?php echo esc_html( $s['text'] ); ?></p>
								<a class="btn btn-slide" href="<?php echo esc_url( $s['link'] ); ?>"><?php echo esc_html( $s['btn'] ); ?> ←</a>
							</div>
							<span class="slide-art" aria-hidden="true"><?php echo esc_html( $s['emoji'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<button class="slide-arrow slide-next" type="button" aria-label="<?php esc_attr_e( 'بعدی', 'gmx-theme' ); ?>">‹</button>
				<button class="slide-arrow slide-prev" type="button" aria-label="<?php esc_attr_e( 'قبلی', 'gmx-theme' ); ?>">›</button>
				<div class="slide-dots" role="tablist"></div>
			</div>
		</div>
	</div>
</section>

<!-- بازی‌های محبوب -->
<?php if ( $games ) : ?>
<section class="container home-section reveal">
	<div class="section-head">
		<h2 class="section-title"><?php esc_html_e( 'بازی‌های محبوب', 'gmx-theme' ); ?></h2>
		<a class="see-all" href="<?php echo esc_url( get_post_type_archive_link( 'gmx_product' ) ); ?>"><?php esc_html_e( 'همه بازی‌ها', 'gmx-theme' ); ?> →</a>
	</div>
	<div class="games-row">
		<?php foreach ( $games as $g ) : $gi = isset( $game_icons[ $g->slug ] ) ? $game_icons[ $g->slug ] : '🎮'; ?>
			<a class="game-chip" href="<?php echo esc_url( get_term_link( $g ) ); ?>">
				<span class="game-chip-icon"><?php echo esc_html( $gi ); ?></span>
				<span class="game-chip-name"><?php echo esc_html( $g->name ); ?></span>
				<span class="game-chip-count"><?php echo esc_html( gmx_fa_num( $g->count ) ); ?> <?php esc_html_e( 'آگهی', 'gmx-theme' ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<!-- دسته‌بندی نوع -->
<?php if ( $types ) : ?>
<section class="container home-section reveal">
	<div class="type-row">
		<?php
		$icons = array( 'item' => '🗡️', 'gem' => '💎', 'account' => '👤', 'service' => '🛠️' );
		foreach ( $types as $term ) :
			$icon = isset( $icons[ $term->slug ] ) ? $icons[ $term->slug ] : '🎮';
			?>
			<a class="type-pill" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
				<span class="type-icon"><?php echo esc_html( $icon ); ?></span>
				<span class="type-name"><?php echo esc_html( $term->name ); ?></span>
				<span class="type-count"><?php echo esc_html( gmx_fa_num( $term->count ) ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<!-- محصولات با تب -->
<section class="container home-section reveal">
	<div class="section-head">
		<h2 class="section-title"><?php esc_html_e( 'آگهی‌های مارکت', 'gmx-theme' ); ?></h2>
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

<!-- پالس زنده + پیشنهاد -->
<?php if ( $recent ) : ?>
<section class="container home-section reveal">
	<div class="live-wrap">
		<div class="live-ticker">
			<h3 class="live-title"><i class="pulse-dot"></i> <?php esc_html_e( 'همین الان‌ها', 'gmx-theme' ); ?></h3>
			<div class="live-viewport" aria-live="polite">
				<ul class="live-list">
					<?php foreach ( $recent as $o ) : ?>
						<li class="live-item">
							<span class="live-ok">✔</span>
							<span class="live-text"><?php echo esc_html( wp_trim_words( $o->product_title, 6, '…' ) ); ?></span>
							<span class="live-price"><?php echo esc_html( gmx_price( $o->total ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<div class="live-deal">
			<h3 class="live-title">🔥 <?php esc_html_e( 'پیشنهاد ویژه امروز', 'gmx-theme' ); ?></h3>
			<p class="live-deal-desc"><?php esc_html_e( 'فرصت‌ها با نیمه‌شب تمام می‌شوند؛ دیر بجنب، تموم شد!', 'gmx-theme' ); ?></p>
			<div class="gmx-countdown" data-gmx-deadline="<?php echo esc_attr( $deal_deadline ); ?>">
				<div class="cd-cell"><span class="cd-num cd-h">۰۰</span><small><?php esc_html_e( 'ساعت', 'gmx-theme' ); ?></small></div>
				<span class="cd-sep">:</span>
				<div class="cd-cell"><span class="cd-num cd-m">۰۰</span><small><?php esc_html_e( 'دقیقه', 'gmx-theme' ); ?></small></div>
				<span class="cd-sep">:</span>
				<div class="cd-cell"><span class="cd-num cd-s">۰۰</span><small><?php esc_html_e( 'ثانیه', 'gmx-theme' ); ?></small></div>
			</div>
			<a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'gmx_product' ) ); ?>"><?php esc_html_e( 'دیدن پیشنهادها', 'gmx-theme' ); ?></a>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- فروشندگان برتر -->
<?php if ( $top_sellers ) : ?>
<section class="container home-section reveal">
	<div class="section-head">
		<h2 class="section-title"><?php esc_html_e( 'فروشندگان برتر', 'gmx-theme' ); ?></h2>
		<span class="top-badge">🏅 <?php esc_html_e( 'بر اساس فروش موفق', 'gmx-theme' ); ?></span>
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

<!-- مراحل -->
<section class="container home-section reveal">
	<h2 class="section-title"><?php esc_html_e( 'سه قدم تا خرید امن', 'gmx-theme' ); ?></h2>
	<div class="steps-grid steps-grid-v2">
		<div class="step-card">
			<span class="step-num"><?php echo esc_html( gmx_fa_num( 1 ) ); ?></span>
			<h3><?php esc_html_e( 'انتخاب و سفارش', 'gmx-theme' ); ?></h3>
			<p><?php esc_html_e( 'آگهی تاییدشده را انتخاب کن و مشخصات اکانتت را امن ثبت کن.', 'gmx-theme' ); ?></p>
		</div>
		<div class="step-card">
			<span class="step-num"><?php echo esc_html( gmx_fa_num( 2 ) ); ?></span>
			<h3><?php esc_html_e( 'پرداخت امانی', 'gmx-theme' ); ?></h3>
			<p><?php esc_html_e( 'مبلغ نزد سایت امانت می‌ماند تا زمانی که تحویل بگیری.', 'gmx-theme' ); ?></p>
		</div>
		<div class="step-card">
			<span class="step-num"><?php echo esc_html( gmx_fa_num( 3 ) ); ?></span>
			<h3><?php esc_html_e( 'تحویل و تسویه', 'gmx-theme' ); ?></h3>
			<p><?php esc_html_e( 'تحویل را تایید کن تا پول به کیف پول فروشنده برسد.', 'gmx-theme' ); ?></p>
		</div>
	</div>
</section>

<!-- نظرات -->
<section class="container home-section reveal">
	<h2 class="section-title"><?php esc_html_e( 'گیمرها چی می‌گویند؟', 'gmx-theme' ); ?></h2>
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

<!-- سوالات متداول -->
<section class="container home-section reveal">
	<h2 class="section-title"><?php esc_html_e( 'سوالات متداول', 'gmx-theme' ); ?></h2>
	<div class="faq-list">
		<?php foreach ( $faqs as $f ) : ?>
			<details class="faq-item">
				<summary><?php echo esc_html( $f['q'] ); ?><span class="faq-chevron">⌄</span></summary>
				<p><?php echo esc_html( $f['a'] ); ?></p>
			</details>
		<?php endforeach; ?>
	</div>
</section>

<!-- CTA پایانی -->
<section class="container home-section reveal">
	<div class="final-cta">
		<div class="final-cta-inner">
			<h2><?php esc_html_e( 'اکانتی داری که بفروش می‌رسد؟', 'gmx-theme' ); ?></h2>
			<p><?php esc_html_e( 'همین امروز آگهی‌ات را بگذار؛ لوتینو فروش را برایت امن و حرفه‌ای می‌کند.', 'gmx-theme' ); ?></p>
			<div class="hero-actions">
				<a class="btn btn-primary btn-lg btn-glow" href="<?php echo esc_url( home_url( '/my-account/seller/' ) ); ?>"><?php esc_html_e( 'ثبت آگهی فروش', 'gmx-theme' ); ?> <span class="btn-arrow">←</span></a>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
