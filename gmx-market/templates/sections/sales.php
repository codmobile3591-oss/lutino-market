<?php
/**
 * بخش فروش‌های من (فروشنده).
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();

if ( ! GMX_Seller::is_seller( $user_id ) ) {
	echo '<div class="gmx-alert gmx-alert-info">' . esc_html__( 'این بخش فقط برای فروشندگان فعال است.', 'gmx-market' ) . '</div>';
	return;
}

$stats  = GMX_Seller::stats( $user_id );
$view_id = isset( $_GET['order'] ) ? (int) $_GET['order'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<?php if ( $view_id ) :
	$order = GMX_Orders::get( $view_id );
	if ( $order && ( (int) $order->seller_id === $user_id || current_user_can( 'manage_options' ) ) ) :
		$history = GMX_Orders::get_history( $order->id );
		?>
		<div class="gmx-order-head">
			<a class="gmx-back" href="<?php echo esc_url( GMX_Dashboard::section_url( 'sales' ) ); ?>">→ <?php esc_html_e( 'فروش‌ها', 'gmx-market' ); ?></a>
			<h1 class="gmx-dash-title"><?php echo esc_html( $order->order_no ); ?></h1>
			<span class="gmx-status gmx-status-<?php echo esc_attr( gmx_order_status_class( $order->status ) ); ?>"><?php echo esc_html( gmx_order_status_label( $order->status ) ); ?></span>
		</div>

		<div class="gmx-order-grid">
			<div class="gmx-card">
				<h3><?php esc_html_e( 'سفارش', 'gmx-market' ); ?></h3>
				<ul class="gmx-meta-list">
					<li><span><?php esc_html_e( 'خریدار', 'gmx-market' ); ?>:</span> <?php echo esc_html( gmx_display_name( (int) $order->user_id ) ); ?></li>
					<li><span><?php esc_html_e( 'محصول', 'gmx-market' ); ?>:</span> <?php echo esc_html( $order->product_title ); ?></li>
					<li><span><?php esc_html_e( 'مبلغ', 'gmx-market' ); ?>:</span> <strong><?php echo esc_html( gmx_price( $order->total ) ); ?></strong></li>
					<li><span><?php esc_html_e( 'سهم شما پس از کمیسیون', 'gmx-market' ); ?>:</span> <?php echo esc_html( gmx_price( (float) $order->total - (float) $order->commission ) ); ?></li>
				</ul>

				<h3 style="margin-top:20px"><?php esc_html_e( 'تغییر وضعیت سفارش', 'gmx-market' ); ?></h3>
				<form method="post">
					<input type="hidden" name="gmx_order_action" value="status" />
					<input type="hidden" name="order_id" value="<?php echo (int) $order->id; ?>" />
					<?php wp_nonce_field( 'gmx_order_status', 'gmx_order_nonce' ); ?>
					<label><?php esc_html_e( 'وضعیت جدید', 'gmx-market' ); ?></label>
					<select name="new_status">
						<?php
						$allowed = GMX_Orders::allowed_transitions();
						foreach ( $allowed[ $order->status ] ?? array() as $st ) :
							?>
							<option value="<?php echo esc_attr( $st ); ?>"><?php echo esc_html( gmx_order_status_label( $st ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<label><?php esc_html_e( 'یادداشت (اختیاری)', 'gmx-market' ); ?></label>
					<textarea name="status_note" rows="2"></textarea>
					<label><?php esc_html_e( 'اطلاعات تحویل برای خریدار', 'gmx-market' ); ?></label>
					<textarea name="delivery_note" rows="3" placeholder="<?php esc_attr_e( 'مثلاً: یوزرنیم و پسورد اکانت...', 'gmx-market' ); ?>"></textarea>
					<button class="gmx-btn gmx-btn-primary" type="submit"><?php esc_html_e( 'ثبت تغییر', 'gmx-market' ); ?></button>
				</form>
			</div>

			<div class="gmx-card">
				<h3><?php esc_html_e( 'اطلاعات اکانت خریدار', 'gmx-market' ); ?></h3>
				<?php $info = json_decode( (string) $order->account_info, true ); ?>
				<?php if ( $info ) : ?>
					<ul class="gmx-meta-list">
						<?php foreach ( $info as $k => $v ) : ?>
							<li><span><?php echo esc_html( $k ); ?>:</span> <?php echo esc_html( $v ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="gmx-muted"><?php esc_html_e( 'اطلاعاتی وارد نشده.', 'gmx-market' ); ?></p>
				<?php endif; ?>

				<h3 style="margin-top:20px"><?php esc_html_e( 'تاریخچه', 'gmx-market' ); ?></h3>
				<ul class="gmx-timeline">
					<?php foreach ( $history as $h ) : ?>
						<li>
							<span class="gmx-timeline-dot"></span>
							<div>
								<strong><?php echo esc_html( gmx_order_status_label( $h->to_status ) ); ?></strong>
								<small><?php echo esc_html( gmx_date( $h->created_at ) ); ?></small>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

<?php else :
	$orders = GMX_Orders::get_user_orders( $user_id, 50, 0, array( 'role' => 'seller' ) );
	?>
	<h1 class="gmx-dash-title"><?php esc_html_e( 'فروش‌های من', 'gmx-market' ); ?></h1>

	<div class="gmx-stat-grid">
		<div class="gmx-stat">
			<span class="gmx-stat-value"><?php echo esc_html( gmx_fa_num( $stats->count ) ); ?></span>
			<span class="gmx-stat-label"><?php esc_html_e( 'فروش موفق', 'gmx-market' ); ?></span>
		</div>
		<div class="gmx-stat">
			<span class="gmx-stat-value"><?php echo esc_html( gmx_price( $stats->revenue ) ); ?></span>
			<span class="gmx-stat-label"><?php esc_html_e( 'درآمد تکمیل‌شده', 'gmx-market' ); ?></span>
		</div>
		<div class="gmx-stat">
			<span class="gmx-stat-value"><?php echo esc_html( gmx_fa_num( $stats->pending ) ); ?></span>
			<span class="gmx-stat-label"><?php esc_html_e( 'در انتظار انجام', 'gmx-market' ); ?></span>
		</div>
	</div>

	<?php if ( $orders ) : ?>
		<div class="gmx-table-wrap">
			<table class="gmx-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'شماره', 'gmx-market' ); ?></th>
						<th><?php esc_html_e( 'خریدار', 'gmx-market' ); ?></th>
						<th><?php esc_html_e( 'مبلغ', 'gmx-market' ); ?></th>
						<th><?php esc_html_e( 'وضعیت', 'gmx-market' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $orders as $order ) : ?>
						<tr>
							<td><?php echo esc_html( $order->order_no ); ?></td>
							<td><?php echo esc_html( gmx_display_name( (int) $order->user_id ) ); ?></td>
							<td><?php echo esc_html( gmx_price( $order->total ) ); ?></td>
							<td><span class="gmx-status gmx-status-<?php echo esc_attr( gmx_order_status_class( $order->status ) ); ?>"><?php echo esc_html( gmx_order_status_label( $order->status ) ); ?></span></td>
							<td><a class="gmx-btn gmx-btn-sm" href="<?php echo esc_url( add_query_arg( 'order', (int) $order->id, GMX_Dashboard::section_url( 'sales' ) ) ); ?>"><?php esc_html_e( 'مدیریت', 'gmx-market' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<div class="gmx-empty"><?php esc_html_e( 'فروشی ثبت نشده است.', 'gmx-market' ); ?></div>
	<?php endif; ?>
<?php endif; ?>
