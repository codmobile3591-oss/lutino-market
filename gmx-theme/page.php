<?php
/**
 * قالب برگه.
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
	<section class="container page-section">
		<article class="single-post card">
			<h1 class="page-title"><?php the_title(); ?></h1>
			<div class="post-content"><?php the_content(); ?></div>
		</article>
	</section>
	<?php
endwhile;

get_footer();
