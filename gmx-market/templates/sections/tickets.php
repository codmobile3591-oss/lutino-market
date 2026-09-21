<?php
/**
 * بخش تیکت‌های پشتیبانی.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id  = get_current_user_id();
$tickets  = GMX_Tickets::get_user_tickets( $user_id );
$view_id  = isset( $_GET['ticket'] ) ? (int) $_GET['ticket'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$error    = get_transient( 'gmx_ticket_error_' . $user_id );
delete_transient( 'gmx_ticket_error_' . $user_id );
$cats     = GMX_Tickets::categories();
?>

<?php if ( $error ) : ?>
	<div class="gmx-alert gmx-alert-danger"><?php echo esc_html( $error ); ?></div>
<?php endif; ?>

<?php if ( $view_id ) :
	$ticket = GMX_Tickets::get( $view_id );
	if ( $ticket && GMX_Tickets::user_can_view( $ticket ) ) :
		$messages = GMX_Tickets::get_messages( $ticket->id );
		?>
		<div class="gmx-order-head">
			<a class="gmx-back" href="<?php echo esc_url( GMX_Dashboard::section_url( 'tickets' ) ); ?>">→ <?php esc_html_e( 'تیکت‌ها', 'gmx-market' ); ?></a>
			<h1 class="gmx-dash-title"><?php echo esc_html( $ticket->subject ); ?></h1>
			<span class="gmx-status gmx-status-info"><?php echo esc_html( gmx_ticket_status_label( $ticket->status ) ); ?></span>
		</div>

		<div class="gmx-ticket-thread">
			<?php foreach ( $messages as $msg ) : ?>
				<?php $is_staff = user_can( (int) $msg->sender_id, 'gmx_manage_tickets' ) || user_can( (int) $msg->sender_id, 'manage_options' ); ?>
				<div class="gmx-ticket-msg<?php echo $is_staff ? ' is-staff' : ''; ?>">
					<div class="gmx-ticket-msg-head">
						<?php echo gmx_avatar( (int) $msg->sender_id, 32 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<strong><?php echo esc_html( gmx_display_name( (int) $msg->sender_id ) ); ?></strong>
						<?php if ( $is_staff ) : ?><span class="gmx-tag-staff"><?php esc_html_e( 'پشتیبانی', 'gmx-market' ); ?></span><?php endif; ?>
						<small><?php echo esc_html( gmx_date( $msg->created_at ) ); ?></small>
					</div>
					<div class="gmx-ticket-msg-body"><?php echo wp_kses_post( wpautop( $msg->body ) ); ?></div>
					<?php if ( $msg->attachment_id ) : ?>
						<div class="gmx-ticket-msg-attach"><?php echo GMX_Uploads::attachment_html( (int) $msg->attachment_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( 'closed' !== $ticket->status ) : ?>
			<form class="gmx-card gmx-reply-form" method="post" enctype="multipart/form-data">
				<input type="hidden" name="gmx_ticket_action" value="reply" />
				<input type="hidden" name="ticket_id" value="<?php echo (int) $ticket->id; ?>" />
				<?php wp_nonce_field( 'gmx_ticket_reply', 'gmx_ticket_nonce' ); ?>
				<textarea name="body" rows="4" required placeholder="<?php esc_attr_e( 'پاسخ خود را بنویسید...', 'gmx-market' ); ?>"></textarea>
				<div class="gmx-form-row">
					<label class="gmx-file-label">📎 <?php esc_html_e( 'پیوست تصویر/فایل', 'gmx-market' ); ?><input type="file" name="gmx_ticket_file" accept="image/*,.pdf,.zip,.txt" /></label>
					<button class="gmx-btn gmx-btn-primary" type="submit"><?php esc_html_e( 'ارسال پاسخ', 'gmx-market' ); ?></button>
				</div>
			</form>
		<?php else : ?>
			<div class="gmx-alert gmx-alert-info"><?php esc_html_e( 'این تیکت بسته شده است.', 'gmx-market' ); ?></div>
		<?php endif; ?>
	<?php endif; ?>

<?php else : ?>
	<div class="gmx-orders-grid-page">
		<div class="gmx-ticket-list-col">
			<h1 class="gmx-dash-title"><?php esc_html_e( 'تیکت‌های پشتیبانی', 'gmx-market' ); ?></h1>
			<?php if ( $tickets ) : ?>
				<ul class="gmx-ticket-list">
					<?php foreach ( $tickets as $t ) : ?>
						<li>
							<a href="<?php echo esc_url( add_query_arg( 'ticket', (int) $t->id, GMX_Dashboard::section_url( 'tickets' ) ) ); ?>">
								<strong>#<?php echo esc_html( $t->ticket_no ); ?> — <?php echo esc_html( $t->subject ); ?></strong>
								<span class="gmx-status gmx-status-info"><?php echo esc_html( gmx_ticket_status_label( $t->status ) ); ?></span>
								<small><?php echo esc_html( gmx_date( $t->updated_at ?? $t->created_at, false ) ); ?></small>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div class="gmx-empty"><?php esc_html_e( 'تیکتی ثبت نشده است.', 'gmx-market' ); ?></div>
			<?php endif; ?>
		</div>

		<div class="gmx-ticket-form-col">
			<h2 class="gmx-dash-subtitle"><?php esc_html_e( 'تیکت جدید', 'gmx-market' ); ?></h2>
			<form class="gmx-card" method="post" enctype="multipart/form-data">
				<input type="hidden" name="gmx_ticket_action" value="new" />
				<?php wp_nonce_field( 'gmx_ticket_new', 'gmx_ticket_nonce' ); ?>

				<label><?php esc_html_e( 'موضوع', 'gmx-market' ); ?> *</label>
				<input type="text" name="subject" required />

				<label><?php esc_html_e( 'دسته‌بندی', 'gmx-market' ); ?></label>
				<select name="category">
					<?php foreach ( $cats as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<label><?php esc_html_e( 'سفارش مرتبط (اختیاری)', 'gmx-market' ); ?></label>
				<select name="order_id">
					<option value="0"><?php esc_html_e( '—', 'gmx-market' ); ?></option>
					<?php foreach ( GMX_Orders::get_user_orders( $user_id, 50 ) as $o ) : ?>
						<option value="<?php echo (int) $o->id; ?>"><?php echo esc_html( $o->order_no . ' — ' . $o->product_title ); ?></option>
					<?php endforeach; ?>
				</select>

				<label><?php esc_html_e( 'متن تیکت', 'gmx-market' ); ?> *</label>
				<textarea name="body" rows="5" required></textarea>

				<label class="gmx-file-label">📎 <?php esc_html_e( 'پیوست تصویر/فایل', 'gmx-market' ); ?><input type="file" name="gmx_ticket_file" accept="image/*,.pdf,.zip,.txt" /></label>

				<button class="gmx-btn gmx-btn-primary" type="submit"><?php esc_html_e( 'ثبت تیکت', 'gmx-market' ); ?></button>
			</form>
		</div>
	</div>
<?php endif; ?>
