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

		$text_keys   = array( 'gmx_currency_unit', 'gmx_gateway', 'gmx_sms_provider', 'gmx_zarinpal_merchant', 'gmx_idpay_api', 'gmx_payping_api', 'gmx_kavenegar_key', 'gmx_kavenegar_sender', 'gmx_melipayamak_user', 'gmx_melipayamak_pass', 'gmx_melipayamak_from', 'gmx_admin_mobile', 'gmx_notify_email', 'gmx_ticket_categories' );
		$hero_keys   = array(
			'gmx_hero_eyebrow', 'gmx_hero_title_l1', 'gmx_hero_title_l2', 'gmx_hero_desc',
			'gmx_hero_btn1', 'gmx_hero_btn2',
			'gmx_hero_stat1_l', 'gmx_hero_stat2_l', 'gmx_hero_stat3_l',
			'gmx_slide1_tag', 'gmx_slide1_title', 'gmx_slide1_text', 'gmx_slide1_btn', 'gmx_slide1_link',
			'gmx_slide2_tag', 'gmx_slide2_title', 'gmx_slide2_text', 'gmx_slide2_btn', 'gmx_slide2_link',
			'gmx_slide3_tag', 'gmx_slide3_title', 'gmx_slide3_text', 'gmx_slide3_btn', 'gmx_slide3_link',
		);
		$hero_textareas = array( 'gmx_hero_desc', 'gmx_slide1_text', 'gmx_slide2_text', 'gmx_slide3_text' );
		$number_keys = array( 'gmx_currency_decimals', 'gmx_commission_percent' );

		foreach ( $text_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_option( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
		foreach ( $hero_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = in_array( $key, $hero_textareas, true ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				update_option( $key, $value );
			}
		}
		foreach ( $number_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_option( $key, (int) $_POST[ $key ] );
			}
		}

		// چک‌باکس‌ها: اگر تیک نخورده باشند در POST نمی‌آیند — صریح صفر می‌کنیم.
		update_option( 'gmx_otp_test_mode', isset( $_POST['gmx_otp_test_mode'] ) ? 1 : 0 );

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
						<td><input type="text" name="gmx_kavenegar_key" value="<?php echo esc_attr( get_option( 'gmx_kavenegar_key' ) ); ?>" class="regular-text" dir="ltr" />
							<p class="description"><?php esc_html_e( 'از پنل kavenegar.com ← تنظیمات ← API Key. همچنین می‌توانید در wp-config.php به‌صورت define( \'GMX_KAVENEGAR_KEY\', \'...\' ) تعریف کنید.', 'gmx-market' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'شماره فرستنده کاوه‌نگار (اختیاری)', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_kavenegar_sender" value="<?php echo esc_attr( get_option( 'gmx_kavenegar_sender' ) ); ?>" class="regular-text" dir="ltr" placeholder="10008663" />
							<p class="description"><?php esc_html_e( 'خالی بگذارید تا از شماره خط عمومی/خدماتی پیش‌فرض حساب استفاده شود.', 'gmx-market' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'حالت تست کد ورود', 'gmx-market' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="gmx_otp_test_mode" value="1" <?php checked( get_option( 'gmx_otp_test_mode', 1 ), '1' ); ?> />
								<?php esc_html_e( 'نمایش کد روی صفحه به‌جای ارسال پیامک واقعی (فقط برای توسعه — خاموش کنید تا پیامک واقعی کاوه‌نگار ارسال شود)', 'gmx-market' ); ?>
							</label>
						</td>
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

				<hr />
				<h2 class="title"><?php esc_html_e( 'محتوای صفحه اصلی (هیرو و بنرها)', 'gmx-market' ); ?></h2>
				<p class="description"><?php esc_html_e( 'اگر فیلدی را خالی بگذاری، متن پیش‌فرض استفاده می‌شود. آمارها خودکار از دیتابیس می‌آیند.', 'gmx-market' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th colspan="2"><strong><?php esc_html_e( 'بخش هیرو', 'gmx-market' ); ?></strong></th></tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'برچسب بالای تیتر', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_hero_eyebrow" value="<?php echo esc_attr( get_option( 'gmx_hero_eyebrow', 'مارکت امن گیمرهای ایران' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'تیتر (خط اول)', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_hero_title_l1" value="<?php echo esc_attr( get_option( 'gmx_hero_title_l1', 'خرید و فروش امن' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'تیتر رنگی (خط دوم)', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_hero_title_l2" value="<?php echo esc_attr( get_option( 'gmx_hero_title_l2', 'آیتم، جم و اکانت' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'توضیح هیرو', 'gmx-market' ); ?></th>
						<td><textarea name="gmx_hero_desc" rows="2" class="large-text"><?php echo esc_textarea( get_option( 'gmx_hero_desc', 'پرداخت امانی، چت مستقیم با فروشنده و تحویل سریع — همه‌چیز در یک مارکت حرفه‌ای.' ) ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'متن دکمه اول', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_hero_btn1" value="<?php echo esc_attr( get_option( 'gmx_hero_btn1', 'شروع خرید' ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'متن دکمه دوم', 'gmx-market' ); ?></th>
						<td><input type="text" name="gmx_hero_btn2" value="<?php echo esc_attr( get_option( 'gmx_hero_btn2', 'فروشنده شو' ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'عنوان آمار ۱ / ۲ / ۳', 'gmx-market' ); ?></th>
						<td>
							<input type="text" name="gmx_hero_stat1_l" value="<?php echo esc_attr( get_option( 'gmx_hero_stat1_l', 'آگهی فعال' ) ); ?>" style="width:130px" />
							<input type="text" name="gmx_hero_stat2_l" value="<?php echo esc_attr( get_option( 'gmx_hero_stat2_l', 'سفارش موفق' ) ); ?>" style="width:130px" />
							<input type="text" name="gmx_hero_stat3_l" value="<?php echo esc_attr( get_option( 'gmx_hero_stat3_l', 'گیمر عضو' ) ); ?>" style="width:130px" />
							<p class="description"><?php esc_html_e( 'اعداد خودکار از دیتابیس شمرده می‌شوند.', 'gmx-market' ); ?></p>
						</td>
					</tr>
				</table>

				<?php
				$slide_defaults = array(
					1 => array( 'tag' => 'جم و ارز درون‌بازی', 'title' => 'جم فوری با بهترین نرخ بازار', 'text' => 'تحویل زیر ۵ دقیقه روی بازی‌های محبوب — اولین خرید با ۱۵٪ تخفیف.', 'btn' => 'خرید جم', 'link' => '/shop/type/gem/' ),
					2 => array( 'tag' => 'معامله امانی', 'title' => 'اکانت بخر، خیالت راحت', 'text' => 'پول تا تایید تحویل نزد سایت امانت می‌ماند؛ اسکم ممنوع.', 'btn' => 'دیدن اکانت‌ها', 'link' => '/shop/type/account/' ),
					3 => array( 'tag' => 'کسب درآمد', 'title' => 'فروشنده لوتینو شو', 'text' => 'آگهی‌ات را بگذار، ما امنیت پرداخت و مشتری را می‌آوریم.', 'btn' => 'ثبت آگهی', 'link' => '/my-account/seller/' ),
				);
				for ( $i = 1; $i <= 3; $i++ ) :
					$d = $slide_defaults[ $i ];
					?>
					<table class="form-table" role="presentation">
						<tr><th colspan="2"><strong><?php echo esc_html( sprintf( __( 'اسلاید %s', 'gmx-market' ), gmx_fa_num( $i ) ) ); ?></strong></th></tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'برچسب', 'gmx-market' ); ?></th>
							<td><input type="text" name="gmx_slide<?php echo (int) $i; ?>_tag" value="<?php echo esc_attr( get_option( "gmx_slide{$i}_tag", $d['tag'] ) ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'عنوان', 'gmx-market' ); ?></th>
							<td><input type="text" name="gmx_slide<?php echo (int) $i; ?>_title" value="<?php echo esc_attr( get_option( "gmx_slide{$i}_title", $d['title'] ) ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'متن', 'gmx-market' ); ?></th>
							<td><textarea name="gmx_slide<?php echo (int) $i; ?>_text" rows="2" class="large-text"><?php echo esc_textarea( get_option( "gmx_slide{$i}_text", $d['text'] ) ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'دکمه: متن', 'gmx-market' ); ?></th>
							<td><input type="text" name="gmx_slide<?php echo (int) $i; ?>_btn" value="<?php echo esc_attr( get_option( "gmx_slide{$i}_btn", $d['btn'] ) ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'دکمه: لینک', 'gmx-market' ); ?></th>
							<td><input type="text" name="gmx_slide<?php echo (int) $i; ?>_link" value="<?php echo esc_attr( get_option( "gmx_slide{$i}_link", $d['link'] ) ); ?>" class="regular-text" dir="ltr" /></td>
						</tr>
					</table>
				<?php endfor; ?>
				<?php submit_button( __( 'ذخیره تنظیمات', 'gmx-market' ) ); ?>
			</form>
		</div>
		<?php
	}
}
