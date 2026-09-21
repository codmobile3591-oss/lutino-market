<?php
/**
 * بخش فروشنده: فعال‌سازی، ثبت و مدیریت آگهی.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id     = get_current_user_id();
$is_seller   = GMX_Seller::is_seller( $user_id );
$ok_msg      = get_transient( 'gmx_seller_ok_' . $user_id );
$err_msg     = get_transient( 'gmx_seller_err_' . $user_id );
$rest       = (string) get_query_var( 'gmx_dash_rest' );
$creating   = ( isset( $_GET['action'] ) && 'new' === $_GET['action'] ) || ( '' !== $rest && false !== strpos( $rest, 'new' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$edit_id     = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$editing     = $edit_id && get_post( $edit_id ) && (int) get_post( $edit_id )->post_author === $user_id;
$my_products = array();

if ( $is_seller ) {
	$my_products = get_posts(
		array(
			'post_type'      => 'gmx_product',
			'author'         => $user_id,
			'posts_per_page' => 30,
			'post_status'    => array( 'pending', 'publish', 'draft' ),
		)
	);
}

delete_transient( 'gmx_seller_ok_' . $user_id );
delete_transient( 'gmx_seller_err_' . $user_id );
?>

<?php if ( $ok_msg ) : ?>
	<div class="gmx-alert gmx-alert-ok"><?php echo esc_html( $ok_msg ); ?></div>
<?php endif; ?>
<?php if ( $err_msg ) : ?>
	<div class="gmx-alert gmx-alert-err"><?php echo esc_html( $err_msg ); ?></div>
<?php endif; ?>

<?php if ( ! $is_seller ) : ?>

	<h1 class="gmx-dash-title"><?php esc_html_e( 'فروشنده شو', 'gmx-market' ); ?></h1>
	<div class="gmx-card gmx-seller-apply">
		<h3><?php esc_html_e( 'با فروش در لوتینو درآمد داشته باش', 'gmx-market' ); ?></h3>
		<p class="gmx-muted"><?php esc_html_e( 'آگهی‌ات را ثبت کن، ما امنیت پرداخت امانی، چت با خریدار و تسویه خودکار را فراهم می‌کنیم.', 'gmx-market' ); ?></p>
		<ul class="gmx-benefits">
			<li>🛡️ <?php esc_html_e( 'پرداخت امانی — بدون ریسک اسکم', 'gmx-market' ); ?></li>
			<li>💬 <?php esc_html_e( 'چت مستقیم با خریدار داخل سایت', 'gmx-market' ); ?></li>
			<li>💸 <?php esc_html_e( 'تسویه خودکار به کیف پول پس از تکمیل سفارش', 'gmx-market' ); ?></li>
		</ul>
		<form method="post">
			<input type="hidden" name="gmx_seller_apply" value="1" />
			<?php wp_nonce_field( 'gmx_seller_apply', 'gmx_seller_apply_nonce' ); ?>
			<label><?php esc_html_e( 'نام فروشگاه (اختیاری)', 'gmx-market' ); ?></label>
			<input type="text" name="shop_name" placeholder="<?php esc_attr_e( 'اگر فروشگاه نداری خالی بگذار — با اسم خودت فروش می‌کنی', 'gmx-market' ); ?>" />
			<label><?php esc_html_e( 'شماره تماس', 'gmx-market' ); ?></label>
			<input type="tel" name="shop_phone" placeholder="۰۹xxxxxxxxx" />
			<button class="gmx-btn gmx-btn-primary" type="submit"><?php esc_html_e( 'فعال‌سازی حساب فروشنده', 'gmx-market' ); ?></button>
		</form>
	</div>

<?php else : ?>

	<div class="gmx-order-head">
		<h1 class="gmx-dash-title"><?php esc_html_e( 'آگهی‌های من', 'gmx-market' ); ?></h1>
		<a class="gmx-btn gmx-btn-primary" href="<?php echo esc_url( GMX_Dashboard::section_url( 'seller', 'new' ) ); ?>">＋ <?php esc_html_e( 'ثبت آگهی جدید', 'gmx-market' ); ?></a>
	</div>

	<?php if ( $creating || $editing ) : ?>
		<?php
		$p        = $editing ? get_post( $edit_id ) : null;
		$price    = $p ? (float) get_post_meta( $p->ID, '_gmx_price', true ) : '';
		$stock    = $p ? (int) get_post_meta( $p->ID, '_gmx_stock', true ) : '';
		$dtime    = $p ? (string) get_post_meta( $p->ID, '_gmx_delivery_time', true ) : '';
		$autodel  = $p ? (string) get_post_meta( $p->ID, '_gmx_auto_delivery', true ) : '';
		$creds    = $p ? (string) get_post_meta( $p->ID, '_gmx_credentials', true ) : '';
		$p_types  = get_the_terms( $p ? $p->ID : 0, 'gmx_product_type' );
		$cur_type = ( $p_types && ! is_wp_error( $p_types ) ) ? $p_types[0]->slug : 'item';
		?>
		<div class="gmx-card">
			<a class="gmx-back" href="<?php echo esc_url( GMX_Dashboard::section_url( 'seller' ) ); ?>">→ <?php esc_html_e( 'بازگشت', 'gmx-market' ); ?></a>
			<h3><?php echo $editing ? esc_html__( 'ویرایش آگهی', 'gmx-market' ) : esc_html__( 'ثبت آگهی جدید', 'gmx-market' ); ?></h3>
			<form method="post" enctype="multipart/form-data" class="gmx-form">
				<input type="hidden" name="gmx_seller_product" value="1" />
				<?php if ( $editing ) : ?><input type="hidden" name="edit_id" value="<?php echo (int) $edit_id; ?>" /><?php endif; ?>
				<?php wp_nonce_field( 'gmx_seller_product', 'gmx_seller_nonce' ); ?>

				<div class="gmx-form-row">
					<div>
						<label><?php esc_html_e( 'عنوان آگهی', 'gmx-market' ); ?> <span class="gmx-req">*</span></label>
						<input type="text" name="title" required maxlength="120" value="<?php echo $p ? esc_attr( $p->post_title ) : ''; ?>" placeholder="<?php esc_attr_e( 'مثلاً: ۵۰۰۰ جم فری فایر — تحویل آنی', 'gmx-market' ); ?>" />
					</div>
					<div>
						<label><?php esc_html_e( 'بازی', 'gmx-market' ); ?></label>
						<select name="game">
							<option value=""><?php esc_html_e( '— انتخاب کنید —', 'gmx-market' ); ?></option>
							<?php
							$games = get_terms( array( 'taxonomy' => 'gmx_game', 'hide_empty' => false ) );
							if ( ! is_wp_error( $games ) ) {
								$p_games = $p ? get_the_terms( $p->ID, 'gmx_game' ) : array();
								$p_game  = ( $p_games && ! is_wp_error( $p_games ) ) ? $p_games[0]->term_id : 0;
								foreach ( $games as $g ) {
									echo '<option value="' . esc_attr( $g->term_id ) . '"' . selected( $p_game, $g->term_id, false ) . '>' . esc_html( $g->name ) . '</option>';
								}
							}
							?>
						</select>
					</div>
				</div>

				<div class="gmx-form-row">
					<div>
						<label><?php esc_html_e( 'نوع محصول', 'gmx-market' ); ?></label>
						<select name="product_type">
							<?php foreach ( array( 'item' => 'آیتم', 'gem' => 'جم و ارز', 'account' => 'اکانت', 'service' => 'خدمات', 'gift_card' => 'گیفت کارت' ) as $k => $lbl ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $cur_type, $k ); ?>><?php echo esc_html( $lbl ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label><?php esc_html_e( 'قیمت (تومان)', 'gmx-market' ); ?> <span class="gmx-req">*</span></label>
						<input type="number" name="price" required min="1000" step="1000" value="<?php echo esc_attr( $price ); ?>" placeholder="100000" />
					</div>
				</div>

				<div class="gmx-form-row">
					<div>
						<label><?php esc_html_e( 'موجودی (تعداد قابل فروش)', 'gmx-market' ); ?></label>
						<input type="number" name="stock" min="1" value="<?php echo esc_attr( $stock ); ?>" placeholder="10" />
					</div>
					<div>
						<label><?php esc_html_e( 'زمان تحویل اعلامی', 'gmx-market' ); ?></label>
						<input type="text" name="delivery_time" value="<?php echo esc_attr( $dtime ); ?>" placeholder="<?php esc_attr_e( 'مثلاً: زیر ۵ دقیقه', 'gmx-market' ); ?>" />
					</div>
				</div>

				<label><?php esc_html_e( 'توضیحات آگهی', 'gmx-market' ); ?></label>
				<textarea name="description" rows="5" placeholder="<?php esc_attr_e( 'جزئیات محصول، شرایط تحویل و...', 'gmx-market' ); ?>"><?php echo $p ? esc_textarea( $p->post_content ) : ''; ?></textarea>

				<label class="gmx-checkbox">
					<input type="checkbox" name="auto_delivery" value="1"<?php checked( $autodel, '1' ); ?> />
					<?php esc_html_e( 'تحویل خودکار (اطلاعات زیر بلافاصله پس از پرداخت به خریدار داده می‌شود)', 'gmx-market' ); ?>
				</label>
				<label class="gmx-cred-label"><?php esc_html_e( 'اطلاعات تحویل خودکار', 'gmx-market' ); ?></label>
				<textarea name="credentials" rows="3" placeholder="<?php esc_attr_e( 'کد یا اطلاعات اکانت — فقط برای خریدار نمایش داده می‌شود', 'gmx-market' ); ?>"><?php echo esc_textarea( $creds ); ?></textarea>

				<label><?php esc_html_e( 'تصویر محصول', 'gmx-market' ); ?></label>
				<input type="file" name="image" accept="image/*" />

				<button class="gmx-btn gmx-btn-primary" type="submit"><?php echo $editing ? esc_html__( 'ذخیره تغییرات', 'gmx-market' ) : esc_html__( 'ثبت آگهی', 'gmx-market' ); ?></button>
			</form>
		</div>

	<?php else : ?>

		<?php if ( $my_products ) : ?>
			<div class="gmx-table-wrap">
				<table class="gmx-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'آگهی', 'gmx-market' ); ?></th>
							<th><?php esc_html_e( 'قیمت', 'gmx-market' ); ?></th>
							<th><?php esc_html_e( 'وضعیت', 'gmx-market' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $my_products as $mp ) :
							$mp_price = (float) get_post_meta( $mp->ID, '_gmx_price', true );
							?>
							<tr>
								<td><a href="<?php echo esc_url( get_permalink( $mp ) ); ?>"><?php echo esc_html( get_the_title( $mp ) ); ?></a></td>
								<td><?php echo esc_html( gmx_price( $mp_price ) ); ?></td>
								<td>
									<?php if ( 'publish' === $mp->post_status ) : ?>
										<span class="gmx-status gmx-status-completed"><?php esc_html_e( 'منتشر شده', 'gmx-market' ); ?></span>
									<?php elseif ( 'pending' === $mp->post_status ) : ?>
										<span class="gmx-status gmx-status-processing"><?php esc_html_e( 'در انتظار تایید', 'gmx-market' ); ?></span>
									<?php else : ?>
							<span class="gmx-status gmx-status-cancelled"><?php echo esc_html( get_post_status_object( $mp->post_status )->label ); ?></span>
									<?php endif; ?>
								</td>
								<td><a class="gmx-btn gmx-btn-sm" href="<?php echo esc_url( add_query_arg( 'edit', (int) $mp->ID, GMX_Dashboard::section_url( 'seller' ) ) ); ?>"><?php esc_html_e( 'ویرایش', 'gmx-market' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="gmx-empty"><?php esc_html_e( 'هنوز آگهی‌ای ثبت نکرده‌ای.', 'gmx-market' ); ?></div>
		<?php endif; ?>

	<?php endif; ?>
<?php endif; ?>
