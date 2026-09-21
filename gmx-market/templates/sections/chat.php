<?php
/**
 * بخش پیام‌ها (چت).
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$chats   = GMX_Chat::get_user_chats( $user_id );
$active  = isset( $_GET['chat'] ) ? (int) $_GET['chat'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

if ( ! $active && $chats ) {
	$active = (int) $chats[0]->id;
}
?>
<h1 class="gmx-dash-title"><?php esc_html_e( 'پیام‌ها', 'gmx-market' ); ?></h1>

<?php if ( ! $chats ) : ?>
	<div class="gmx-empty"><?php esc_html_e( 'گفتگویی وجود ندارد. گفتگو پس از اولین خرید شروع می‌شود.', 'gmx-market' ); ?></div>
<?php else : ?>
	<div class="gmx-chat-layout">
		<aside class="gmx-chat-list">
			<?php foreach ( $chats as $chat ) : ?>
				<?php
				$other_id = ( (int) $chat->buyer_id === $user_id ) ? (int) $chat->seller_id : (int) $chat->buyer_id;
				$unread   = ( (int) $chat->buyer_id === $user_id ) ? (int) $chat->seller_unread : (int) $chat->buyer_unread;
				?>
				<a class="gmx-chat-item<?php echo $active === (int) $chat->id ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'chat', (int) $chat->id, GMX_Dashboard::section_url( 'chat' ) ) ); ?>">
					<?php echo gmx_avatar( $other_id, 40 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="gmx-chat-item-body">
						<strong><?php echo esc_html( gmx_display_name( $other_id ) ); ?></strong>
						<?php if ( (int) $chat->order_id ) : ?>
							<small><?php esc_html_e( 'سفارش', 'gmx-market' ); ?> #<?php echo esc_html( gmx_fa_num( $chat->order_id ) ); ?></small>
						<?php endif; ?>
					</div>
					<?php if ( $unread ) : ?>
						<span class="gmx-badge"><?php echo esc_html( gmx_fa_num( $unread ) ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</aside>

		<div class="gmx-chat" data-chat-id="<?php echo (int) $active; ?>">
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
