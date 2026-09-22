<?php
/**
 * صفحه ورود اختصاصی لوتینو: دوستونه پریمیوم (پنل برند + کارت فرم) + ورود با موبایل.
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
		add_filter( 'login_headerurl', array( $this, 'logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'logo_text' ) );
		add_action( 'login_footer', array( $this, 'phone_panel' ) );
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
		<div class="gmx-field">
			<label for="gmx-reg-pass">🔒 <?php esc_html_e( 'رمز عبور', 'gmx-market' ); ?></label>
			<input type="password" name="gmx_pass" id="gmx-reg-pass" class="input" autocomplete="new-password" placeholder="**********" />
		</div>
		<div class="gmx-field">
			<label for="gmx-reg-pass2">🔒 <?php esc_html_e( 'تکرار رمز عبور', 'gmx-market' ); ?></label>
			<input type="password" name="gmx_pass2" id="gmx-reg-pass2" class="input" autocomplete="new-password" placeholder="**********" />
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
	 * CSS و JS صفحه ورود.
	 */
	public function assets() {
		$hero_bg = esc_url( get_template_directory_uri() . '/assets/img/ref/hero-bg.jpg' );

		$css = '
		:root{--lg-bg:#05070d;--lg-card:#11151f;--lg-card2:#0b0e16;--lg-border:#262e46;--lg-text:#eef0f6;--lg-muted:#98a1b8;--lg-primary:#8b5cf6;--lg-primary2:#6d28d9;--lg-accent:#22d3ee}
		*{box-sizing:border-box}
		body.login{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:28px 18px;background:linear-gradient(rgba(5,7,13,.9),rgba(5,7,13,.95)),url(' . $hero_bg . ') center/cover no-repeat,var(--lg-bg)!important;font-family:Vazirmatn,Tahoma,sans-serif}
		body.login:before{content:"";position:fixed;inset:0;pointer-events:none;background:radial-gradient(760px 460px at 82% -10%,rgba(139,92,246,.25),transparent),radial-gradient(600px 400px at 10% 110%,rgba(34,211,238,.13),transparent)}
		body.login:after{content:"";position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(139,92,246,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(139,92,246,.045) 1px,transparent 1px);background-size:46px 46px;-webkit-mask-image:radial-gradient(ellipse at center,#000 25%,transparent 78%);mask-image:radial-gradient(ellipse at center,#000 25%,transparent 78%)}
		/* ---------- پوسته دوستونه ---------- */
		#login{width:auto!important;max-width:100%;padding:0;margin:0!important;position:relative;z-index:2;flex:1;min-width:0}
		.gmx-shell{display:flex;flex:0 0 auto;width:min(960px,100%);border-radius:26px;overflow:hidden;border:1px solid rgba(139,92,246,.24);background:rgba(12,15,24,.78);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);box-shadow:0 36px 90px rgba(0,0,0,.62),0 0 0 1px rgba(255,255,255,.03) inset}
		/* ستون برند */
		.gmx-col-brand{position:relative;flex:1.06;display:flex;flex-direction:column;justify-content:space-between;gap:24px;padding:38px 36px;background:linear-gradient(rgba(9,7,22,.8),rgba(9,7,22,.92)),url(' . $hero_bg . ') center/cover no-repeat;overflow:hidden;border-left:1px solid rgba(139,92,246,.18)}
		.gmx-col-brand:before,.gmx-col-brand:after{content:"";position:absolute;border-radius:50%;filter:blur(60px);pointer-events:none}
		.gmx-col-brand:before{width:220px;height:220px;background:rgba(139,92,246,.35);top:-70px;left:-70px;animation:lgOrb 9s ease-in-out infinite}
		.gmx-col-brand:after{width:180px;height:180px;background:rgba(34,211,238,.22);bottom:-60px;right:-50px;animation:lgOrb 11s ease-in-out infinite reverse}
		@keyframes lgOrb{0%,100%{transform:translateY(0)}50%{transform:translateY(-18px)}}
		.gmx-btop{position:relative;z-index:1}
		.gmx-blogo{display:flex;align-items:center;gap:12px;margin-bottom:24px}
		.gmx-blogo img{width:54px;height:54px;border-radius:15px;box-shadow:0 10px 30px rgba(139,92,246,.45),0 0 0 3px rgba(139,92,246,.16)}
		.gmx-blogo b{display:block;font-size:19px;font-weight:900;color:var(--lg-text)}
		.gmx-blogo span{display:block;font-size:9px;letter-spacing:4px;color:var(--lg-muted);font-weight:700;margin-top:3px}
		.gmx-bhead{margin:0 0 10px;font-size:25px;line-height:1.6;font-weight:900;color:var(--lg-text)}
		.gmx-bhead em{font-style:normal;background:linear-gradient(90deg,#a78bfa,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent}
		.gmx-bsub{margin:0;font-size:12.5px;color:var(--lg-muted);line-height:2.1}
		.gmx-feats{position:relative;z-index:1;display:flex;flex-direction:column;gap:10px}
		.gmx-feat{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:11px 14px;transition:border-color .2s,transform .2s}
		.gmx-feat:hover{border-color:rgba(139,92,246,.45);transform:translateX(-3px)}
		.gmx-feat i{flex:none;width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-style:normal;font-size:16px;background:linear-gradient(135deg,rgba(139,92,246,.32),rgba(34,211,238,.22));border:1px solid rgba(139,92,246,.35)}
		.gmx-feat b{display:block;font-size:12.5px;color:var(--lg-text)}
		.gmx-feat span{display:block;font-size:10.5px;color:var(--lg-muted);margin-top:2px}
		.gmx-bstats{position:relative;z-index:1;display:flex;gap:10px}
		.gmx-bstat{flex:1;text-align:center;background:rgba(139,92,246,.09);border:1px solid rgba(139,92,246,.22);border-radius:14px;padding:12px 6px}
		.gmx-bstat b{display:block;font-size:16px;font-weight:900;color:#c4b5fd}
		.gmx-bstat span{display:block;font-size:10px;color:var(--lg-muted);margin-top:3px}
		/* ستون فرم */
		.gmx-col-form{flex:1;display:flex;flex-direction:column;padding:36px 34px;background:linear-gradient(180deg,rgba(17,21,31,.92),rgba(11,14,22,.94))}
		/* ---------- هدر برند فشرده (موبایل) ---------- */
		.gmx-brand{display:flex;flex-direction:column;align-items:center;gap:8px;margin-bottom:20px;text-align:center}
		.login-logo-img{display:block;width:76px;height:76px;border-radius:20px;box-shadow:0 14px 44px rgba(139,92,246,.5),0 0 0 4px rgba(139,92,246,.14);animation:lgFloat 4.5s ease-in-out infinite}
		@keyframes lgFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
		.gmx-brand strong{font-size:20px;font-weight:900;color:var(--lg-text)}
		.gmx-brand > span{font-size:9.5px;letter-spacing:4px;color:var(--lg-muted);font-weight:700}
		.gmx-welcome{font-size:12px;color:var(--lg-muted);margin-top:2px}
		@media(min-width:861px){.gmx-brand{display:none}}
		.login h1{display:none}
		/* ---------- فرم‌ها (کارت داخلی ندارند — داخل پوسته‌اند) ---------- */
		#loginform,#lostpasswordform,#registerform{background:transparent!important;border:none!important;border-radius:0!important;box-shadow:none!important;padding:0!important;margin:0!important}
		.login label{color:var(--lg-muted)!important;font-size:12.5px;margin-bottom:7px;display:block;font-weight:600}
		.login input[type=text],.login input[type=password],.login input[type=tel],.login input[type=email]{background:var(--lg-card2)!important;border:1.5px solid var(--lg-border)!important;color:var(--lg-text)!important;border-radius:12px!important;padding:12px 14px!important;font-size:15px!important;font-family:inherit!important;width:100%!important;box-sizing:border-box!important;height:48px!important;transition:border-color .15s,box-shadow .15s}
		.login input:focus{border-color:var(--lg-primary)!important;box-shadow:0 0 0 3px rgba(139,92,246,.16)!important;outline:none}
		.login input::placeholder{color:#5a6379}
		.wp-core-ui .button.wp-hide-pw{background:transparent!important;border:none!important}
		.wp-core-ui .button.wp-hide-pw .dashicons{color:var(--lg-muted)}
		#wp-submit,.wp-core-ui .button-primary{background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2))!important;border:none!important;border-radius:12px!important;height:48px!important;padding:0 22px!important;font-size:14.5px!important;font-family:inherit!important;font-weight:800;text-shadow:none!important;width:100%;box-sizing:border-box;cursor:pointer;box-shadow:0 10px 26px rgba(139,92,246,.38)!important;transition:filter .15s,transform .15s}
		#wp-submit:hover,.wp-core-ui .button-primary:hover{filter:brightness(1.13);transform:translateY(-1px)}
		.login #nav,.login #backtoblog{margin:14px 0 0!important;text-align:center}
		.login #backtoblog{margin-top:8px!important}
		.login #nav a,.login #backtoblog a{color:var(--lg-muted)!important;font-size:12.5px;text-decoration:none;transition:color .15s}
		.login #nav a:hover,.login #backtoblog a:hover{color:var(--lg-accent)!important}
		#login_error,.login .message{width:100%;background:rgba(26,32,48,.85)!important;border:1px solid var(--lg-border)!important;color:var(--lg-text)!important;border-radius:12px!important;box-shadow:none!important;font-size:12.5px;margin:0 0 14px!important;padding:11px 14px!important}
		#login_error{border-color:rgba(239,68,68,.45)!important;color:#fca5a5!important}
		/* ---------- تب‌های سگمنتی ---------- */
		.gmx-tabs{display:flex;gap:5px;background:var(--lg-card2);border:1px solid var(--lg-border);border-radius:13px;padding:4px;margin:0 0 20px}
		.gmx-tabs button{flex:1;background:transparent;border:none;color:var(--lg-muted);border-radius:9px;padding:10px 8px;cursor:pointer;font-family:inherit;font-size:12.5px;font-weight:700;transition:background .18s,color .18s;display:flex;align-items:center;justify-content:center;gap:7px}
		.gmx-tabs button:hover{color:var(--lg-text)}
		.gmx-tabs button.is-active{background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2));color:#fff;box-shadow:0 5px 16px rgba(139,92,246,.38)}
		body.gmx-mode-phone #loginform,body.gmx-mode-phone #nav,body.gmx-mode-phone #backtoblog{display:none}
		body.gmx-mode-pass #gmx-phone-panel{display:none!important}
		/* ---------- پنل موبایل (داخل ستون فرم) ---------- */
		#gmx-phone-panel{padding:0;border:none;background:transparent}
		#gmx-phone-panel h2{margin:0 0 4px;font-size:16.5px;color:var(--lg-text);font-weight:800}
		#gmx-phone-panel .gmx-hint{color:var(--lg-muted);font-size:12px;margin:0 0 18px;line-height:1.9}
		.gmx-field{margin-bottom:14px}
		.gmx-field label{display:flex;align-items:center;gap:6px;color:var(--lg-muted);font-size:12.5px;margin-bottom:7px;font-weight:600}
		.gmx-field input{width:100%;box-sizing:border-box;background:var(--lg-card2);border:1.5px solid var(--lg-border);color:var(--lg-text);border-radius:12px;padding:12px 14px;font-size:15px;font-family:inherit;height:48px;letter-spacing:1px;transition:border-color .15s,box-shadow .15s}
		.gmx-field input:focus{outline:none;border-color:var(--lg-primary);box-shadow:0 0 0 3px rgba(139,92,246,.16)}
		.gmx-field input::placeholder{color:#5a6379;letter-spacing:2px}
		.gmx-btn{width:100%;height:48px;background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2));border:none;color:#fff;border-radius:12px;padding:0;font-size:14.5px;font-weight:800;cursor:pointer;font-family:inherit;box-shadow:0 10px 26px rgba(139,92,246,.38);transition:filter .15s,transform .15s;display:flex;align-items:center;justify-content:center;gap:8px}
		.gmx-btn:hover{filter:brightness(1.13);transform:translateY(-1px)}
		.gmx-btn:disabled{opacity:.55;cursor:not-allowed;transform:none}
		.gmx-btn-ghost{background:transparent;border:1.5px solid var(--lg-border);color:var(--lg-muted);box-shadow:none;margin-top:10px}
		.gmx-btn-ghost:hover{border-color:var(--lg-primary);color:var(--lg-text);filter:none}
		/* ---------- باکس کد OTP پنج‌خانه ---------- */
		.gmx-otp-row{display:flex;gap:9px;direction:ltr;justify-content:center;margin-bottom:14px}
		.gmx-otp{width:52px!important;height:58px!important;text-align:center;font-size:23px!important;font-weight:800!important;border-radius:13px!important;padding:0!important;background:var(--lg-card2);border:1.5px solid var(--lg-border);color:var(--lg-text);font-family:inherit;transition:border-color .15s,box-shadow .15s,transform .1s}
		.gmx-otp:focus{outline:none;border-color:var(--lg-primary);box-shadow:0 0 0 3px rgba(139,92,246,.18);transform:translateY(-2px)}
		.gmx-otp.filled{border-color:rgba(139,92,246,.65);background:rgba(139,92,246,.08)}
		.gmx-msg{display:none;margin:0 0 14px;padding:11px 14px;border-radius:12px;font-size:12.5px;line-height:1.9}
		.gmx-msg.ok{display:block;background:rgba(52,211,153,.09);color:#6ee7b7;border:1px solid rgba(52,211,153,.3)}
		.gmx-msg.err{display:block;background:rgba(239,68,68,.09);color:#fca5a5;border:1px solid rgba(239,68,68,.35)}
		.gmx-code-box{display:none}
		.gmx-code-box.show{display:block;animation:lgIn .25s ease}
		@keyframes lgIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
		.gmx-test-code{background:rgba(34,211,238,.08);border:1.5px dashed rgba(34,211,238,.45);color:var(--lg-accent);border-radius:12px;padding:10px 14px;font-size:15px;font-weight:800;margin-bottom:14px;text-align:center;letter-spacing:6px}
		/* ---------- نوار اعتماد و قوانین (پایین ستون فرم) ---------- */
		.gmx-bottom{margin-top:20px;border-top:1px dashed var(--lg-border);padding-top:16px}
		.gmx-trust{display:flex;justify-content:center;gap:14px;flex-wrap:wrap;margin:0}
		.gmx-trust span{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--lg-muted)}
		.gmx-terms{text-align:center;font-size:11px;color:#6b7490;margin:12px 0 0;line-height:1.8}
		.gmx-terms a{color:var(--lg-accent);text-decoration:none}
		/* ---------- چک‌باکس مرا به خاطر بسپار + سوییچر زبان ---------- */
		.login .forgetmenot{display:flex;align-items:center;gap:7px}
		.login .forgetmenot label{margin:0;font-size:12px;color:var(--lg-muted);cursor:pointer}
		.login #rememberme{appearance:none;-webkit-appearance:none;width:18px;height:18px;border-radius:6px;background:var(--lg-card2);border:1.5px solid var(--lg-border);cursor:pointer;position:relative;transition:background .15s,border-color .15s;margin:0}
		.login #rememberme:checked{background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2));border-color:var(--lg-primary)}
		.login #rememberme:checked:after{content:"✓";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:900}
		.login #rememberme:hover{border-color:var(--lg-primary)}
		.login #loginform .submit{display:flex;align-items:center;justify-content:space-between;gap:10px}
		.login #loginform .submit #wp-submit{flex:none;width:auto;min-width:150px}
		body.login #language-switcher{display:none!important}
		/* ---------- صفحه ثبت‌نام ---------- */
		body.login-action-register #gmx-phone-panel{display:none!important}
		#reg_passmail{display:none}
		/* ---------- ریسپانسیو ---------- */
		@media(max-width:860px){.gmx-col-brand{display:none}.gmx-shell{width:460px}.gmx-col-form{padding:32px 28px}}
		@media(max-width:500px){.gmx-shell{width:100%}.gmx-col-form{padding:26px 20px}.gmx-otp{width:46px!important;height:54px!important}}
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
			'dashUrl' => home_url( '/my-account/' ),
		);

		wp_register_script( 'gmx-login', false, array(), '1.3', true );
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

			/* ---------- پوسته دوستونه: پنل برند + ستون فرم ---------- */
			var loginEl = document.getElementById("login");
			if (loginEl && !document.getElementById("gmx-shell")) {
				var shell = document.createElement("div");
				shell.id = "gmx-shell";
				shell.className = "gmx-shell";
				loginEl.parentNode.insertBefore(shell, loginEl);
				shell.appendChild(loginEl);

				var brand = document.createElement("div");
				brand.className = "gmx-col-brand";
				brand.innerHTML =
					"<div class=gmx-btop>" +
						"<div class=gmx-blogo><img src=\"" + CFG.logo + "\" alt=\"\"><div><b>" + (CFG.site || "لوتینو") + "</b><span>L U T I N O</span></div></div>" +
						"<h2 class=gmx-bhead>به <em>دنیای گیمینگ</em> خوش آمدید</h2>" +
						"<p class=gmx-bsub>خرید و فروش امن اکانت، جم، آیتم و خدمات بازی‌های مورد علاقه‌ات — با تحویل سریع و پشتیبانی واقعی.</p>" +
					"</div>" +
					"<div class=gmx-feats>" +
						"<div class=gmx-feat><i>⚡</i><div><b>تحویل فوری</b><span>سفارش‌ها در چند دقیقه انجام می‌شوند</span></div></div>" +
						"<div class=gmx-feat><i>🛡️</i><div><b>پرداخت امانی</b><span>مبلغ تا تایید سفارش نزد لوتینو می‌ماند</span></div></div>" +
						"<div class=gmx-feat><i>🎧</i><div><b>پشتیبانی ۲۴/۷</b><span>همیشه یک پاسخ سریع داری</span></div></div>" +
						"<div class=gmx-feat><i>💎</i><div><b>محصولات تضمینی</b><span>کیفیت همه آگهی‌ها بررسی می‌شود</span></div></div>" +
					"</div>" +
					"<div class=gmx-bstats>" +
						"<div class=gmx-bstat><b>۲,۵۰۰+</b><span>گیمر فعال</span></div>" +
						"<div class=gmx-bstat><b>۱,۲۰۰+</b><span>معامله موفق</span></div>" +
						"<div class=gmx-bstat><b>۴.۹★</b><span>رضایت کاربران</span></div>" +
					"</div>";
				shell.insertBefore(brand, loginEl);

				// انتقال پنل موبایل و نوار اعتماد به داخل ستون فرم.
				var panel = document.getElementById("gmx-phone-panel");
				var bottom = document.getElementById("gmx-bottom");
				if (panel) { loginEl.insertBefore(panel, document.getElementById("nav")); }
				if (bottom) { loginEl.appendChild(bottom); }
			}

			/* ---------- هدر برند فشرده (موبایل) داخل ستون فرم ---------- */
			var form = $("#loginform") || $("#registerform") || $("#lostpasswordform");
			if (form && !document.getElementById("gmx-login-logo")) {
				var compact = document.createElement("div");
				compact.className = "gmx-brand";
				compact.innerHTML = "<img id=gmx-login-logo class=login-logo-img src=\"" + CFG.logo + "\" alt=\"\">" +
					"<strong>" + (CFG.site || "لوتینو") + "</strong>" +
					"<span>L U T I N O</span>" +
					"<span class=gmx-welcome>به مارکت گیمرهای ایران خوش آمدید 🎮</span>";
				form.parentNode.insertBefore(compact, form);
			}

			var isRegister = !!$("#registerform");
			if (!isRegister) {
				var tabs = document.createElement("div");
				tabs.className = "gmx-tabs";
				tabs.innerHTML = "<button type=button id=gmx-tab-pass class=is-active>🔑 ورود با رمز</button><button type=button id=gmx-tab-phone>📱 ورود با موبایل</button>";
				form.parentNode.insertBefore(tabs, form);
			}

			// برچسب: ثبت نام + placeholderهای راهنما.
			document.querySelectorAll("#nav a, #backtoblog a").forEach(function(a){ a.textContent = a.textContent.replace(/نام‌نویسی/g, "ثبت نام"); });
			var ul = document.getElementById("user_login"); if (ul) { ul.placeholder = "نام کاربری یا ایمیل"; }
			var ue = document.getElementById("user_email"); if (ue) { ue.placeholder = "you@example.com"; }

			function setMode(m){
				document.body.className = document.body.className.replace(/gmx-mode-\\w+/g,"").trim();
				document.body.classList.add("gmx-mode-" + m);
				var panelEl = document.getElementById("gmx-phone-panel");
				if (panelEl) { panelEl.style.display = (m === "phone") ? "block" : "none"; }
				var p = $("#gmx-tab-pass"), ph = $("#gmx-tab-phone");
				if (m === "pass") { if(p) p.classList.add("is-active"); if(ph) ph.classList.remove("is-active"); }
				else { if(ph) ph.classList.add("is-active"); if(p) p.classList.remove("is-active"); }
			}
			document.addEventListener("click", function(e){
				if (e.target.id === "gmx-tab-pass") { setMode("pass"); }
				if (e.target.id === "gmx-tab-phone") { setMode("phone"); var f = $("#gmx-phone"); if (f) setTimeout(function(){ f.focus(); }, 50); }
			});
			if (document.getElementById("gmx-tab-pass")) { setMode("pass"); } else { document.body.classList.add("gmx-mode-pass"); }

			/* ---------- OTP پنج‌خانه ---------- */
			var panel = $("#gmx-phone-panel");
			if (!panel) return;
			var step1 = $("#gmx-step-phone"), step2 = $("#gmx-step-code");
			var msg = $("#gmx-phone-msg");
			var phoneInput = $("#gmx-phone"), codeInput = $("#gmx-code");
			var sendBtn = $("#gmx-send-code"), loginBtn = $("#gmx-do-login");
			var backBtn = $("#gmx-back-phone");
			var otps = Array.prototype.slice.call(document.querySelectorAll(".gmx-otp"));
			var timer = null;

			function syncCode(){
				var v = otps.map(function(o){ return (o.value || "").replace(/\\D/g,""); }).join("");
				codeInput.value = v;
				otps.forEach(function(o){ o.classList.toggle("filled", !!(o.value && o.value.trim())); });
				return v;
			}
			otps.forEach(function(o, i){
				o.addEventListener("input", function(){
					o.value = o.value.replace(/\\D/g,"").slice(-1);
					if (o.value && i < otps.length - 1) { otps[i + 1].focus(); }
					if (syncCode().length === 5) { loginBtn.focus(); }
				});
				o.addEventListener("keydown", function(e){
					if (e.key === "Backspace" && !o.value && i > 0) { otps[i - 1].focus(); otps[i - 1].value = ""; syncCode(); e.preventDefault(); }
				});
				o.addEventListener("paste", function(e){
					e.preventDefault();
					var txt = (e.clipboardData || window.clipboardData).getData("text").replace(/\\D/g,"").slice(0, 5);
					for (var k = 0; k < txt.length && k < otps.length; k++) { otps[k].value = txt[k]; }
					syncCode();
					otps[Math.min(txt.length, otps.length - 1)].focus();
					if (txt.length === 5) { loginBtn.focus(); }
				});
			});
			function fillCode(v){
				v = String(v || "").replace(/\\D/g,"").slice(0, 5);
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

	/**
	 * پنل ورود با موبایل + نوار اعتماد (جابه‌جا شده به داخل ستون فرم با JS).
	 */
	public function phone_panel() {
		?>
		<div id="gmx-phone-panel" style="display:none">
			<h2><?php esc_html_e( 'ورود / ثبت‌نام با موبایل', 'gmx-market' ); ?></h2>
			<p class="gmx-hint"><?php esc_html_e( 'شماره موبایل خود را وارد کنید؛ کد تایید برایتان پیامک می‌شود. اگر حساب نداشته باشید، خودکار ساخته می‌شود.', 'gmx-market' ); ?></p>
			<p class="gmx-msg" id="gmx-phone-msg"></p>

			<div id="gmx-step-phone">
				<div class="gmx-field">
					<label for="gmx-phone">📱 <?php esc_html_e( 'شماره موبایل', 'gmx-market' ); ?></label>
					<input type="tel" id="gmx-phone" dir="ltr" placeholder="09xxxxxxxxx" autocomplete="tel" />
				</div>
				<button type="button" class="gmx-btn" id="gmx-send-code"><?php esc_html_e( 'دریافت کد تایید', 'gmx-market' ); ?></button>
			</div>

			<div class="gmx-code-box" id="gmx-step-code">
				<div class="gmx-field">
					<label>✉️ <?php esc_html_e( 'کد ۵ رقمی پیامک‌شده را وارد کنید', 'gmx-market' ); ?></label>
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
				<button type="button" class="gmx-btn" id="gmx-do-login"><?php esc_html_e( 'ورود به حساب', 'gmx-market' ); ?></button>
				<button type="button" class="gmx-btn gmx-btn-ghost" id="gmx-back-phone"><?php esc_html_e( '← تغییر شماره', 'gmx-market' ); ?></button>
			</div>
		</div>

		<div id="gmx-bottom">
			<div class="gmx-trust">
				<span>⚡ <?php esc_html_e( 'تحویل فوری', 'gmx-market' ); ?></span>
				<span>🛡️ <?php esc_html_e( 'پرداخت امانی', 'gmx-market' ); ?></span>
				<span>🎧 <?php esc_html_e( 'پشتیبانی ۲۴/۷', 'gmx-market' ); ?></span>
			</div>
			<p class="gmx-terms"><?php esc_html_e( 'با ورود یا ثبت‌نام،', 'gmx-market' ); ?> <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'قوانین لوتینو', 'gmx-market' ); ?></a> <?php esc_html_e( 'را می‌پذیرید.', 'gmx-market' ); ?></p>
		</div>
		<?php
	}
}
