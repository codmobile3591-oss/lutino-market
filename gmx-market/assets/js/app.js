/* GMX Market — Dashboard JS (chat polling, confirm, notifications) */
(function () {
	'use strict';

	var CFG = window.GMX || {};
	var REST = CFG.restUrl || '/wp-json/gmx/v1';
	var NONCE = CFG.nonce || '';
	var I18N = CFG.i18n || {};

	/* ---------- Confirm dialogs ---------- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-gmx-confirm]');
		if (btn && !window.confirm(btn.getAttribute('data-gmx-confirm') || I18N.confirm)) {
			e.preventDefault();
			e.stopPropagation();
		}
	});

	/* ---------- Chat ---------- */
	var chatBox = document.getElementById('gmx-chat-messages');
	var chatForm = document.getElementById('gmx-chat-form');
	var chatId = 0;
	var lastId = 0;
	var pollTimer = null;

	function el(tag, cls, html) {
		var n = document.createElement(tag);
		if (cls) n.className = cls;
		if (html !== undefined) n.innerHTML = html;
		return n;
	}

	function escapeHtml(str) {
		var d = document.createElement('div');
		d.textContent = str || '';
		return d.innerHTML;
	}

	function renderMsg(m) {
		var me = parseInt(m.sender, 10) === parseInt(CFG.userId || 0, 10);
		var wrap = el('div', 'gmx-chat-msg ' + (me ? 'is-me' : 'is-them'));
		var body = m.attach || '';
		if (m.body) body += (body ? '<br/>' : '') + escapeHtml(m.body);
		wrap.innerHTML = body + '<small>' + escapeHtml(m.time || '') + (m.seen && me ? ' ✓✓' : me ? ' ✓' : '') + '</small>';
		chatBox.appendChild(wrap);
	}

	function scrollToBottom() {
		chatBox.scrollTop = chatBox.scrollHeight;
	}

	function fetchMessages(afterId) {
		if (!chatId) return;
		var url = REST + '/chat/messages?chat_id=' + chatId + '&after_id=' + (afterId || 0);
		fetch(url, { headers: { 'X-WP-Nonce': NONCE }, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data && data.messages && data.messages.length) {
					data.messages.forEach(function (m) {
						renderMsg(m);
						lastId = Math.max(lastId, parseInt(m.id, 10) || 0);
					});
					scrollToBottom();
					markSeen();
				}
			})
			.catch(function () {});
	}

	function markSeen() {
		if (!chatId) return;
		fetch(REST + '/chat/seen', {
			method: 'POST',
			headers: { 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' },
			credentials: 'same-origin',
			body: JSON.stringify({ chat_id: chatId })
		}).catch(function () {});
	}

	function startPolling() {
		if (pollTimer) clearInterval(pollTimer);
		pollTimer = setInterval(function () {
			fetchMessages(lastId);
		}, parseInt(I18N.pollInterval, 10) || 4000);
	}

	if (chatBox && chatForm) {
		var chatWidget = chatBox.closest('.gmx-chat');
		chatId = chatWidget ? parseInt(chatWidget.getAttribute('data-chat-id'), 10) : 0;

		if (chatId) {
			CFG.userId = parseInt(CFG.userId, 10) || (window.GMX_currentUser || 0);
			fetchMessages(0);
			startPolling();

			chatForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var input = chatForm.querySelector('input[name=body]');
				var file = chatForm.querySelector('input[type=file]');
				var body = (input.value || '').trim();
				var hasFile = file && file.files && file.files.length;

				if (!body && !hasFile) return;

				var fd = new FormData();
				fd.append('chat_id', chatId);
				fd.append('body', body);
				if (hasFile) fd.append('file', file.files[0]);

				var btn = chatForm.querySelector('button[type=submit]');
				if (btn) btn.disabled = true;

				fetch(REST + '/chat/send', {
					method: 'POST',
					headers: { 'X-WP-Nonce': NONCE },
					credentials: 'same-origin',
					body: fd
				})
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (btn) btn.disabled = false;
						if (data && data.ok) {
							input.value = '';
							if (file) file.value = '';
							fetchMessages(lastId);
						} else {
							window.alert((data && data.message) || I18N.uploadFail);
						}
					})
					.catch(function () {
						if (btn) btn.disabled = false;
						window.alert(I18N.uploadFail);
					});
			});
		}
	}

	/* ---------- Notification badge ---------- */
	var bell = document.getElementById('gmx-notif-bell');
	if (bell) {
		bell.addEventListener('click', function () {
			window.location.href = CFG.dashUrl || '/';
		});
	}
})();
