<?php
/**
 * نتایج جستجو.
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
		printf(
			/* translators: %s: search query */
			esc_html__( 'نتایج جستجو برای: %s', 'gmx-theme' ),
			'<span>' . esc_html( get_search_query() ) . '</span>'
		);
		?>
	</h1>

	<?php if ( have_posts() ) : ?>
		<div class="posts-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				if ( 'gmx_product' === get_post_type() ) {
					get_template_part( 'template-parts/card', 'product' );
				} else {
					get_template_part( 'template-parts/card', 'post' );
				}
			endwhile;
			?>
		</div>
		<div class="pagination-wrap"><?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?></div>
	<?php else : ?>
		<div class="empty-state">
			<p><?php esc_html_e( 'نتیجه‌ای یافت نشد. عبارت دیگری را امتحان کنید.', 'gmx-theme' ); ?></p>
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="search-form-inline">
				<input type="search" name="s" placeholder="<?php esc_attr_e( 'جستجو...', 'gmx-theme' ); ?>" />
				<button class="btn btn-primary" type="submit"><?php esc_html_e( 'جستجو', 'gmx-theme' ); ?></button>
			</form>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();
