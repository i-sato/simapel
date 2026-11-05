# ADR 0005 — Playwright di Docker dan uji UI terdampak

- Status: Diterima (4 November 2026)

## Konteks

Pemilik proyek meminta Playwright untuk uji UI/otomasi yang berjalan di Docker. Setiap uji UI cukup mencakup area yang terdampak perubahan agar cepat, kecuali diminta lain.

## Keputusan

- Container resmi `mcr.microsoft.com/playwright` (profil compose `e2e`) dengan versi image dan paket `@playwright/test` yang dipin sama.
- Setiap spec diberi tag area (`@auth`, `@account`, ...). `e2e/impact-map.json` memetakan path sumber ke tag. File global menjalankan semua uji; kode yang belum dipetakan juga menjalankan semua uji (aman secara default).
- Akun uji sintetis dibuat oleh `E2eSeeder` dengan kata sandi acak per eksekusi.

## Konsekuensi

- Setiap modul baru wajib menambah tag dan entri di `impact-map.json`. Bila lupa, semua uji UI dijalankan.
- Alternatif PHP (Laravel Dusk, Pest browser) tetap terbuka bila tim lebih memilih satu bahasa.
