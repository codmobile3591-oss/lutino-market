/* لوتینو — افکت‌های صفحه اصلی (بدون jQuery، سبک) */
(function () {
	'use strict';

	var faNum = function (n) {
		return String(n).replace(/\d/g, function (d) {
			return '۰۱۲۳۴۵۶۷۸۹'[d];
		});
	};
	var pad2 = function (n) {
		return n < 10 ? '0' + n : '' + n;
	};

	/* ---------- ذرات پس‌زمینه هیرو + پارالاکس ماوس ---------- */
	var canvas = document.querySelector('.hero-grid-bg');
	if (canvas && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		var ctx = canvas.getContext('2d');
		var parts = [];
		var W = 0, H = 0, mx = 0.5, my = 0.5;

		var resize = function () {
			var rect = canvas.parentElement.getBoundingClientRect();
			W = canvas.width = rect.width;
			H = canvas.height = rect.height;
		};

		var init = function () {
			parts = [];
			var count = Math.min(70, Math.floor(W / 18));
			for (var i = 0; i < count; i++) {
				parts.push({
					x: Math.random() * W,
					y: Math.random() * H,
					r: Math.random() * 1.8 + 0.6,
					vx: (Math.random() - 0.5) * 0.25,
					vy: (Math.random() - 0.5) * 0.25,
					depth: Math.random() * 0.6 + 0.4
				});
			}
		};

		var frame = function () {
			ctx.clearRect(0, 0, W, H);
			for (var i = 0; i < parts.length; i++) {
				var p = parts[i];
				p.x += p.vx; p.y += p.vy;
				if (p.x < 0) p.x = W; if (p.x > W) p.x = 0;
				if (p.y < 0) p.y = H; if (p.y > H) p.y = 0;
				var ox = (mx - 0.5) * 18 * p.depth;
				var oy = (my - 0.5) * 12 * p.depth;
				ctx.beginPath();
				ctx.arc(p.x + ox, p.y + oy, p.r, 0, Math.PI * 2);
				ctx.fillStyle = 'rgba(139, 92, 246, ' + (0.16 + p.depth * 0.22) + ')';
				ctx.fill();
			}
			// خطوط اتصال نزدیک.
			for (var a = 0; a < parts.length; a++) {
				for (var b = a + 1; b < parts.length; b++) {
					var dx = parts[a].x - parts[b].x, dy = parts[a].y - parts[b].y;
					var d2 = dx * dx + dy * dy;
					if (d2 < 8100) {
						ctx.strokeStyle = 'rgba(34, 211, 238, ' + (0.09 * (1 - d2 / 8100)) + ')';
						ctx.lineWidth = 1;
						ctx.beginPath();
						ctx.moveTo(parts[a].x, parts[a].y);
						ctx.lineTo(parts[b].x, parts[b].y);
						ctx.stroke();
					}
				}
			}
			requestAnimationFrame(frame);
		};

		window.addEventListener('mousemove', function (e) {
			var r = canvas.getBoundingClientRect();
			mx = (e.clientX - r.left) / r.width;
			my = (e.clientY - r.top) / r.height;
		}, { passive: true });

		window.addEventListener('resize', function () { resize(); init(); });
		resize(); init();
		requestAnimationFrame(frame);
	}

	/* ---------- شمارنده متحرک آمار ---------- */
	var counters = document.querySelectorAll('[data-gmx-count]');
	if (counters.length) {
		var animateCount = function (el) {
			var target = parseInt(el.getAttribute('data-gmx-count'), 10) || 0;
			var dur = 1400, t0 = null;
			var step = function (ts) {
				if (!t0) t0 = ts;
				var k = Math.min(1, (ts - t0) / dur);
				var eased = 1 - Math.pow(1 - k, 3);
				el.textContent = faNum(Math.round(target * eased));
				if (k < 1) requestAnimationFrame(step);
			};
			requestAnimationFrame(step);
		};
		var ioC = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					animateCount(en.target);
					ioC.unobserve(en.target);
				}
			});
		}, { threshold: 0.4 });
		counters.forEach(function (c) { ioC.observe(c); });
	}

	/* ---------- شمارش معکوس پیشنهاد ویژه ---------- */
	var cd = document.querySelector('.gmx-countdown');
	if (cd) {
		var deadline = parseInt(cd.getAttribute('data-gmx-deadline'), 10) * 1000;
		var hEl = cd.querySelector('.cd-h'), mEl = cd.querySelector('.cd-m'), sEl = cd.querySelector('.cd-s');
		var tick = function () {
			var diff = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
			var h = Math.floor(diff / 3600);
			var m = Math.floor((diff % 3600) / 60);
			var s = diff % 60;
			if (hEl) hEl.textContent = faNum(pad2(h));
			if (mEl) mEl.textContent = faNum(pad2(m));
			if (sEl) sEl.textContent = faNum(pad2(s));
		};
		tick();
		setInterval(tick, 1000);
	}

	/* ---------- تیکر زنده: کلون حلقه‌ای ---------- */
	var list = document.querySelector('.live-list');
	if (list) {
		var clone = list.cloneNode(true);
		clone.removeAttribute('class');
		clone.setAttribute('class', 'live-list live-list-clone');
		clone.setAttribute('aria-hidden', 'true');
		list.parentElement.appendChild(clone);
	}

	/* ---------- اسلایدر بنر ---------- */
	var slider = document.querySelector('[data-gmx-slider]');
	if (slider) {
		var slides = slider.querySelectorAll('.gmx-slide');
		var dotsBox = slider.querySelector('.slide-dots');
		var cur = 0, timer = null;

		if (slides.length && dotsBox) {
			slides.forEach(function (_, i) {
				var d = document.createElement('button');
				d.type = 'button';
				d.className = 'slide-dot' + (i === 0 ? ' is-active' : '');
				d.setAttribute('aria-label', 'اسلاید ' + faNum(i + 1));
				d.addEventListener('click', function () { go(i); restart(); });
				dotsBox.appendChild(d);
			});
		}

		var dots = dotsBox ? dotsBox.querySelectorAll('.slide-dot') : [];

		var go = function (n) {
			cur = (n + slides.length) % slides.length;
			slides.forEach(function (s, i) {
				s.classList.toggle('is-active', i === cur);
			});
			dots.forEach(function (d, i) {
				d.classList.toggle('is-active', i === cur);
			});
		};

		var restart = function () {
			clearInterval(timer);
			timer = setInterval(function () { go(cur + 1); }, 5200);
		};

		var nextBtn = slider.querySelector('.slide-next');
		var prevBtn = slider.querySelector('.slide-prev');
		if (nextBtn) nextBtn.addEventListener('click', function () { go(cur + 1); restart(); });
		if (prevBtn) prevBtn.addEventListener('click', function () { go(cur - 1); restart(); });

		go(0);
		restart();
	}

	/* ---------- تب محصولات ---------- */
	var tabs = document.querySelectorAll('.ptab');
	if (tabs.length) {
		tabs.forEach(function (t) {
			t.addEventListener('click', function () {
				var key = t.getAttribute('data-ptab');
				tabs.forEach(function (x) { x.classList.remove('is-active'); });
				t.classList.add('is-active');
				document.querySelectorAll('.ptab-panel').forEach(function (p) {
					p.classList.toggle('is-active', p.getAttribute('data-ptab-panel') === key);
				});
			});
		});
	}

	/* ---------- تازه‌سازی زنده اخبار (REST، هر ۳۰ ثانیه) ---------- */
	var newsList = document.getElementById('gmx-news-list');
	if (newsList && window.gmxThemeRest && window.gmxThemeRest.url) {
		var fadeNews = function () {
			newsList.style.transition = 'opacity .35s';
			newsList.style.opacity = '0.35';
		};
		var refreshNews = function () {
			fadeNews();
			fetch(window.gmxThemeRest.url + 'gmx/v1/news', { credentials: 'same-origin' })
				.then(function (r) { return r.ok ? r.json() : null; })
				.then(function (data) {
					if (data && data.html) {
						newsList.innerHTML = data.html;
					}
				})
				.catch(function () {})
				.finally(function () {
					newsList.style.opacity = '1';
				});
		};
		setInterval(refreshNews, 30000);
		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) refreshNews();
		});
	}

	/* ---------- ریویل اسکرول ---------- */
	var reveals = document.querySelectorAll('.reveal');
	if (reveals.length) {
		if ('IntersectionObserver' in window) {
			var ioR = new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					if (en.isIntersecting) {
						en.target.classList.add('is-visible');
						ioR.unobserve(en.target);
					}
				});
			}, { threshold: 0.12 });
			reveals.forEach(function (r) { ioR.observe(r); });
		} else {
			reveals.forEach(function (r) { r.classList.add('is-visible'); });
		}
	}
})();
