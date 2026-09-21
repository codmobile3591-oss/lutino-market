<?php
/**
 * پنل تنظیمات مدیریت.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس تنظیمات.
 */
class GMX_Settings {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
	}

	/**
	 * افزودن منوی مدیریت.
	 */
	public function menu() {
		add_menu_page(
			__( 'لوتینو', 'gmx-market' ),
			__( 'لوتینو', 'gmx-market' ),
			'manage_options',
			'gmx-market',
			array( $this, 'render' ),
			'dashicons-store',
			56
		);
	}

	/**
	 * ذخیره تنظیمات.
	 */
	public function handle_save() {
		if ( ! isset( $_POST['gmx_settings_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['gmx_settings_nonce'] ), 'gmx_settings_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$text_keys   = array( 'gmx_currency_unit', 'gmx_gateway', 'gmx_sms_provider', 'gmx_zarinpal_merchant', 'gmx_idpay_api', 'gmx_payping_api', 'gmx_kavenegar_key', 'gmx_melipayamak_user', 'gmx_melipayamak_pass', 'gmx_melipayamak_from', 'gmx_admin_mobile', 'gmx_notify_email', 'gmx_ticket_categories' );
		$number_keys = array( 'gmx_currency_decimals', 'gmx_commission_percent' );

		foreach ( $text_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_option( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
		foreach ( $number_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_option( $key, (int) $_POST[ $key ] );
			}
		}

		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'تنظیمات ذخیره شد.', 'gmx-market' ) . '</p></div>';
		} );
	}

	/**
	 * رندر صفحه تنظیمات.
	 */
	public function render() {
		$gw   = get_option( 'gmx_gateway', 'zarinpal' );
		$sms  = get_option( 'gmx_sms_provider', 'kavenegar' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'تنظیمات لوتینو', 'gmx-market' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'gmx_settings_save', 'gmx_settings_nonce' ); ?>

				<h2 class="title"><?php esc_html_e( 'عمومی', 'gmx-market' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'واحد پول', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_currency_unit" value="<?php echo esc_attr( get_option( 'gmx_currency_unit', 'تومان' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'درصد کمیسیون فروشنده', 'gmx-market' ); ?></th>
						<td>
							<input type="number" name="gmx_commission_percent" value="<?php echo esc_attr( get_option( 'gmx_commission_percent', 5 ) ); ?>" min="0" max="90" /> %
							<p class="description"><?php esc_html_e( 'سهم سایت از هر فروش موفق.', 'gmx-market' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'درگاه پرداخت', 'gmx-market' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'درگاه فعال', 'gmx-market' ) ?></th>
						<td>
							<select name="gmx_gateway">
								<option value="zarinpal" <?php selected( $gw, 'zarinpal' ); ?>>زرین‌پال</option>
								<option value="idpay" <?php selected( $gw, 'idpay' ); ?>>IDPay</option>
								<option value="payping" <?php selected( $gw, 'payping' ); ?>>PayPing</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'مرچنت کد زرین‌پال', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_zarinpal_merchant" value="<?php echo esc_attr( get_option( 'gmx_zarinpal_merchant' ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>					<tr>
						<th scope="row"><?php esc_html_e( 'کلید API IDPay', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_idpay_api" value="<?php echo esc_attr( get_option( 'gmx_idpay_api' ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'کلید API PayPing', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_payping_api" value="<?php echo esc_attr( get_option( 'gmx_payping_api' ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'پیامک و ایمیل', 'gmx-market' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'سرویس پیامک', 'gmx-market' ); ?></th>
						<td>
							<select name="gmx_sms_provider">
								<option value="kavenegar" <?php selected( $sms, 'kavenegar' ); ?>>کاوه‌نگار</option>
								<option value="melipayamak" <?php selected( $sms, 'melipayamak' ); ?>>ملی‌پیامک</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'کلید API کاوه‌نگار', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_kavenegar_key" value="<?php echo esc_attr( get_option( 'gmx_kavenegar_key' ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'شماره موبایل مدیر برای اطلاع‌رسانی', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_admin_mobile" value="<?php echo esc_attr( get_option( 'gmx_admin_mobile' ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'ایمیل اطلاع‌رسانی', 'gmx-market' ); ?></th>
						<td><input type="email" name="gmx_notify_email" value="<?php echo esc_attr( get_option( 'gmx_notify_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'نام کاربری ملی‌پیامک', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_melipayamak_user" value="<?php echo esc_attr( get_option( 'gmx_melipayamak_user' ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'رمز ملی‌پیامک', 'gmx-market' ); ?></th>
						<td><input type="password" name="gmx_melipayamak_pass" value="<?php echo esc_attr( get_option( 'gmx_melipayamak_pass' ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'شماره فرستنده ملی‌پیامک', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_melipayamak_from" value="<?php echo esc_attr( get_option( 'gmx_melipayamak_from' ) ); ?>" class="regular-text" dir="ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'دسته‌بندی‌های تیکت (slug|عنوان با کاما)', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_ticket_categories" value="<?php echo esc_attr( get_option( 'gmx_ticket_categories' ) ); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'ذخیره تنظیمات', 'gmx-market' ) ); ?>
			</form>
		</div>
		<?php
	}
}
