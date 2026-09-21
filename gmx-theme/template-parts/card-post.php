<?php
/**
 * کارت نوشته.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="product-card post-card">
	<a class="product-thumb" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'gmx-card', array( 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<span class="product-thumb-placeholder">📝</span>
		<?php endif; ?>
	</a>
	<div class="product-body">
		<h3 class="product-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="post-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
		<div class="product-footer">
			<time class="post-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( function_exists( 'gmx_date' ) ? gmx_date( get_the_date( 'U' ), false ) : get_the_date() ); ?></time>
			<a class="btn btn-ghost btn-sm" href="<?php the_permalink(); ?>"><?php esc_html_e( 'ادامه', 'gmx-theme' ); ?></a>
		</div>
	</div>
</article>
