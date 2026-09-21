<?php
/**
 * تک محصول گیمینگ + فرم سفارش.
 *
 * @package GMX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$product_id = get_the_ID();
	$price      = (float) get_post_meta( $product_id, '_gmx_price', true );
	$delivery   = get_post_meta( $product_id, '_gmx_delivery_time', true );
	$auto       = get_post_meta( $product_id, '_gmx_auto_delivery', true );
	$types      = get_the_terms( $product_id, 'gmx_product_type' );
	$type       = ( $types && ! is_wp_error( $types ) ) ? $types[0] : null;
	$games      = get_the_terms( $product_id, 'gmx_game' );
	$seller_id  = (int) get_post_field( 'post_author' );
	$is_logged  = is_user_logged_in();
	?>

	<section class="container page-section single-product">
		<div class="product-breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'خانه', 'gmx-theme' ); ?></a> /
			<a href="<?php echo esc_url( get_post_type_archive_link( 'gmx_product' ) ); ?>"><?php esc_html_e( 'محصولات', 'gmx-theme' ); ?></a>
			<?php if ( $type ) : ?> / <a href="<?php echo esc_url( get_term_link( $type ) ); ?>"><?php echo esc_html( $type->name ); ?></a><?php endif; ?>
		</div>

		<div class="single-product-grid">
			<div class="product-main">
				<div class="product-gallery">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'gmx-hero' ); ?>
					<?php else : ?>
						<div class="product-hero-placeholder">🎮</div>
					<?php endif; ?>
				</div>

				<div class="product-content card">
					<h1 class="product-single-title"><?php the_title(); ?></h1>
					<div class="product-tags">
						<?php if ( $type ) : ?><span class="tag"><?php echo esc_html( $type->name ); ?></span><?php endif; ?>
						<?php if ( $games && ! is_wp_error( $games ) ) : ?>
							<?php foreach ( $games as $game ) : ?>
								<span class="tag tag-game">🎯 <?php echo esc_html( $game->name ); ?></span>
							<?php endforeach; ?>
						<?php endif; ?>
						<?php if ( $delivery ) : ?><span class="tag tag-time">⏱ <?php echo esc_html( $delivery ); ?></span><?php endif; ?>
					</div>
					<div class="product-desc"><?php the_content(); ?></div>
				</div>
			</div>

			<aside class="product-sidebar">
				<div class="buy-card card">
					<div class="buy-price">
						<span class="buy-price-label"><?php esc_html_e( 'قیمت', 'gmx-theme' ); ?></span>
						<strong><?php echo $price > 0 ? esc_html( gmx_price( $price ) ) : esc_html__( 'تماس بگیرید', 'gmx-theme' ); ?></strong>
					</div>

					<?php if ( $auto ) : ?>
						<div class="buy-note buy-note-auto">⚡ <?php esc_html_e( 'تحویل خودکار — بلافاصله پس از پرداخت', 'gmx-theme' ); ?></div>
					<?php endif; ?>

					<?php if ( $delivery ) : ?>
						<div class="buy-note">⏱ <?php esc_html_e( 'زمان آماده‌سازی:', 'gmx-theme' ); ?> <?php echo esc_html( $delivery ); ?></div>
					<?php endif; ?>

					<?php $is_seller = class_exists( 'GMX_Seller' ) && GMX_Seller::is_seller( $seller_id ); ?>
					<div class="seller-box">
						<span class="seller-label"><?php esc_html_e( 'فروشنده', 'gmx-theme' ); ?></span>
						<strong><?php echo esc_html( function_exists( 'gmx_shop_name' ) ? gmx_shop_name( $seller_id ) : get_the_author() ); ?></strong>
						<?php if ( $is_seller ) : ?><span class="seller-verified" title="<?php esc_attr_e( 'فروشنده فعال مارکت', 'gmx-theme' ); ?>">✔ <?php esc_html_e( 'فروشنده تاییدشده', 'gmx-theme' ); ?></span><?php endif; ?>
					</div>

					<?php if ( $price > 0 ) : ?>
						<form class="order-form" method="post">
							<input type="hidden" name="gmx_order_action" value="create" />
							<input type="hidden" name="product_id" value="<?php echo (int) $product_id; ?>" />
							<input type="hidden" name="quantity" value="1" />
							<?php wp_nonce_field( 'gmx_order_create', 'gmx_order_nonce' ); ?>

							<?php
							/**
							 * فیلدهای اطلاعات اکانت که از خریدار پرسیده می‌شود.
							 * قابل فیلتر توسط توسعه‌دهنده.
							 */
							$account_fields = apply_filters(
								'gmx_order_account_fields',
								array(
									'game_id'   => array( 'label' => __( 'آیدی بازی (ID)', 'gmx-theme' ), 'required' => true ),
									'nickname'  => array( 'label' => __( 'نام کاربری داخل بازی', 'gmx-theme' ), 'required' => false ),
									'platform'  => array( 'label' => __( 'پلتفرم', 'gmx-theme' ), 'required' => false ),
								),
								$product_id
							);

							if ( $account_fields ) :
								?>
								<h4><?php esc_html_e( 'اطلاعات اکانت شما', 'gmx-theme' ); ?></h4>
								<?php foreach ( $account_fields as $key => $field ) : ?>
									<label class="order-field">
										<span><?php echo esc_html( $field['label'] ); ?><?php echo ! empty( $field['required'] ) ? ' *' : ''; ?></span>
										<input type="text" name="gmx_account_fields[<?php echo esc_attr( $key ); ?>]" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?> />
									</label>
								<?php endforeach; ?>
							<?php endif; ?>

							<label class="order-field">
								<span><?php esc_html_e( 'توضیحات (اختیاری)', 'gmx-theme' ); ?></span>
								<textarea name="account_note" rows="2"></textarea>
							</label>

							<?php if ( $is_logged ) : ?>
								<button class="btn btn-primary btn-block" type="submit">🛒 <?php esc_html_e( 'ثبت سفارش و پرداخت', 'gmx-theme' ); ?></button>
							<?php else : ?>
								<a class="btn btn-primary btn-block" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'برای خرید وارد شوید', 'gmx-theme' ); ?></a>
							<?php endif; ?>

							<p class="buy-guarantee">🛡 <?php esc_html_e( 'پرداخت امن — وجه تا تایید تحویل نزد سایت امانت می‌ماند.', 'gmx-theme' ); ?></p>
						</form>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</section>

	<?php
endwhile;

get_footer();
