<?php
/**
 * بخش کیف پول.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id    = get_current_user_id();
$balance    = GMX_Wallet::balance( $user_id );
$trans      = GMX_Wallet::get_transactions( $user_id, 30 );
$is_seller  = GMX_Seller::is_seller();
$withdrawals = $is_seller ? GMX_Wallet::get_withdrawals( $user_id ) : array();

$charge_error = get_transient( 'gmx_wallet_error_' . $user_id );
delete_transient( 'gmx_wallet_error_' . $user_id );
?>
<h1 class="gmx-dash-title"><?php esc_html_e( 'کیف پول', 'gmx-market' ); ?></h1>

<?php if ( $charge_error ) : ?>
	<div class="gmx-alert gmx-alert-danger"><?php echo esc_html( $charge_error ); ?></div>
<?php endif; ?>

<div class="gmx-wallet-hero">
	<div class="gmx-wallet-balance">
		<span class="gmx-wallet-label"><?php esc_html_e( 'موجودی فعلی', 'gmx-market' ); ?></span>
		<strong><?php echo esc_html( gmx_price( $balance ) ); ?></strong>
	</div>
	<form class="gmx-wallet-charge" method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php wp_nonce_field( 'gmx_wallet_charge', 'gmx_wallet_nonce' ); ?>
		<input type="hidden" name="gmx_wallet_action" value="charge" />
		<label><?php esc_html_e( 'شارژ کیف پول (تومان)', 'gmx-market' ); ?></label>
		<input type="number" name="amount" min="10000" step="10000" placeholder="100000" required />
		<button class="gmx-btn gmx-btn-primary" type="submit"><?php esc_html_e( 'پرداخت و شارژ', 'gmx-market' ); ?></button>
	</form>
</div>

<?php if ( $is_seller ) : ?>
	<h2 class="gmx-dash-subtitle"><?php esc_html_e( 'برداشت به حساب بانکی', 'gmx-market' ); ?></h2>
	<form class="gmx-card gmx-withdraw-form" method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php wp_nonce_field( 'gmx_wallet_withdraw', 'gmx_withdraw_nonce' ); ?>
		<input type="hidden" name="gmx_wallet_action" value="withdraw" />
		<div class="gmx-form-row">
			<div>
				<label><?php esc_html_e( 'مبلغ (تومان)', 'gmx-market' ); ?></label>
				<input type="number" name="amount" min="100000" step="10000" required />
			</div>
			<div>
				<label><?php esc_html_e( 'شماره شبا', 'gmx-market' ); ?></label>
				<input type="text" name="iban" dir="ltr" placeholder="IR..." required />
			</div>
			<button class="gmx-btn" type="submit"><?php esc_html_e( 'درخواست برداشت', 'gmx-market' ); ?></button>
		</div>
	</form>

	<?php if ( $withdrawals ) : ?>
		<table class="gmx-table">
			<thead>
				<tr><th><?php esc_html_e( 'مبلغ', 'gmx-market' ); ?></th><th><?php esc_html_e( 'شبا', 'gmx-market' ); ?></th><th><?php esc_html_e( 'وضعیت', 'gmx-market' ); ?></th><th><?php esc_html_e( 'تاریخ', 'gmx-market' ); ?></th></tr>
			</thead>
			<tbody>
				<?php foreach ( $withdrawals as $w ) : ?>
					<tr>
						<td><?php echo esc_html( gmx_price( $w->amount ) ); ?></td>
						<td dir="ltr"><?php echo esc_html( $w->iban ); ?></td>
						<td><?php echo esc_html( $w->status ); ?></td>
						<td><?php echo esc_html( gmx_date( $w->created_at, false ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
<?php endif; ?>

<h2 class="gmx-dash-subtitle"><?php esc_html_e( 'تاریخچه تراکنش‌ها', 'gmx-market' ); ?></h2>
<?php if ( $trans ) : ?>
	<div class="gmx-table-wrap">
		<table class="gmx-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'شرح', 'gmx-market' ); ?></th>
					<th><?php esc_html_e( 'مبلغ', 'gmx-market' ); ?></th>
					<th><?php esc_html_e( 'نوع', 'gmx-market' ); ?></th>
					<th><?php esc_html_e( 'تاریخ', 'gmx-market' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $trans as $t ) : ?>
					<tr>
						<td><?php echo esc_html( $t->description ); ?></td>
						<td class="<?php echo $t->amount >= 0 ? 'gmx-amount-in' : 'gmx-amount-out'; ?>">
							<?php echo esc_html( ( $t->amount >= 0 ? '+' : '' ) . gmx_price( $t->amount ) ); ?>
						</td>
						<td><?php echo esc_html( $t->type ); ?></td>
						<td><?php echo esc_html( gmx_date( $t->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="gmx-empty"><?php esc_html_e( 'تراکنشی ثبت نشده است.', 'gmx-market' ); ?></div>
<?php endif; ?>
