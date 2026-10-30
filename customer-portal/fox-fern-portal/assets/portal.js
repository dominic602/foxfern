/* Fox & Fern Customer Portal: small progressive enhancements. Every feature works without JavaScript. */
(function () {
	'use strict';

	// Phone photos are 3-8 MB. Shrinking them in the browser before upload makes posting from a job
	// site much faster and keeps uploads under the server's size limit. The server resizes again anyway.
	var MAX_DIM = 2400;
	var MIN_BYTES = 1.5 * 1024 * 1024;
	var SHRINKABLE = /^image\/(jpeg|webp|heic|heif)$/i;

	function shrink(file) {
		if (!SHRINKABLE.test(file.type) || file.size < MIN_BYTES || !window.createImageBitmap) {
			return Promise.resolve(file);
		}
		return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
			var scale = Math.min(1, MAX_DIM / Math.max(bmp.width, bmp.height));
			var canvas = document.createElement('canvas');
			canvas.width = Math.round(bmp.width * scale);
			canvas.height = Math.round(bmp.height * scale);
			canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
			return new Promise(function (resolve) {
				canvas.toBlob(function (blob) {
					if (!blob || blob.size >= file.size) {
						resolve(file);
						return;
					}
					resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: file.lastModified }));
				}, 'image/jpeg', 0.85);
			});
		}).catch(function () { return file; });
	}

	function busy(form, label) {
		var btn = form.querySelector('[type=submit]');
		if (btn) {
			btn.textContent = label;
			setTimeout(function () { btn.disabled = true; }, 0);
		}
	}

	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form.getAttribute) { return; }
		// Confirm destructive forms.
		var msg = form.getAttribute('data-confirm');
		if (msg && !window.confirm(msg)) {
			e.preventDefault();
			return;
		}
		if (form.enctype !== 'multipart/form-data') { return; }
		var inputs = Array.prototype.filter.call(form.querySelectorAll('input[type=file]'), function (input) {
			return Array.prototype.some.call(input.files || [], function (f) { return SHRINKABLE.test(f.type) && f.size >= MIN_BYTES; });
		});
		if (form.dataset.shrunk || !inputs.length || typeof DataTransfer === 'undefined') {
			busy(form, 'Uploading…');
			return;
		}
		e.preventDefault();
		busy(form, 'Preparing photos…');
		Promise.all(inputs.map(function (input) {
			return Promise.all(Array.prototype.map.call(input.files, shrink)).then(function (files) {
				var dt = new DataTransfer();
				files.forEach(function (f) { dt.items.add(f); });
				input.files = dt.files;
			});
		})).catch(function () {}).then(function () {
			form.dataset.shrunk = '1';
			var btn = form.querySelector('[type=submit]');
			if (btn) { btn.textContent = 'Uploading…'; }
			HTMLFormElement.prototype.submit.call(form);
		});
	});

	document.addEventListener('DOMContentLoaded', function () {
		// Show / hide password.
		document.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var input = document.getElementById(btn.getAttribute('aria-controls'));
				var show = input.type === 'password';
				input.type = show ? 'text' : 'password';
				btn.textContent = show ? 'Hide' : 'Show';
			});
		});

		// List chosen files under file inputs.
		document.querySelectorAll('input[type=file][data-file-list]').forEach(function (input) {
			input.addEventListener('change', function () {
				var scope = input.closest('.field, .form-foot') || input.parentNode;
				var list = scope.querySelector('.file-list') || (input.closest('form') && input.closest('form').querySelector('.file-list'));
				if (!list) { return; }
				var names = Array.prototype.map.call(input.files, function (f) { return f.name; });
				list.textContent = names.length ? names.length + ' selected: ' + names.join(', ') : '';
			});
		});

		// Copy invite link.
		document.querySelectorAll('[data-copy]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var input = btn.parentNode.querySelector('input');
				input.select();
				var done = function () { btn.textContent = 'Copied'; };
				if (navigator.clipboard) {
					navigator.clipboard.writeText(input.value).then(done, function () { document.execCommand('copy'); done(); });
				} else {
					document.execCommand('copy');
					done();
				}
			});
		});

		// Print button on the home record.
		document.querySelectorAll('[data-print]').forEach(function (btn) {
			btn.addEventListener('click', function () { window.print(); });
		});

		// Warranty fields only matter for warranty documents.
		document.querySelectorAll('select[data-warranty-toggle]').forEach(function (select) {
			var form = select.closest('form');
			var box = form && form.querySelector('.warranty-only');
			if (!box) { return; }
			var sync = function () { box.hidden = select.value !== 'warranty'; };
			select.addEventListener('change', sync);
			sync();
		});

		// Change-request hint in the message composer.
		document.querySelectorAll('.compose').forEach(function (box) {
			var sync = function () {
				var checked = box.querySelector('input[name=type]:checked');
				box.classList.toggle('is-cr', !!checked && checked.value === 'change_request');
			};
			box.addEventListener('change', sync);
			sync();
		});

		// Settings: dropdown choices only for dropdown fields; add field rows.
		var syncType = function (select) {
			var opts = select.parentNode.querySelector('.opts');
			if (opts) { opts.hidden = select.value !== 'select'; }
		};
		document.addEventListener('change', function (e) {
			if (e.target.matches && e.target.matches('[data-type-select]')) { syncType(e.target); }
		});
		var addBtn = document.querySelector('[data-add-row]');
		var tpl = document.getElementById('ffp-field-row');
		var rows = document.querySelector('[data-rows]');
		if (addBtn && tpl && rows) {
			var next = rows.children.length + 100;
			addBtn.addEventListener('click', function () {
				var html = tpl.innerHTML.replace(/__i__/g, String(next));
				var tmp = document.createElement('tbody');
				tmp.innerHTML = html;
				var row = tmp.firstElementChild;
				row.querySelector('.order').value = String((rows.children.length + 1) * 10);
				rows.appendChild(row);
				next++;
				row.querySelector('input[type=text]').focus();
			});
		}

		// Photo lightbox with arrows, swipe and keyboard.
		var lb = document.getElementById('lb');
		if (!lb) { return; }
		var img = lb.querySelector('img');
		var cap = lb.querySelector('figcaption');
		var group = [];
		var idx = 0;
		var opener = null;
		var show = function (i) {
			idx = (i + group.length) % group.length;
			var a = group[idx];
			img.src = a.href;
			img.alt = (a.querySelector('img') && a.querySelector('img').alt) || '';
			cap.textContent = a.getAttribute('data-caption') || '';
			lb.querySelector('.lb-prev').hidden = lb.querySelector('.lb-next').hidden = group.length < 2;
		};
		var close = function () {
			lb.hidden = true;
			img.removeAttribute('src');
			document.body.style.overflow = '';
			if (opener) { opener.focus(); }
		};
		document.addEventListener('click', function (e) {
			var a = e.target.closest && e.target.closest('a.lb-link');
			if (!a || e.metaKey || e.ctrlKey) { return; }
			e.preventDefault();
			opener = a;
			var name = a.getAttribute('data-group');
			group = name ? Array.prototype.slice.call(document.querySelectorAll('a.lb-link[data-group="' + name + '"]')) : [a];
			lb.hidden = false;
			document.body.style.overflow = 'hidden';
			show(group.indexOf(a));
			lb.querySelector('.lb-close').focus();
		});
		lb.querySelector('.lb-close').addEventListener('click', close);
		lb.querySelector('.lb-prev').addEventListener('click', function () { show(idx - 1); });
		lb.querySelector('.lb-next').addEventListener('click', function () { show(idx + 1); });
		lb.addEventListener('click', function (e) { if (e.target === lb) { close(); } });
		document.addEventListener('keydown', function (e) {
			if (lb.hidden) { return; }
			if (e.key === 'Escape') { close(); }
			if (e.key === 'ArrowLeft') { show(idx - 1); }
			if (e.key === 'ArrowRight') { show(idx + 1); }
		});
		var startX = null;
		lb.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
		lb.addEventListener('touchend', function (e) {
			if (startX === null) { return; }
			var dx = e.changedTouches[0].clientX - startX;
			if (Math.abs(dx) > 50) { show(idx + (dx < 0 ? 1 : -1)); }
			startX = null;
		});
	});
}());
