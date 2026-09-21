<?php
/**
 * صفحه ورود اختصاصی: دارک گیمینگ + ورود با موبایل.
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
		$css = '
		:root{--lg-bg:#0a0d14;--lg-card:#141925;--lg-border:#242c3e;--lg-text:#eef0f6;--lg-muted:#9aa2b8;--lg-primary:#8b5cf6;--lg-primary2:#6d28d9;--lg-accent:#22d3ee;--lg-danger:#ef4444}
		body.login{background:radial-gradient(900px 500px at 85% -10%,rgba(139,92,246,.16),transparent),radial-gradient(700px 400px at 5% 110%,rgba(34,211,238,.08),transparent),var(--lg-bg)!important;font-family:Vazirmatn,Tahoma,sans-serif}
		body.login:before{content:"";display:none}
		.login-logo-img{display:block;width:76px;height:76px;margin:0 auto 14px;border-radius:18px;box-shadow:0 12px 40px rgba(139,92,246,.45);animation:lgFloat 4.5s ease-in-out infinite}
		@keyframes lgFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
		#login{width:400px;padding:80px 0 40px}
		.login h1 a{background:none!important;text-indent:0!important;width:auto!important;height:auto!important;font-size:24px;font-weight:900;color:var(--lg-text)!important;margin-bottom:8px;background-image:none!important}
		#loginform,#lostpasswordform,#registerform{background:var(--lg-card)!important;border:1px solid var(--lg-border)!important;border-radius:16px!important;box-shadow:0 18px 60px rgba(0,0,0,.45)!important;padding:28px 26px!important}
		.login label{color:var(--lg-muted)!important;font-size:13px;margin-bottom:6px;display:block}
		.login input[type=text],.login input[type=password],.login input[type=tel],.login input[type=email]{background:#0d1017!important;border:1px solid var(--lg-border)!important;color:var(--lg-text)!important;border-radius:10px!important;padding:11px 14px!important;font-size:15px!important;font-family:inherit!important;width:100%!important;box-sizing:border-box!important;transition:border-color .15s}
		.login input:focus{border-color:var(--lg-primary)!important;box-shadow:none!important;outline:none}
		.wp-core-ui .button.wp-hide-pw{background:transparent!important;border:none!important}
		.wp-core-ui .button.wp-hide-pw .dashicons{color:var(--lg-muted)}
		#wp-submit,.wp-core-ui .button-primary{background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2))!important;border:none!important;border-radius:10px!important;height:auto!important;padding:12px 22px!important;font-size:15px!important;font-family:inherit!important;font-weight:700;text-shadow:none!important;width:100%;box-sizing:border-box}
		#wp-submit:hover,.wp-core-ui .button-primary:hover{filter:brightness(1.15)}
		.login #nav,.login #backtoblog{margin:14px 0 0!important}
		.login #nav a,.login #backtoblog a{color:var(--lg-muted)!important;font-size:13px;text-decoration:none}
		.login #nav a:hover,.login #backtoblog a:hover{color:var(--lg-accent)!important}
		#login_error,.login .message{background:#1a2030!important;border-color:var(--lg-border)!important;color:var(--lg-text)!important;border-radius:10px!important;box-shadow:none!important}
		#login_error{border-color:rgba(239,68,68,.4)!important;color:#fca5a5!important}
		/* تب‌ها */
		.gmx-tabs{display:flex;gap:8px;margin-bottom:18px}
		.gmx-tabs button{flex:1;background:#0d1017;border:1px solid var(--lg-border);color:var(--lg-muted);border-radius:10px;padding:10px;cursor:pointer;font-family:inherit;font-size:13px;font-weight:700}
		.gmx-tabs button.is-active{background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2));color:#fff;border-color:transparent}
		body.gmx-mode-phone #loginform,body.gmx-mode-phone #nav,body.gmx-mode-phone #backtoblog{display:none}
		body.gmx-mode-pass #gmx-phone-panel{display:none}
		#gmx-phone-panel{background:var(--lg-card);border:1px solid var(--lg-border);border-radius:16px;box-shadow:0 18px 60px rgba(0,0,0,.45);padding:28px 26px}
		#gmx-phone-panel h2{margin:0 0 6px;font-size:16px;color:var(--lg-text)}
		#gmx-phone-panel .gmx-hint{color:var(--lg-muted);font-size:12px;margin:0 0 16px;line-height:1.8}
		.gmx-field{margin-bottom:12px}
		.gmx-field label{display:block;color:var(--lg-muted);font-size:13px;margin-bottom:6px}
		.gmx-field input{width:100%;box-sizing:border-box;background:#0d1017;border:1px solid var(--lg-border);color:var(--lg-text);border-radius:10px;padding:11px 14px;font-size:15px;font-family:inherit}
		.gmx-field input:focus{outline:none;border-color:var(--lg-primary)}
		.gmx-btn{width:100%;background:linear-gradient(135deg,var(--lg-primary),var(--lg-primary2));border:none;color:#fff;border-radius:10px;padding:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit}
		.gmx-btn:disabled{opacity:.55;cursor:not-allowed}
		.gmx-btn-ghost{background:transparent;border:1px solid var(--lg-border);color:var(--lg-muted);margin-top:8px}
		.gmx-msg{display:none;margin:0 0 14px;padding:10px 14px;border-radius:10px;font-size:13px;line-height:1.8}
		.gmx-msg.ok{display:block;background:rgba(34,197,94,.1);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
		.gmx-msg.err{display:block;background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.3)}
		.gmx-code-box{display:none}
		.gmx-code-box.show{display:block}
		.gmx-test-code{background:rgba(34,211,238,.08);border:1px dashed rgba(34,211,238,.4);color:var(--lg-accent);border-radius:10px;padding:10px 14px;font-size:14px;margin-bottom:12px;text-align:center;letter-spacing:4px}
		@media(max-width:480px){#login{width:92%}}
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
			'i18n'    => array(
				'sendCode' => __( 'دریافت کد تایید', 'gmx-market' ),
				'resend'   => __( 'ارسال مجدد کد', 'gmx-market' ),
				'sending'  => __( 'در حال ارسال…', 'gmx-market' ),
				'login'    => __( 'ورود به حساب', 'gmx-market' ),
				'countdown' => __( 'ثانیه تا ارسال مجدد', 'gmx-market' ),
			),
			'dashUrl' => home_url( '/my-account/' ),
		);

		wp_register_script( 'gmx-login', false, array(), '1.0', true );
		wp_enqueue_script( 'gmx-login' );
		wp_localize_script( 'gmx-login', 'GMXLogin', $data );

		$js = '
		(function(){
			var CFG = window.GMXLogin || {};
			var I18N = CFG.i18n || {};
			function $(s){ return document.querySelector(s); }
			function post(url, data){
				return fetch(CFG.restUrl.replace(/\/$/,"") + url, {
					method: "POST",
					headers: {"Content-Type":"application/json","X-WP-Nonce":CFG.nonce},
					credentials: "same-origin",
					body: JSON.stringify(data)
				}).then(function(r){ return r.json(); });
			}
			// تزریق لوگو و تب‌ها بالای فرم.
			var form = $("#loginform") || $("#registerform") || $("#lostpasswordform");
			if (form) {
				if (!document.getElementById("gmx-login-logo")) {
					var lg = document.createElement("img");
					lg.id = "gmx-login-logo"; lg.className = "login-logo-img";
					lg.src = CFG.logo; lg.alt = "";
					form.parentNode.insertBefore(lg, form);
				}
				var tabs = document.createElement("div");
				tabs.className = "gmx-tabs";
				tabs.innerHTML = "<button type=button id=gmx-tab-pass class=is-active>🔑 رمز عبور</button><button type=button id=gmx-tab-phone>📱 ورود با موبایل</button>";
				form.parentNode.insertBefore(tabs, form);
			}
			function setMode(m){
				document.body.className = document.body.className.replace(/gmx-mode-\w+/g,"").trim();
				document.body.classList.add("gmx-mode-"+m);
				var p=$("#gmx-tab-pass"), ph=$("#gmx-tab-phone");
				if(m==="pass"){ p.classList.add("is-active"); ph.classList.remove("is-active"); }
				else { ph.classList.add("is-active"); p.classList.remove("is-active"); }
			}
			document.addEventListener("click", function(e){
				if(e.target.id==="gmx-tab-pass"){ setMode("pass"); }
				if(e.target.id==="gmx-tab-phone"){ setMode("phone"); }
			});
			setMode("pass");

			var panel = $("#gmx-phone-panel");
			if(!panel) return;
			var step1 = $("#gmx-step-phone"), step2 = $("#gmx-step-code");
			var msg = $("#gmx-phone-msg");
			var phoneInput = $("#gmx-phone"), codeInput = $("#gmx-code");
			var sendBtn = $("#gmx-send-code"), loginBtn = $("#gmx-do-login");
			var timer = null;

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
					if(left <= 0){ clearInterval(timer); sendBtn.disabled = false; sendBtn.textContent = I18N.resend; return; }
					sendBtn.textContent = left + " " + I18N.countdown;
				}, 1000);
			}

			sendBtn.addEventListener("click", function(){
				var phone = (phoneInput.value||"").trim();
				if(!phone){ showMsg("شماره موبایل را وارد کنید.", false); return; }
				sendBtn.disabled = true; sendBtn.textContent = I18N.sending;
				post("/auth/send", {phone: phone}).then(function(r){
					if(r && r.ok){
						showMsg("کد تایید پیامک شد" + (r.test_code ? " — حالت تست، کد: " + r.test_code : "؛ لطفاً کد را وارد کنید."), true);
						if(r.test_code){ codeInput.value = r.test_code; }
						step2.classList.add("show");
						codeInput.focus();
						countdown(120);
					} else {
						showMsg((r && r.message) || "خطا در ارسال کد.", false);
						sendBtn.disabled = false; sendBtn.textContent = I18N.sendCode;
					}
				}).catch(function(){ showMsg("خطای شبکه.", false); sendBtn.disabled=false; sendBtn.textContent=I18N.sendCode; });
			});

			loginBtn.addEventListener("click", function(){
				var phone = (phoneInput.value||"").trim();
				var code = (codeInput.value||"").trim();
				if(!phone || !code){ showMsg("شماره و کد را وارد کنید.", false); return; }
				loginBtn.disabled = true; loginBtn.textContent = "...";
				post("/auth/verify", {phone: phone, code: code}).then(function(r){
					if(r && r.ok){
						showMsg("خوش آمدید! در حال انتقال…", true);
						window.location.href = r.redirect || CFG.dashUrl;
					} else {
						showMsg((r && r.message) || "کد نامعتبر است.", false);
						loginBtn.disabled = false; loginBtn.textContent = I18N.login;
					}
				}).catch(function(){ showMsg("خطای شبکه.", false); loginBtn.disabled=false; loginBtn.textContent=I18N.login; });
			});
		})();
		';
		wp_add_inline_script( 'gmx-login', $js );
	}

	/**
	 * پنل ورود با موبایل (پایین فرم پیش‌فرض).
	 */
	public function phone_panel() {
		?>
		<div id="gmx-phone-panel" style="display:none">
			<h2><?php esc_html_e( 'ورود / ثبت‌نام با موبایل', 'gmx-market' ); ?></h2>
			<p class="gmx-hint"><?php esc_html_e( 'شماره موبایل خود را وارد کنید؛ کد تایید برایتان پیامک می‌شود. اگر حساب نداشته باشید، خودکار ساخته می‌شود.', 'gmx-market' ); ?></p>
			<p class="gmx-msg" id="gmx-phone-msg"></p>

			<div id="gmx-step-phone">
				<div class="gmx-field">
					<label for="gmx-phone"><?php esc_html_e( 'شماره موبایل', 'gmx-market' ); ?></label>
					<input type="tel" id="gmx-phone" dir="ltr" placeholder="09xxxxxxxxx" autocomplete="tel" />
				</div>
				<button type="button" class="gmx-btn" id="gmx-send-code"><?php esc_html_e( 'دریافت کد تایید', 'gmx-market' ); ?></button>
			</div>

			<div class="gmx-code-box" id="gmx-step-code">
				<div class="gmx-field">
					<label for="gmx-code"><?php esc_html_e( 'کد ۵ رقمی', 'gmx-market' ); ?></label>
					<input type="text" id="gmx-code" dir="ltr" maxlength="5" inputmode="numeric" placeholder="- - - - -" />
				</div>
				<button type="button" class="gmx-btn" id="gmx-do-login"><?php esc_html_e( 'ورود به حساب', 'gmx-market' ); ?></button>
			</div>
		</div>
		<script>
		// نمایش پنل موبایل (بدون تداخل با حالت پیش‌فرض).
		document.addEventListener('DOMContentLoaded', function(){
			var p = document.getElementById('gmx-phone-panel');
			if (p) { p.style.display = 'block'; }
		});
		</script>
		<?php
	}
}
