<?php
/**
 * بخش سفارش‌های من.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();

// نمایش جزئیات یک سفارش خاص.
$view_id = isset( $_GET['order'] ) ? (int) $_GET['order'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

if ( $view_id ) :
	$order = GMX_Orders::get( $view_id );

	if ( ! $order || ! GMX_Orders::user_can_view( $order ) ) :
		?>
		<div class="gmx-alert gmx-alert-danger"><?php esc_html_e( 'سفارش یافت نشد یا دسترسی ندارید.', 'gmx-market' ); ?></div>
		<a class="gmx-btn" href="<?php echo esc_url( GMX_Dashboard::section_url( 'orders' ) ); ?>"><?php esc_html_e( 'بازگشت به لیست', 'gmx-market' ); ?></a>
		<?php
	else :
		$account_info = json_decode( (string) $order->account_info, true );
		$history      = GMX_Orders::get_history( $order->id );
		$error        = get_transient( 'gmx_order_error_' . $user_id );
		delete_transient( 'gmx_order_error_' . $user_id );
		?>
		<?php if ( $error ) : ?>
			<div class="gmx-alert gmx-alert-danger"><?php echo esc_html( $error ); ?></div>
		<?php endif; ?>

		<?php if ( isset( $_GET['payment'] ) && 'success' === $_GET['payment'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="gmx-alert gmx-alert-success"><?php esc_html_e( 'پرداخت با موفقیت انجام شد. ✅', 'gmx-market' ); ?></div>
		<?php endif; ?>

		<div class="gmx-order-head">
			<a class="gmx-back" href="<?php echo esc_url( GMX_Dashboard::section_url( 'orders' ) ); ?>">→ <?php esc_html_e( 'سفارش‌ها', 'gmx-market' ); ?></a>
			<h1 class="gmx-dash-title"><?php echo esc_html( $order->order_no ); ?></h1>
			<span class="gmx-status gmx-status-<?php echo esc_attr( gmx_order_status_class( $order->status ) ); ?>"><?php echo esc_html( gmx_order_status_label( $order->status ) ); ?></span>
		</div>

		<div class="gmx-order-grid">
			<div class="gmx-card">
				<h3><?php esc_html_e( 'جزئیات سفارش', 'gmx-market' ); ?></h3>
				<ul class="gmx-meta-list">
					<li><span><?php esc_html_e( 'محصول', 'gmx-market' ); ?>:</span> <?php echo esc_html( $order->product_title ); ?></li>
					<li><span><?php esc_html_e( 'نوع', 'gmx-market' ); ?>:</span> <?php echo esc_html( gmx_product_type_label( $order->product_type ) ); ?></li>
					<li><span><?php esc_html_e( 'تعداد', 'gmx-market' ); ?>:</span> <?php echo esc_html( gmx_fa_num( $order->quantity ) ); ?></li>
					<li><span><?php esc_html_e( 'مبلغ کل', 'gmx-market' ); ?>:</span> <strong><?php echo esc_html( gmx_price( $order->total ) ); ?></strong></li>
					<li><span><?php esc_html_e( 'تاریخ', 'gmx-market' ); ?>:</span> <?php echo esc_html( gmx_date( $order->created_at ) ); ?></li>
				</ul>

				<?php if ( $account_info ) : ?>
					<h4><?php esc_html_e( 'اطلاعات اکانت شما', 'gmx-market' ); ?></h4>
					<ul class="gmx-meta-list">
						<?php foreach ( $account_info as $k => $v ) : ?>
							<li><span><?php echo esc_html( $k ); ?>:</span> <?php echo esc_html( $v ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( 'pending_payment' === $order->status ) : ?>
					<div class="gmx-order-actions">
						<form method="post" style="display:inline">
							<input type="hidden" name="gmx_order_action" value="pay" />
							<input type="hidden" name="order_id" value="<?php echo (int) $order->id; ?>" />
							<?php wp_nonce_field( 'gmx_order_pay', 'gmx_order_nonce' ); ?>
							<button class="gmx-btn gmx-btn-primary" type="submit"><?php esc_html_e( 'پرداخت آنلاین', 'gmx-market' ); ?></button>
						</form>
						<form method="post" style="display:inline">
							<input type="hidden" name="gmx_order_action" value="cancel" />
							<input type="hidden" name="order_id" value="<?php echo (int) $order->id; ?>" />
							<?php wp_nonce_field( 'gmx_order_cancel', 'gmx_order_nonce' ); ?>
							<button class="gmx-btn gmx-btn-ghost" type="submit" data-gmx-confirm="<?php esc_attr_e( 'سفارش لغو شود؟', 'gmx-market' ); ?>"><?php esc_html_e( 'لغو سفارش', 'gmx-market' ); ?></button>
						</form>
					</div>
				<?php endif; ?>
			</div>

			<div class="gmx-card">
				<h3><?php esc_html_e( 'اطلاعات تحویل', 'gmx-market' ); ?></h3>
				<?php if ( $order->delivery_note ) : ?>
					<div class="gmx-delivery-box"><?php echo esc_html( $order->delivery_note ); ?></div>
				<?php elseif ( in_array( $order->status, array( 'pending_payment', 'cancelled' ), true ) ) : ?>
					<p class="gmx-muted"><?php esc_html_e( 'پس از پرداخت و انجام سفارش، اطلاعات تحویل اینجا نمایش داده می‌شود.', 'gmx-market' ); ?></p>
				<?php else : ?>
					<p class="gmx-muted"><?php esc_html_e( 'در حال آماده‌سازی...', 'gmx-market' ); ?></p>
				<?php endif; ?>

				<h3 style="margin-top:20px"><?php esc_html_e( 'تاریخچه سفارش', 'gmx-market' ); ?></h3>
				<ul class="gmx-timeline">
					<?php foreach ( $history as $h ) : ?>
						<li>
							<span class="gmx-timeline-dot"></span>
							<div>
								<?php if ( $h->to_status ) : ?>
									<strong><?php echo esc_html( gmx_order_status_label( $h->to_status ) ); ?></strong>
								<?php endif; ?>
								<?php if ( $h->note ) : ?> — <?php echo esc_html( $h->note ); ?><?php endif; ?>
								<small><?php echo esc_html( gmx_date( $h->created_at ) ); ?></small>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<?php
		// گفتگوی چت متصل به سفارش (در صورت وجود).
		$chat_id = $GLOBALS['wpdb']->get_var(
			$GLOBALS['wpdb']->prepare( 'SELECT id FROM ' . $GLOBALS['wpdb']->prefix . 'gmx_chats WHERE order_id = %d', (int) $order->id )
		);
		if ( $chat_id ) :
			?>
			<div class="gmx-card">
				<h3><?php esc_html_e( 'گفتگوی این سفارش', 'gmx-market' ); ?></h3>
				<div class="gmx-chat" data-chat-id="<?php echo (int) $chat_id; ?>">
					<div class="gmx-chat-messages" id="gmx-chat-messages"></div>
					<form class="gmx-chat-form" id="gmx-chat-form" enctype="multipart/form-data">
						<input type="text" name="body" placeholder="<?php esc_attr_e( 'پیام خود را بنویسید...', 'gmx-market' ); ?>" autocomplete="off" />
						<label class="gmx-chat-attach" title="<?php esc_attr_e( 'پیوست فایل', 'gmx-market' ); ?>">
							📎<input type="file" name="file" accept="image/*,.pdf,.zip,.txt" hidden />
						</label>
						<button class="gmx-btn gmx-btn-primary" type="submit"><?php esc_html_e( 'ارسال', 'gmx-market' ); ?></button>
					</form>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>

<?php else :
	$orders = GMX_Orders::get_user_orders( $user_id, 50 );
	?>
	<h1 class="gmx-dash-title"><?php esc_html_e( 'سفارش‌های من', 'gmx-market' ); ?></h1>

	<?php if ( $orders ) : ?>
		<div class="gmx-table-wrap">
			<table class="gmx-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'شماره', 'gmx-market' ); ?></th>
						<th><?php esc_html_e( 'محصول', 'gmx-market' ); ?></th>
						<th><?php esc_html_e( 'نوع', 'gmx-market' ); ?></th>
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
							<td><?php echo esc_html( gmx_product_type_label( $order->product_type ) ); ?></td>
							<td><?php echo esc_html( gmx_price( $order->total ) ); ?></td>
							<td><span class="gmx-status gmx-status-<?php echo esc_attr( gmx_order_status_class( $order->status ) ); ?>"><?php echo esc_html( gmx_order_status_label( $order->status ) ); ?></span></td>
							<td><?php echo esc_html( gmx_date( $order->created_at, false ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<div class="gmx-empty">
			<p><?php esc_html_e( 'هنوز سفارشی ندارید.', 'gmx-market' ); ?></p>
			<a class="gmx-btn gmx-btn-primary" href="<?php echo esc_url( home_url( '/product/' ) ); ?>"><?php esc_html_e( 'مشاهده محصولات', 'gmx-market' ); ?></a>
		</div>
	<?php endif; ?>
<?php endif; ?>
