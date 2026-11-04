# ADR 0006 — Isi repository dan data uji

- Status: Diterima (4 November 2026)

## Konteks

Folder `reference-simapel/` berisi `.env`, `vendor`, log, dan dokumen SOP resmi yang mencantumkan identitas pejabat. Repository akan dihosting di GitHub (private).

## Keputusan

- `reference-simapel/` diabaikan git dan hanya tersedia lokal sebagai acuan.
- Semua data uji sintetis. NIP uji diawali `9999`, dan tidak ada kredensial bawaan.
- Riwayat git dipindai gitleaks di CI.

## Konsekuensi

- Anggota tim memperoleh salinan referensi dan dokumen SOP lewat kanal internal instansi.
- Transkripsi SOP yang dibutuhkan pengembangan disimpan di `docs/proses-bisnis/`.
