// SIMAPEL — perilaku formulir global (tanpa dependensi, kompatibel dengan CSP 'self').
//  - data-confirm="..." pada tombol submit: minta konfirmasi sebelum mengirim.
//  - Cegah kirim ganda dan tampilkan status "Memproses…" pada tombol.
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
