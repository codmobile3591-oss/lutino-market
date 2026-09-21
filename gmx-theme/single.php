<?php
/**
 * تک نوشته.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="container page-section single-post-wrap">
		<article class="single-post card">
			<h1 class="page-title"><?php the_title(); ?></h1>
			<div class="post-meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( function_exists( 'gmx_date' ) ? gmx_date( get_the_date( 'U' ), false ) : get_the_date() ); ?></time>
			</div>
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="single-post-thumb"><?php the_post_thumbnail( 'gmx-hero' ); ?></div>
			<?php endif; ?>
			<div class="post-content"><?php the_content(); ?></div>
		</article>

		<?php if ( comments_open() ) : ?>
			<div class="comments-wrap card"><?php comments_template(); ?></div>
		<?php endif; ?>
	</section>
	<?php
endwhile;

get_footer();
