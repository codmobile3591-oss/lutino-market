<?php
/**
 * چیدمان مشترک مارکت: آرشیو محصولات + آرشیو تکسونومی‌ها.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// عنوان هوشمند بر اساس نوع صفحه.
$title = __( 'مارکت محصولات', 'gmx-theme' );
if ( is_tax( 'gmx_product_type' ) ) {
	$term  = get_queried_object();
	$title = $term ? $term->name : $title;
} elseif ( is_tax( 'gmx_game' ) ) {
	$term  = get_queried_object();
	$title = sprintf( __( 'بازی %s', 'gmx-theme' ), $term ? $term->name : '' );
}

$current_term_id = is_tax() ? (int) get_queried_object_id() : 0;
?>
<section class="container page-section">
	<div class="section-head">
		<h1 class="page-title"><?php echo esc_html( $title ); ?></h1>
		<span class="archive-count"><?php echo esc_html( gmx_fa_num( (int) $GLOBALS['wp_query']->found_posts ) ); ?> <?php esc_html_e( 'آگهی', 'gmx-theme' ); ?></span>
	</div>

	<div class="archive-layout">
		<aside class="archive-sidebar">
			<div class="filter-card">
				<h4><?php esc_html_e( 'نوع محصول', 'gmx-theme' ); ?></h4>
				<ul class="filter-list">
					<li><a class="<?php echo 0 === $current_term_id ? 'is-active' : ''; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'gmx_product' ) ); ?>"><?php esc_html_e( 'همه', 'gmx-theme' ); ?></a></li>
					<?php foreach ( gmx_theme_product_types() as $term ) : ?>
						<li><a class="<?php echo (int) $term->term_id === $current_term_id ? 'is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?> <small>(<?php echo esc_html( gmx_fa_num( $term->count ) ); ?>)</small></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<?php
			// فقط بازی‌هایی که حداقل یک آگهی دارند (تا فیلتر به صفحه خالی/۴۰۴ نرسد).
			$games = get_terms(
				array(
					'taxonomy'   => 'gmx_game',
					'hide_empty' => true,
					'number'     => 12,
				)
			);
			if ( ! is_wp_error( $games ) && $games ) :
				?>
				<div class="filter-card">
					<h4><?php esc_html_e( 'بازی‌ها', 'gmx-theme' ); ?></h4>
					<ul class="filter-list">
						<?php foreach ( $games as $game ) : ?>
							<li><a class="<?php echo (int) $game->term_id === $current_term_id ? 'is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $game ) ); ?>"><?php echo esc_html( $game->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</aside>

		<div class="archive-content">
			<?php if ( have_posts() ) : ?>
				<div class="posts-grid products-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/card', 'product' );
					endwhile;
					?>
				</div>
				<div class="pagination-wrap"><?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?></div>
			<?php else : ?>
				<div class="empty-state">
					<p><?php esc_html_e( 'فعلاً آگهی‌ای اینجا نیست — اولین فروشنده باش!', 'gmx-theme' ); ?></p>
					<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/my-account/seller/' ) ); ?>"><?php esc_html_e( 'ثبت آگهی', 'gmx-theme' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
get_footer();
