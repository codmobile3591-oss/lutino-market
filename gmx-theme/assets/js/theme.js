/* لوتینو — تعاملات سراسری قالب (منوی موبایل و ...) */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var toggle = document.getElementById('gmx-nav-toggle');
		var panel = document.getElementById('gmx-mobile-nav');
		if (!toggle || !panel) {
			return;
		}

		toggle.addEventListener('click', function () {
			var open = panel.classList.toggle('is-open');
			toggle.classList.toggle('is-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				panel.removeAttribute('hidden');
			} else {
				panel.setAttribute('hidden', '');
			}
		});

		// بستن منو با کلیک بیرون یا تغییر عرض به دسکتاپ.
		document.addEventListener('click', function (e) {
			if (panel.classList.contains('is-open') && !panel.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
				panel.classList.remove('is-open');
				toggle.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
				panel.setAttribute('hidden', '');
			}
		});
		window.addEventListener('resize', function () {
			if (window.innerWidth > 820 && panel.classList.contains('is-open')) {
				panel.classList.remove('is-open');
				toggle.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
				panel.setAttribute('hidden', '');
			}
		});
	});
})();
