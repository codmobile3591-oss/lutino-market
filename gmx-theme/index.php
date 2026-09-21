<?php
/**
 * قالب اصلی (فال‌بک).
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="container page-section">
	<h1 class="page-title">
		<?php
		if ( is_home() && ! is_front_page() ) {
			single_post_title();
		} elseif ( is_archive() ) {
			the_archive_title();
		} elseif ( is_search() ) {
			printf( esc_html__( 'نتایج جستجو: %s', 'gmx-theme' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
		} else {
			esc_html_e( 'آخرین مطالب', 'gmx-theme' );
		}
		?>
	</h1>

	<?php if ( have_posts() ) : ?>
		<div class="posts-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card', 'post' );
			endwhile;
			?>
		</div>
		<div class="pagination-wrap"><?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?></div>
	<?php else : ?>
		<div class="empty-state">
			<p><?php esc_html_e( 'چیزی یافت نشد.', 'gmx-theme' ); ?></p>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();
