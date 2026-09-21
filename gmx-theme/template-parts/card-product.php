<?php
/**
 * کارت محصول لوتینو — مطابق طرح مرجع.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id  = get_the_ID();
$price       = (float) get_post_meta( $product_id, '_gmx_price', true );
$auto        = get_post_meta( $product_id, '_gmx_auto_delivery', true );
$delivery    = get_post_meta( $product_id, '_gmx_delivery_time', true );
$types       = get_the_terms( $product_id, 'gmx_product_type' );
$type        = ( $types && ! is_wp_error( $types ) ) ? $types[0] : null;
$games       = get_the_terms( $product_id, 'gmx_game' );
$game        = ( $games && ! is_wp_error( $games ) ) ? $games[0] : null;
$seller_id   = (int) get_post_field( 'post_author' );
$type_icons  = array(
	'item'      => '🗡️',
	'gem'       => '💎',
	'account'   => '👤',
	'service'   => '🛠️',
	'gift_card' => '🎁',
);
$icon        = $type && isset( $type_icons[ $type->slug ] ) ? $type_icons[ $type->slug ] : '🎮';
?>
<article class="product-card">
	<a class="product-thumb" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'gmx-card', array( 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<span class="product-thumb-placeholder"><?php echo esc_html( $icon ); ?></span>
		<?php endif; ?>
		<?php if ( $type ) : ?>
			<span class="product-type-badge"><?php echo esc_html( $type->name ); ?></span>
		<?php endif; ?>
		<?php if ( $auto ) : ?>
			<span class="product-instant-badge">⚡ <?php esc_html_e( 'تحویل آنی', 'gmx-theme' ); ?></span>
		<?php endif; ?>
	</a>
	<div class="product-body">
		<h3 class="product-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( $game || $delivery ) : ?>
			<div class="product-meta-line">
				<?php if ( $game ) : ?><span class="pm-game">برای بازی: <strong><?php echo esc_html( $game->name ); ?></strong></span><?php endif; ?>
				<?php if ( $delivery ) : ?><span class="pm-delivery">• <?php echo esc_html( $delivery ); ?></span><?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="product-stock">
			<span class="stock-dot"></span>
			<?php esc_html_e( 'موجود', 'gmx-theme' ); ?>
			<?php if ( $auto ) : ?><span class="pm-instant">• ⚡ <?php esc_html_e( 'تحویل آنی', 'gmx-theme' ); ?></span><?php endif; ?>
		</div>
		<div class="product-footer">
			<div class="product-price-wrap">
				<?php if ( $price > 0 ) : ?>
					<span class="product-price"><?php echo esc_html( gmx_price( $price ) ); ?></span>
				<?php else : ?>
					<span class="product-price"><?php esc_html_e( 'تماس بگیرید', 'gmx-theme' ); ?></span>
				<?php endif; ?>
				<?php if ( $seller_id ) : ?><small class="product-seller"><?php echo esc_html( function_exists( 'gmx_shop_name' ) ? gmx_shop_name( $seller_id ) : get_the_author() ); ?></small><?php endif; ?>
			</div>
		</div>
		<a class="btn btn-buy" href="<?php the_permalink(); ?>">
			🛒 <?php esc_html_e( 'افزودن به سبد خرید', 'gmx-theme' ); ?>
		</a>
	</div>
</article>
