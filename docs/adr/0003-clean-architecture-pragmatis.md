# ADR 0003 — Clean architecture pragmatis

- Status: Diterima (4 November 2026)

## Konteks

Referensi memakai pola controller → service → model. Logika hak akses dan aturan bisnis tersebar di controller dan Blade, dengan beberapa jalur kode ganda. Pemilik proyek meminta clean architecture yang tetap memudahkan transisi tim.

## Keputusan

- Lapisan **Domain** (murni) dan **Application** (use case + port) ditambahkan.
- Konvensi Laravel tetap dipakai untuk lapisan luar: `app/Http`, `app/Models`, `app/Providers`, `routes`, `resources/views`.
- "Service" di referensi dipetakan ke use case. Model Eloquent menjadi bagian Infrastructure.
- Aturan dependensi ditegakkan oleh `tests/Architecture/LayerDependencyTest.php`, bukan hanya oleh konvensi.
- Pembacaan data untuk tampilan boleh langsung lewat Eloquent di controller, sedangkan perubahan data wajib lewat Application.

## Konsekuensi

- Ada lebih banyak file per fitur (DTO, port, fake). Sebagai gantinya, aturan SOP dapat diuji cepat tanpa database.
- Developer baru cukup mempelajari empat folder dan satu aturan arah dependensi (lihat docs/arsitektur.md).
