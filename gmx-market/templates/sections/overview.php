<?php
/**
 * بخش نمای کلی داشبورد.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id   = get_current_user_id();
$counts    = array(
	'total'   => GMX_Orders::count_user_orders( $user_id ),
	'active'  => GMX_Orders::count_user_orders( $user_id, array( 'status' => 'processing' ) ),
	'wallet'  => GMX_Wallet::balance( $user_id ),
	'tickets' => count( GMX_Tickets::get_user_tickets( $user_id ) ),
);
$orders    = GMX_Orders::get_user_orders( $user_id, 5 );
$notifs    = GMX_Notify::get_notifications( $user_id, 8 );
?>
<h1 class="gmx-dash-title"><?php esc_html_e( 'سلام', 'gmx-market' ); ?> <?php echo esc_html( gmx_display_name( $user_id ) ); ?> 👋</h1>

<div class="gmx-stat-grid">
	<a class="gmx-stat" href="<?php echo esc_url( GMX_Dashboard::section_url( 'orders' ) ); ?>">
		<span class="gmx-stat-value"><?php echo esc_html( gmx_fa_num( $counts['total'] ) ); ?></span>
		<span class="gmx-stat-label"><?php esc_html_e( 'کل سفارش‌ها', 'gmx-market' ); ?></span>
	</a>
	<a class="gmx-stat" href="<?php echo esc_url( GMX_Dashboard::section_url( 'orders' ) ); ?>">
		<span class="gmx-stat-value"><?php echo esc_html( gmx_fa_num( $counts['active'] ) ); ?></span>
		<span class="gmx-stat-label"><?php esc_html_e( 'در حال انجام', 'gmx-market' ); ?></span>
	</a>
	<a class="gmx-stat" href="<?php echo esc_url( GMX_Dashboard::section_url( 'wallet' ) ); ?>">
		<span class="gmx-stat-value"><?php echo esc_html( gmx_price( $counts['wallet'] ) ); ?></span>
		<span class="gmx-stat-label"><?php esc_html_e( 'موجودی کیف پول', 'gmx-market' ); ?></span>
	</a>
	<a class="gmx-stat" href="<?php echo esc_url( GMX_Dashboard::section_url( 'tickets' ) ); ?>">
		<span class="gmx-stat-value"><?php echo esc_html( gmx_fa_num( $counts['tickets'] ) ); ?></span>
		<span class="gmx-stat-label"><?php esc_html_e( 'تیکت‌ها', 'gmx-market' ); ?></span>
	</a>
</div>

<h2 class="gmx-dash-subtitle"><?php esc_html_e( 'آخرین سفارش‌ها', 'gmx-market' ); ?></h2>
<?php if ( $orders ) : ?>
	<div class="gmx-table-wrap">
		<table class="gmx-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'شماره', 'gmx-market' ); ?></th>
					<th><?php esc_html_e( 'محصول', 'gmx-market' ); ?></th>
					<th><?php esc_html_e( 'مبلغ', 'gmx-market' ); ?></th>
					<th><?php esc_html_e( 'وضعیت', 'gmx-market' ); ?></th>
					<th><?php esc_html_e( 'تاریخ', 'gmx-market' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $orders as $order ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( add_query_arg( 'order', (int) $order->id, GMX_Dashboard::section_url( 'orders' ) ) ); ?>"><?php echo esc_html( $order->order_no ); ?></a></td>
						<td><?php echo esc_html( $order->product_title ); ?></td>
						<td><?php echo esc_html( gmx_price( $order->total ) ); ?></td>
						<td><span class="gmx-status gmx-status-<?php echo esc_attr( gmx_order_status_class( $order->status ) ); ?>"><?php echo esc_html( gmx_order_status_label( $order->status ) ); ?></span></td>
						<td><?php echo esc_html( gmx_date( $order->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="gmx-empty"><?php esc_html_e( 'هنوز سفارشی ثبت نکرده‌اید.', 'gmx-market' ); ?></div>
<?php endif; ?>

<?php if ( $notifs ) : ?>
	<h2 class="gmx-dash-subtitle"><?php esc_html_e( 'اعلان‌های اخیر', 'gmx-market' ); ?></h2>
	<ul class="gmx-notif-list">
		<?php foreach ( $notifs as $notif ) : ?>
			<li>
				<?php if ( $notif->link ) : ?><a href="<?php echo esc_url( $notif->link ); ?>"><?php endif; ?>
				<?php echo esc_html( $notif->title ); ?>
				<small><?php echo esc_html( gmx_time_diff_fa( $notif->created_at ) ); ?></small>
				<?php if ( $notif->link ) : ?></a><?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
