<?php
/**
 * صفحه ورود اختصاصی لوتینو: هدر/فوتر سایت + کارت مرکزی (مثل صفحه واقعی).
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس صفحه ورود.
 */
class GMX_Login_Page {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'login_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'login_header', array( $this, 'page_header' ) );
		add_action( 'login_footer', array( $this, 'page_extras' ) );
		add_filter( 'login_headerurl', array( $this, 'logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'logo_text' ) );
		add_filter( 'login_title', array( $this, 'title' ) );

		// فرم ثبت‌نام با رمز عبور (نام کاربری/ایمیل + رمز).
		add_action( 'register_form', array( $this, 'register_fields' ) );
		add_filter( 'registration_errors', array( $this, 'validate_registration' ), 10, 3 );
		add_action( 'user_register', array( $this, 'set_registration_password' ) );
	}

	/**
	 * فیلدهای رمز عبور در فرم ثبت‌نام.
	 */
	public function register_fields() {
		?>
		<div class="gmx-field" data-icon="🔒">
			<label for="gmx-reg-pass">رمز عبور</label>
			<input type="password" name="gmx_pass" id="gmx-reg-pass" class="input" autocomplete="new-password" placeholder="••••••••" />
		</div>
		<div class="gmx-field" data-icon="🔁">
			<label for="gmx-reg-pass2">تکرار رمز عبور</label>
			<input type="password" name="gmx_pass2" id="gmx-reg-pass2" class="input" autocomplete="new-password" placeholder="••••••••" />
		</div>
		<?php
	}

	/**
	 * اعتبارسنجی رمز ثبت‌نام.
	 *
	 * @param WP_Error $errors خطاها.
	 * @param string   $login  نام کاربری.
	 * @param string   $email  ایمیل.
	 * @return WP_Error
	 */
	public function validate_registration( $errors, $login, $email ) {
		$pass  = isset( $_POST['gmx_pass'] ) ? trim( wp_unslash( $_POST['gmx_pass'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- فرم هسته وردپرس.
		$pass2 = isset( $_POST['gmx_pass2'] ) ? trim( wp_unslash( $_POST['gmx_pass2'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( strlen( $pass ) < 6 ) {
			$errors->add( 'gmx_pass', __( 'رمز عبور باید حداقل ۶ کاراکتر باشد.', 'gmx-market' ) );
		} elseif ( $pass !== $pass2 ) {
			$errors->add( 'gmx_pass2', __( 'تکرار رمز عبور با رمز اصلی مطابقت ندارد.', 'gmx-market' ) );
		}
		return $errors;
	}

	/**
	 * تنظیم رمز کاربر تازه‌ساخت.
	 *
	 * @param int $user_id شناسه کاربر.
	 */
	public function set_registration_password( $user_id ) {
		if ( ! empty( $_POST['gmx_pass'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$pass = trim( wp_unslash( $_POST['gmx_pass'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( strlen( $pass ) >= 6 ) {
				wp_set_password( $pass, $user_id );
			}
		}
	}

	/**
	 * آدرس لوگو.
	 *
	 * @return string
	 */
	public function logo_url() {
		return home_url( '/' );
	}

	/**
	 * متن لوگو.
	 *
	 * @return string
	 */
	public function logo_text() {
		return get_bloginfo( 'name' );
	}

	/**
	 * عنوان صفحه.
	 *
	 * @param string $title عنوان.
	 * @return string
	 */
	public function title( $title ) {
		return sprintf( '%s — %s', get_bloginfo( 'name' ), __( 'ورود / ثبت‌نام', 'gmx-market' ) );
	}

	/**
	 * هدر صفحه (مثل هدر سایت).
	 */
	public function page_header() {
		$logo = esc_url( GMX_PLUGIN_URL . 'assets/img/logo.svg' );
		?>
		<header class="gx-head">
			<div class="gx-head-in">
				<a class="gx-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img src="<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL امن‌شده. ?>" alt="" width="42" height="42" />
					<span class="gx-logo-t"><b>لوتینو</b><i>L U T I N O</i></span>
				</a>
				<nav class="gx-nav">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
					<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">محصولات</a>
					<a href="<?php echo esc_url( home_url( '/shop/type/service/' ) ); ?>">خدمات</a>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">تماس با ما</a>
				</nav>
				<a class="gx-back" href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به سایت ←</a>
			</div>
		</header>
		<?php
	}

	/**
	 * پنل موبایل + نوار اعتماد + فوتر سایت.
	 */
	public function page_extras() {
		?>
		<div id="gmx-phone-panel" style="display:none">
			<h2>ورود / ثبت‌نام با موبایل</h2>
			<p class="gmx-hint">شماره موبایل خود را وارد کنید؛ کد تایید برایتان پیامک می‌شود. اگر حساب نداشته باشید، خودکار ساخته می‌شود.</p>
			<p class="gmx-msg" id="gmx-phone-msg"></p>

			<div id="gmx-step-phone">
				<div class="gmx-field" data-icon="📱">
					<label for="gmx-phone">شماره موبایل</label>
					<input type="tel" id="gmx-phone" dir="ltr" placeholder="09xxxxxxxxx" autocomplete="tel" />
				</div>
				<button type="button" class="gmx-btn" id="gmx-send-code">دریافت کد تایید</button>
			</div>

			<div class="gmx-code-box" id="gmx-step-code">
				<div class="gmx-field">
					<label>کد ۵ رقمی پیامک‌شده را وارد کنید</label>
				</div>
				<div class="gmx-test-code" id="gmx-test-code" style="display:none"></div>
				<div class="gmx-otp-row">
					<input type="text" class="gmx-otp" maxlength="1" inputmode="numeric" autocomplete="one-time-code" />
					<input type="text" class="gmx-otp" maxlength="1" inputmode="numeric" />
					<input type="text" class="gmx-otp" maxlength="1" inputmode="numeric" />
					<input type="text" class="gmx-otp" maxlength="1" inputmode="numeric" />
					<input type="text" class="gmx-otp" maxlength="1" inputmode="numeric" />
				</div>
				<input type="hidden" id="gmx-code" value="" />
				<button type="button" class="gmx-btn" id="gmx-do-login">ورود به حساب</button>
				<button type="button" class="gmx-btn gmx-btn-ghost" id="gmx-back-phone">← تغییر شماره</button>
			</div>
		</div>

		<div id="gmx-bottom">
			<div class="gmx-trust">
				<span>⚡ تحویل فوری</span>
				<span>🛡️ پرداخت امانی</span>
				<span>🎧 پشتیبانی ۲۴/۷</span>
			</div>
			<p class="gmx-terms">با ورود یا ثبت‌نام، <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">قوانین لوتینو</a> را می‌پذیرید.</p>
		</div>

		<footer class="gx-foot">
			<span>© <?php echo esc_html( get_bloginfo( 'name' ) ); ?> — مارکت گیمرهای ایران</span>
			<nav>
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">قوانین</a>
				<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">پشتیبانی</a>
			</nav>
		</footer>
		<?php
	}

	/**
	 * CSS و JS صفحه ورود.
	 */
	public function assets() {
		$hero_bg = esc_url( get_template_directory_uri() . '/assets/img/ref/hero-bg.jpg' );

		$css = '
		:root{--lg-bg:#05070d;--lg-card:rgba(15,19,30,.88);--lg-card2:#0b0e16;--lg-border:#262e46;--lg-text:#eef0f6;--lg-muted:#98a1b8;--lg-primary:#8b5cf6;--lg-primary2:#6d28d9;--lg-accent:#22d3ee}
		*{box-sizing:border-box}
		body.login{display:flex;flex-direction:column;min-height:100vh;margin:0;padding:0!important;background:linear-gradient(rgba(5,7,13,.88),rgba(5,7,13,.94)),url(' . $hero_bg . ') center/cover no-repeat,var(--lg-bg)!important;font-family:Vazirmatn,Tahoma,sans-serif!important}
		body.login:before{content:"";position:fixed;inset:0;pointer-events:none;background:radial-gradient(760px 460px at 85% -10%,rgba(139,92,246,.22),transparent),radial-gradient(600px 400px at 8% 112%,rgba(34,211,238,.12),transparent)}
		body.login:after{content:"";position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(139,92,246,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(139,92,246,.04) 1px,transparent 1px);background-size:46px 46px;-webkit-mask-image:radial-gradient(ellipse at 50% 40%,#000 20%,transparent 75%);mask-image:radial-gradient(ellipse at 50% 40%,#000 20%,transparent 75%)}
		/* ================= هدر سایت ================= */
		.gx-head{position:relative;z-index:5;background:rgba(9,12,20,.72);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid rgba(139,92,246,.16)}
		.gx-head-in{width:min(1160px,100%);margin:0 auto;padding:12px 24px;display:flex;align-items:center;gap:18px}
		.gx-logo{display:flex;align-items:center;gap:10px;text-decoration:none}
		.gx-logo img{width:42px;height:42px;border-radius:12px;box-shadow:0 6px 18px rgba(139,92,246,.4)}
		.gx-logo-t b{display:block;font-size:16.5px;font-weight:900;color:var(--lg-text);line-height:1.4}
		.gx-logo-t i{display:block;font-style:normal;font-size:7.5px;letter-spacing:3.5px;color:var(--lg-muted);font-weight:700}
		.gx-nav{display:flex;gap:2px;background:var(--lg-card2);border:1px solid var(--lg-border);border-radius:999px;padding:4px}
		.gx-nav a{color:var(--lg-muted);font-size:12.5px;font-weight:600;padding:8px 16px;border-radius:999px;text-decoration:none;transition:color .15s,background .15s}
		.gx-nav a:hover{color:var(--lg-text);background:rgba(139,92,246,.14)}
		.gx-back{margin-inline-start:auto;color:var(--lg-muted);font-size:12.5px;font-weight:700;text-decoration:none;padding:9px 18px;border:1px solid var(--lg-border);border-radius:999px;transition:border-color .15s,color .15s;white-space:nowrap}
		.gx-back:hover{color:var(--lg-accent);border-color:rgba(34,211,238,.4)}
		/* ================= کارت اصلی (#login خودش کارت است) ================= */
		#login{position:relative;z-index:4;width:min(464px,100%)!important;padding:0!important;margin:clamp(22px,5vh,52px) auto 26px!important;flex:1 0 auto;display:flex;flex-direction:column;justify-content:center;background:var(--lg-card);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(139,92,246,.22);border-radius:22px;box-shadow:0 30px 80px rgba(0,0,0,.55),0 0 0 1px rgba(255,255,255,.03) inset;overflow:hidden}
		.login h1{display:none}
		#login_error,.login .message,#login .message{width:auto!important;background:rgba(26,32,48,.9)!important;border:1px solid var(--lg-border)!important;color:var(--lg-text)!important;border-radius:14px!important;box-shadow:none!important;font-size:12.5px;margin:22px 26px 0!important;padding:12px 16px!important}
		#login_error{border-color:rgba(239,68,68,.45)!important;color:#fca5a5!important}
		/* سربرگ کارت */
		.gx-card-head{display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;padding:30px 26px 0}
		.gx-card-head img{width:64px;height:64px;border-radius:17px;box-shadow:0 12px 34px rgba(139,92,246,.45),0 0 0 4px rgba(139,92,246,.13)}
		#login h1.gx-title{display:block!important;background:none!important;padding:0!important;margin:6px 0 0!important;font-family:inherit!important;font-size:20px!important;font-weight:900!important;color:var(--lg-text)!important;text-align:center}
		.gx-sub{margin:0!important;color:var(--lg-muted)!important;font-size:12.5px}
		/* ================= فرم‌ها ================= */
		#loginform,#lostpasswordform,#registerform{background:transparent!important;border:none!important;border-radius:0!important;box-shadow:none!important;padding:20px 26px 8px!important;margin:0!important;display:flex;flex-direction:column;gap:13px}
		#loginform p,#registerform p,#lostpasswordform p,#registerform .gmx-field,#loginform .gmx-field,#lostpasswordform .gmx-field{margin:0!important}
		.login label,#login label,.gmx-field label{color:var(--lg-muted)!important;font-size:12.5px;margin:0 0 7px!important;display:flex;align-items:center;justify-content:space-between;font-weight:600;padding:0!important}
		.login input[type=text],.login input[type=password],.login input[type=tel],.login input[type=email],.gmx-field input{background:var(--lg-card2)!important;border:1.5px solid var(--lg-border)!important;color:var(--lg-text)!important;border-radius:12px!important;padding:12px 14px!important;font-size:14.5px!important;font-family:inherit!important;width:100%!important;box-sizing:border-box!important;height:47px!important;transition:border-color .15s,box-shadow .15s;display:block}
		.login input:focus,.gmx-field input:focus{border-color:var(--lg-primary)!important;box-shadow:0 0 0 3px rgba(139,92,246,.16)!important;outline:none}
		.login input::placeholder,.gmx-field input::placeholder{color:#5a6379}
		/* آیکون داخل فیلد (JS کلاس می‌گذارد) */
		.gx-has-icon{position:relative}
		.gx-has-icon input{padding-inline-start:42px!important}
		.gx-has-icon:before{content:attr(data-icon);position:absolute;bottom:13px;inset-inline-start:13px;font-size:16px;pointer-events:none;opacity:.85;z-index:1}
		/* فیلد رمز + دکمه چشم */
		.user-pass-wrap .wp-pwd{position:relative;width:100%}
		.user-pass-wrap .wp-pwd input{padding-inline-end:44px!important}
		.wp-core-ui .button.wp-hide-pw{position:absolute!important;inset-inline-end:6px!important;top:50%!important;transform:translateY(-50%)!important;background:transparent!important;border:none!important;height:36px!important;width:36px!important;padding:0!important;margin:0!important;box-shadow:none!important}
		.wp-core-ui .button.wp-hide-pw .dashicons{color:var(--lg-muted)!important;font-size:20px;width:20px;height:20px}
		.gx-forgot{color:var(--lg-accent)!important;font-size:11.5px;font-weight:700;text-decoration:none}
		.gx-forgot:hover{color:#67e8f9!important}
		/* یادآوری + دکمه در یک ردیف */
		.gx-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:4px!important}
		.login .forgetmenot{display:flex;align-items:center;gap:8px;margin:0!important}			.login .forgetmenot label{margin:0!important;font-size:12px;color:var(--lg-muted);cursor:pointer;justify-content:flex-start}
			.login .forgetmenot .wp-tooltip,.login .forgetmenot .wp-tooltip button{display:none!important}
		.login #rememberme{appearance:none;-webkit-appearance:none;width:18px;height:18px;border-radius:6px;background:var(--lg-card2);border:1.5px solid var(--lg-border);cursor:pointer;position:relative;transition:background .15s,border-color .15s;margin:0!important;flex:none}
		.login #rememberme:checked{background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2));border-color:var(--lg-primary)}
		.login #rememberme:checked:after{content:"✓";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:900}
		.login .submit{margin:0!important;flex:1}
		#wp-submit,.wp-core-ui .button-primary{background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2))!important;border:none!important;border-radius:12px!important;height:47px!important;padding:0 22px!important;font-size:14.5px!important;font-family:inherit!important;font-weight:800;text-shadow:none!important;width:100%;box-sizing:border-box;cursor:pointer;box-shadow:0 10px 26px rgba(139,92,246,.38)!important;transition:filter .15s,transform .15s}
		#wp-submit:hover,.wp-core-ui .button-primary:hover{filter:brightness(1.13);transform:translateY(-1px)}
		/* لینک‌های زیر فرم */
		.login #nav,.login #backtoblog{margin:0!important;text-align:center;float:none!important;padding:0 26px}
		.login #nav{margin-top:2px!important}
		.login #backtoblog{margin:6px 0 0!important}
		.login #nav a,.login #backtoblog a{color:var(--lg-muted)!important;font-size:12.5px;text-decoration:none;transition:color .15s}
		.login #nav a{color:var(--lg-accent)!important;font-weight:700}
		.login #nav a:hover,.login #backtoblog a:hover{color:var(--lg-text)!important}
		/* ================= تب‌های سگمنتی ================= */
		.gmx-tabs{display:flex;gap:5px;background:var(--lg-card2);border:1px solid var(--lg-border);border-radius:13px;padding:4px;margin:16px 26px 2px}
		.gmx-tabs button{flex:1;background:transparent;border:none;color:var(--lg-muted);border-radius:9px;padding:10px 8px;cursor:pointer;font-family:inherit;font-size:12.5px;font-weight:700;transition:background .18s,color .18s;display:flex;align-items:center;justify-content:center;gap:7px}
		.gmx-tabs button:hover{color:var(--lg-text)}
		.gmx-tabs button.is-active{background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2));color:#fff;box-shadow:0 5px 16px rgba(139,92,246,.38)}
		body.gmx-mode-phone #loginform,body.gmx-mode-phone #nav,body.gmx-mode-phone #backtoblog{display:none}
		body.gmx-mode-pass #gmx-phone-panel{display:none!important}
		/* ================= پنل موبایل ================= */
		#gmx-phone-panel{padding:20px 26px 8px;display:none}
		#gmx-phone-panel h2{margin:0 0 4px;font-size:15.5px;color:var(--lg-text);font-weight:800}
		#gmx-phone-panel .gmx-hint{color:var(--lg-muted);font-size:11.5px;margin:0 0 16px;line-height:1.9}
		.gmx-field{margin:0 0 13px!important}
		.gmx-btn{width:100%;height:47px;background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2));border:none;color:#fff;border-radius:12px;padding:0;font-size:14.5px;font-weight:800;cursor:pointer;font-family:inherit;box-shadow:0 10px 26px rgba(139,92,246,.38);transition:filter .15s,transform .15s;display:flex;align-items:center;justify-content:center;gap:8px}
		.gmx-btn:hover{filter:brightness(1.13);transform:translateY(-1px)}
		.gmx-btn:disabled{opacity:.55;cursor:not-allowed;transform:none}
		.gmx-btn-ghost{background:transparent;border:1.5px solid var(--lg-border);color:var(--lg-muted);box-shadow:none!important;margin-top:10px}
		.gmx-btn-ghost:hover{border-color:var(--lg-primary);color:var(--lg-text);filter:none}
		/* OTP */
		.gmx-otp-row{display:flex;gap:9px;direction:ltr;justify-content:center;margin:4px 0 14px}
		.gmx-otp{width:52px!important;height:58px!important;text-align:center;font-size:23px!important;font-weight:800!important;border-radius:13px!important;padding:0!important;background:var(--lg-card2)!important;border:1.5px solid var(--lg-border)!important;color:var(--lg-text)!important;font-family:inherit;transition:border-color .15s,box-shadow .15s,transform .1s}
		.gmx-otp:focus{outline:none;border-color:var(--lg-primary)!important;box-shadow:0 0 0 3px rgba(139,92,246,.18);transform:translateY(-2px)}
		.gmx-otp.filled{border-color:rgba(139,92,246,.65)!important;background:rgba(139,92,246,.08)!important}
		.gmx-msg{display:none;margin:0 0 14px!important;padding:11px 14px;border-radius:12px;font-size:12.5px;line-height:1.9}
		.gmx-msg.ok{display:block;background:rgba(52,211,153,.09);color:#6ee7b7;border:1px solid rgba(52,211,153,.3)}
		.gmx-msg.err{display:block;background:rgba(239,68,68,.09);color:#fca5a5;border:1px solid rgba(239,68,68,.35)}
		.gmx-code-box{display:none}
		.gmx-code-box.show{display:block;animation:gxIn .25s ease}
		@keyframes gxIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
		.gmx-test-code{background:rgba(34,211,238,.08);border:1.5px dashed rgba(34,211,238,.45);color:var(--lg-accent);border-radius:12px;padding:10px 14px;font-size:15px;font-weight:800;margin-bottom:14px;text-align:center;letter-spacing:6px}
		/* ================= نوار اعتماد ================= */
		#gmx-bottom{padding:16px 26px 22px;border-top:1px dashed var(--lg-border);margin-top:auto}
		.gmx-trust{display:flex;justify-content:center;gap:14px;flex-wrap:wrap}
		.gmx-trust span{display:inline-flex;align-items:center;gap:5px;font-size:10.5px;color:var(--lg-muted)}
		.gmx-terms{text-align:center;font-size:10.5px;color:#6b7490;margin:10px 0 0!important;line-height:1.8}
		.gmx-terms a{color:var(--lg-accent);text-decoration:none}
		/* ================= فوتر سایت ================= */
		.gx-foot{position:relative;z-index:4;display:flex;align-items:center;justify-content:space-between;gap:14px;width:min(1160px,100%);margin:0 auto;padding:16px 24px;border-top:1px solid rgba(139,92,246,.12);flex-wrap:wrap}
		.gx-foot>span{font-size:11px;color:#6b7490}
		.gx-foot nav{display:flex;gap:18px}
		.gx-foot nav a{font-size:11px;color:var(--lg-muted);text-decoration:none}
		.gx-foot nav a:hover{color:var(--lg-accent)}
		/* ================= بقیه صفحات ================= */
		body.login-action-register #gmx-phone-panel,body.login-action-lostpassword #gmx-phone-panel{display:none!important}
		#reg_passmail{display:none}
		body.login #language-switcher,body.login #language-switcher-wrap{display:none!important}
		.login #backtoblog a{color:var(--lg-muted)!important}
		/* ================= ریسپانسیو ================= */
		@media(max-width:900px){.gx-nav{display:none}}
		@media(max-width:600px){
			.gx-head-in{padding:10px 16px;gap:10px}
			.gx-back{padding:8px 12px;font-size:11.5px}
			.gx-logo-t b{font-size:15px}
			#login{margin-inline:14px!important;border-radius:18px}
			.gx-card-head{padding-top:24px}
			#loginform,#registerform,#lostpasswordform{padding:16px 18px 6px!important}
			.gmx-tabs{margin:14px 18px 2px}
			#gmx-phone-panel{padding:16px 18px 6px}
			#gmx-bottom{padding:14px 18px 18px}
			.gx-actions{flex-direction:column-reverse;align-items:stretch}
			.login .forgetmenot{justify-content:center}
			.gx-otp{width:46px!important;height:54px!important}
			.gx-foot{justify-content:center;text-align:center}
		}
		@media(min-width:601px) and (max-width:900px){#login{margin-inline:auto!important}}
		';

		wp_register_style( 'gmx-login-fake', false );
		wp_enqueue_style( 'gmx-login-fake' );
		wp_add_inline_style( 'gmx-login-fake', $css );

		// فونت وزیرمتن.
		wp_enqueue_style(
			'gmx-login-font',
			'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css',
			array(),
			'1.0'
		);

		$data = array(
			'restUrl' => esc_url_raw( rest_url( 'gmx/v1' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'logo'    => esc_url_raw( GMX_PLUGIN_URL . 'assets/img/logo.svg' ),
			'site'    => get_bloginfo( 'name' ),
			'i18n'    => array(
				'sendCode' => __( 'دریافت کد تایید', 'gmx-market' ),
				'resend'   => __( 'ارسال مجدد کد', 'gmx-market' ),
				'sending'  => __( 'در حال ارسال…', 'gmx-market' ),
				'login'    => __( 'ورود به حساب', 'gmx-market' ),
				'countdown' => __( 'ثانیه تا ارسال مجدد', 'gmx-market' ),
			),
			'dashUrl' => home_url( '/' ),
		);

		wp_register_script( 'gmx-login', false, array(), '2.0', true );
		wp_enqueue_script( 'gmx-login' );
		wp_localize_script( 'gmx-login', 'GMXLogin', $data );

		$js = '
		(function(){
			var CFG = window.GMXLogin || {};
			var I18N = CFG.i18n || {};
			function $(s){ return document.querySelector(s); }
			function post(url, data){
				return fetch(CFG.restUrl.replace(/\\/$/,"") + url, {
					method: "POST",
					headers: {"Content-Type":"application/json","X-WP-Nonce":CFG.nonce},
					credentials: "same-origin",
					body: JSON.stringify(data)
				}).then(function(r){ return r.json(); });
			}

			/* ---------- نوع صفحه ---------- */
			var action = (document.body.className.match(/login-action-(\\w+)/) || [])[1] || "login";
			var login = document.getElementById("login");
			if (!login) return;

			/* ---------- سربرگ کارت ---------- */
			var head = document.createElement("div");
			head.className = "gx-card-head";
			var titles = {
				login:        {t: "ورود به لوتینو",        s: "به حساب کاربری خود وارد شوید"},
				register:     {t: "ساخت حساب کاربری",      s: "چند ثانیه تا پیوستن به لوتینو 🎮"},
				lostpassword: {t: "بازیابی رمز عبور",      s: "نام کاربری یا ایمیل خود را وارد کنید"}
			};
			var T = titles[action] || titles.login;
			head.innerHTML = "<img src=\\"" + CFG.logo + "\\" alt=\\"\\">" +
				"<h1 class=\\"gx-title\\">" + T.t + "</h1>" +
				"<p class=\\"gx-sub\\">" + T.s + "</p>";
			login.insertBefore(head, login.firstChild);

			/* ---------- آیکون داخل فیلدها ---------- */
			function iconize(inputSel, icon){
				var el = document.getElementById(inputSel);
				if (!el) return;
				var holder = el.closest(".gmx-field") || el.closest("p") || el.parentNode;
				holder.classList.add("gx-has-icon");
				holder.setAttribute("data-icon", icon);
			}
			if (action === "login") {
				iconize("user_login", "👤");
			} else if (action === "register") {
				iconize("user_login", "👤");
				iconize("user_email", "✉️");
				// فیلدهای رمز اختصاصی از قبل data-icon دارند.
			} else {
				iconize("user_login", "👤");
			}

			/* ---------- لینک فراموشی رمز کنار لیبل (فقط صفحه ورود) ---------- */
			if (action === "login") {
				var lostLink = document.querySelector("#nav a[href*=\\"lostpassword\\"]");
				var passLabel = document.querySelector(\'.login label[for="user_pass"]\');
				if (lostLink && passLabel && !passLabel.querySelector(".gx-forgot")) {
					var a = document.createElement("a");
					a.className = "gx-forgot";
					a.href = lostLink.href;
					a.textContent = "فراموشی رمز؟";
					passLabel.appendChild(a);
				}
				var nav = document.getElementById("nav");
				if (nav) { nav.style.display = "none"; }
			}

			/* ---------- یادآوری + دکمه در یک ردیف ---------- */
			var fm = document.querySelector(".forgetmenot");
			var st = document.querySelector("#loginform .submit");
			if (fm && st) {
				var row = document.createElement("div");
				row.className = "gx-actions";
				st.parentNode.insertBefore(row, st);
				row.appendChild(fm);
				row.appendChild(st);
			}

			/* ---------- انتقال پنل موبایل و نوار اعتماد به داخل کارت ---------- */
			var panel = document.getElementById("gmx-phone-panel");
			var bottom = document.getElementById("gmx-bottom");
			if (panel) { login.appendChild(panel); }
			if (bottom) { login.appendChild(bottom); bottom.style.display = "block"; }

			/* ---------- تب‌های ورود ---------- */
			var form = $("#loginform");
			if (form && action === "login") {
				var tabs = document.createElement("div");
				tabs.className = "gmx-tabs";
				tabs.innerHTML = "<button type=button id=gmx-tab-pass class=is-active>🔑 ورود با رمز</button><button type=button id=gmx-tab-phone>📱 ورود با موبایل</button>";
				login.insertBefore(tabs, form);
			}

			/* ---------- placeholderهای راهنما ---------- */
			var ul = document.getElementById("user_login");
			if (ul) { ul.placeholder = (action === "login") ? "نام کاربری یا ایمیل" : "نام کاربری"; }
			var ue = document.getElementById("user_email");
			if (ue) { ue.placeholder = "you@example.com"; }

			/* ---------- سوییچ حالت ---------- */
			function setMode(m){
				document.body.className = document.body.className.replace(/gmx-mode-\\w+/g, "").trim();
				document.body.classList.add("gmx-mode-" + m);
				var panelEl = document.getElementById("gmx-phone-panel");
				if (panelEl) { panelEl.style.display = (m === "phone") ? "block" : "none"; }
				var p = $("#gmx-tab-pass"), ph = $("#gmx-tab-phone");
				if (m === "pass") { if (p) p.classList.add("is-active"); if (ph) ph.classList.remove("is-active"); }
				else { if (ph) ph.classList.add("is-active"); if (p) p.classList.remove("is-active"); }
			}
			document.addEventListener("click", function(e){
				if (e.target.id === "gmx-tab-pass") { setMode("pass"); }
				if (e.target.id === "gmx-tab-phone") { setMode("phone"); var f = $("#gmx-phone"); if (f) setTimeout(function(){ f.focus(); }, 50); }
			});
			if (action === "login") { setMode("pass"); } else { document.body.classList.add("gmx-mode-pass"); }

			/* ---------- OTP پنج‌خانه ---------- */
			if (!panel) return;
			var step1 = $("#gmx-step-phone"), step2 = $("#gmx-step-code");
			var msg = $("#gmx-phone-msg");
			var phoneInput = $("#gmx-phone"), codeInput = $("#gmx-code");
			var sendBtn = $("#gmx-send-code"), loginBtn = $("#gmx-do-login");
			var backBtn = $("#gmx-back-phone");
			var otps = Array.prototype.slice.call(document.querySelectorAll(".gmx-otp"));
			var timer = null;

			function syncCode(){
				var v = otps.map(function(o){ return (o.value || "").replace(/\\D/g, ""); }).join("");
				codeInput.value = v;
				otps.forEach(function(o){ o.classList.toggle("filled", !!(o.value && o.value.trim())); });
				return v;
			}
			otps.forEach(function(o, i){
				o.addEventListener("input", function(){
					o.value = o.value.replace(/\\D/g, "").slice(-1);
					if (o.value && i < otps.length - 1) { otps[i + 1].focus(); }
					if (syncCode().length === 5) { loginBtn.focus(); }
				});
				o.addEventListener("keydown", function(e){
					if (e.key === "Backspace" && !o.value && i > 0) { otps[i - 1].focus(); otps[i - 1].value = ""; syncCode(); e.preventDefault(); }
				});
				o.addEventListener("paste", function(e){
					e.preventDefault();
					var txt = (e.clipboardData || window.clipboardData).getData("text").replace(/\\D/g, "").slice(0, 5);
					for (var k = 0; k < txt.length && k < otps.length; k++) { otps[k].value = txt[k]; }
					syncCode();
					otps[Math.min(txt.length, otps.length - 1)].focus();
					if (txt.length === 5) { loginBtn.focus(); }
				});
			});
			function fillCode(v){
				v = String(v || "").replace(/\\D/g, "").slice(0, 5);
				for (var k = 0; k < otps.length; k++) { otps[k].value = v[k] || ""; }
				syncCode();
			}

			function showMsg(text, ok){
				msg.className = "gmx-msg " + (ok ? "ok" : "err");
				msg.textContent = text;
			}
			function countdown(sec){
				var left = sec;
				clearInterval(timer);
				sendBtn.disabled = true;
				timer = setInterval(function(){
					left--;
					if (left <= 0) { clearInterval(timer); sendBtn.disabled = false; sendBtn.textContent = I18N.resend; return; }
					sendBtn.textContent = left + " " + I18N.countdown;
				}, 1000);
			}

			if (backBtn) backBtn.addEventListener("click", function(){
				step2.classList.remove("show");
				step1.style.display = "block";
				msg.className = "gmx-msg";
				fillCode("");
				clearInterval(timer);
				sendBtn.disabled = false; sendBtn.textContent = I18N.sendCode;
				phoneInput.focus();
			});

			sendBtn.addEventListener("click", function(){
				var phone = (phoneInput.value || "").trim();
				if (!phone) { showMsg("شماره موبایل را وارد کنید.", false); phoneInput.focus(); return; }
				sendBtn.disabled = true; sendBtn.textContent = I18N.sending;
				post("/auth/send", {phone: phone}).then(function(r){
					if (r && r.ok) {
						showMsg("کد تایید پیامک شد" + (r.test_code ? " — حالت تست:" : "؛ لطفاً کد را وارد کنید."), true);
						if (r.test_code) {
							var tc = document.getElementById("gmx-test-code");
							if (tc) { tc.textContent = r.test_code; tc.style.display = "block"; }
							fillCode(r.test_code);
						}
						step1.style.display = "none";
						step2.classList.add("show");
						if (r.test_code) { loginBtn.focus(); } else { otps[0].focus(); }
						countdown(120);
					} else {
						showMsg((r && r.message) || "خطا در ارسال کد.", false);
						sendBtn.disabled = false; sendBtn.textContent = I18N.sendCode;
					}
				}).catch(function(){ showMsg("خطای شبکه.", false); sendBtn.disabled = false; sendBtn.textContent = I18N.sendCode; });
			});

			loginBtn.addEventListener("click", function(){
				var phone = (phoneInput.value || "").trim();
				var code = syncCode();
				if (!phone || code.length !== 5) { showMsg("شماره و کد ۵ رقمی را کامل وارد کنید.", false); return; }
				loginBtn.disabled = true; loginBtn.textContent = "…";
				post("/auth/verify", {phone: phone, code: code}).then(function(r){
					if (r && r.ok) {
						showMsg("خوش آمدید! در حال انتقال…", true);
						window.location.href = r.redirect || CFG.dashUrl;
					} else {
						showMsg((r && r.message) || "کد نامعتبر است.", false);
						loginBtn.disabled = false; loginBtn.textContent = I18N.login;
						fillCode("");
						otps[0].focus();
					}
				}).catch(function(){ showMsg("خطای شبکه.", false); loginBtn.disabled = false; loginBtn.textContent = I18N.login; });
			});
		})();
		';
		wp_add_inline_script( 'gmx-login', $js );
	}
}
