<?php
/**
 * بخش اطلاعات حساب.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user    = wp_get_current_user();
$saved   = isset( $_GET['saved'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<h1 class="gmx-dash-title"><?php esc_html_e( 'اطلاعات حساب', 'gmx-market' ); ?></h1>

<?php if ( $saved ) : ?>
	<div class="gmx-alert gmx-alert-success"><?php esc_html_e( 'اطلاعات ذخیره شد. ✅', 'gmx-market' ); ?></div>
<?php endif; ?>

<form class="gmx-card gmx-profile-form" method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<?php wp_nonce_field( 'gmx_profile_save', 'gmx_profile_nonce' ); ?>
	<input type="hidden" name="gmx_profile_action" value="save" />

	<div class="gmx-form-row">
		<div>
			<label><?php esc_html_e( 'نام نمایشی', 'gmx-market' ); ?></label>
			<input type="text" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required />
		</div>
		<div>
			<label><?php esc_html_e( 'ایمیل', 'gmx-market' ); ?></label>
			<input type="email" name="user_email" value="<?php echo esc_attr( $user->user_email ); ?>" dir="ltr" required />
		</div>
	</div>

	<div class="gmx-form-row">
		<div>
			<label><?php esc_html_e( 'شماره موبایل (برای پیامک وضعیت سفارش)', 'gmx-market' ); ?></label>
			<input type="tel" name="billing_phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'billing_phone', true ) ); ?>" dir="ltr" placeholder="09xxxxxxxxx" />
		</div>
		<div>
			<label><?php esc_html_e( 'رمز جدید (اختیاری)', 'gmx-market' ); ?></label>
			<input type="password" name="user_pass" dir="ltr" minlength="8" autocomplete="new-password" />
		</div>
	</div>

	<button class="gmx-btn gmx-btn-primary" type="submit"><?php esc_html_e( 'ذخیره تغییرات', 'gmx-market' ); ?></button>
</form>

<h2 class="gmx-dash-subtitle"><?php esc_html_e( 'تاریخچه پرداخت‌ها', 'gmx-market' ); ?></h2>
<?php
$payments = GMX_Wallet::get_transactions( $user->ID, 10 );
if ( $payments ) :
	?>
	<div class="gmx-table-wrap">
		<table class="gmx-table">
			<thead><tr><th><?php esc_html_e( 'شرح', 'gmx-market' ); ?></th><th><?php esc_html_e( 'مبلغ', 'gmx-market' ); ?></th><th><?php esc_html_e( 'تاریخ', 'gmx-market' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $payments as $p ) : ?>
					<tr>
						<td><?php echo esc_html( $p->description ); ?></td>
						<td><?php echo esc_html( gmx_price( $p->amount ) ); ?></td>
						<td><?php echo esc_html( gmx_date( $p->created_at, false ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="gmx-empty"><?php esc_html_e( 'پرداختی ثبت نشده است.', 'gmx-market' ); ?></div>
<?php endif; ?>
