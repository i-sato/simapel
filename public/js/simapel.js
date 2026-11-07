// SIMAPEL — perilaku formulir global (tanpa dependensi, kompatibel dengan CSP 'self').
//  - data-confirm="..." pada tombol submit: minta konfirmasi sebelum mengirim.
//  - Cegah kirim ganda dan tampilkan status "Memproses…" pada tombol.
//  - data-copy-target="id-elemen" pada tombol: salin isi elemen ke clipboard;
//    data-copy-status="id-elemen" (opsional) menerima pesan hasilnya.
'use strict';

document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    var button = event.submitter;
    if (button && button.dataset.confirm && !window.confirm(button.dataset.confirm)) {
        event.preventDefault();
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = 'true';

    if (button) {
        button.dataset.originalLabel = button.textContent;
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Memproses…';
    }
});

// Pulihkan tombol bila halaman kembali dari bfcache (tombol Back browser).
window.addEventListener('pageshow', function () {
    document.querySelectorAll('form[data-submitting]').forEach(function (form) {
        delete form.dataset.submitting;
    });

    document.querySelectorAll('button[data-original-label]').forEach(function (button) {
        button.textContent = button.dataset.originalLabel;
        button.removeAttribute('aria-busy');
        delete button.dataset.originalLabel;
    });
});

// Salin teks (mis. template WA) tanpa script inline.
document.addEventListener('click', function (event) {
    var button = event.target instanceof Element ? event.target.closest('[data-copy-target]') : null;
    if (!button) {
        return;
    }

    var source = document.getElementById(button.dataset.copyTarget);
    var status = button.dataset.copyStatus ? document.getElementById(button.dataset.copyStatus) : null;
    if (!source) {
        return;
    }

    var text = 'value' in source ? source.value : source.textContent;
    var report = function (message) {
        if (status) {
            status.textContent = message;
        }
    };

    var fallback = function () {
        if (typeof source.select === 'function') {
            source.select();
            report(document.execCommand('copy') ? 'Teks tersalin.' : 'Gagal menyalin; salin manual dari kotak teks.');
        } else {
            report('Gagal menyalin; salin manual dari kotak teks.');
        }
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function () {
            report('Teks tersalin.');
        }, fallback);
    } else {
        fallback();
    }
});
