# ADR 0001 — Laravel 13 dan PHP 8.5

- Status: Diterima (4 November 2026)

## Konteks

Aplikasi referensi memakai Laravel 8.83 dan PHP 8.2. Laravel 8 sudah tidak menerima perbaikan keamanan sejak Januari 2023, dan dukungan keamanan PHP 8.2 berakhir 31 Desember 2026. Pemilik proyek meminta versi terbaik dengan pertimbangan keamanan, dengan stack yang tetap sama (Laravel, Blade, MySQL).

## Keputusan

- Laravel 13.x: rilis 17 Maret 2026, perbaikan keamanan hingga 17 Maret 2028.
- PHP 8.5 di Docker (dukungan keamanan hingga 31 Desember 2029), dengan batas minimum `^8.4` di `composer.json` untuk lingkungan yang belum bisa memakai 8.5.
- Skeleton resmi `laravel/laravel` tanpa starter kit. Autentikasi NIP ditulis sendiri seperti di referensi, dengan memanfaatkan fitur keamanan bawaan Laravel 13 (Timebox pada `Auth::attempt`, `PreventRequestForgery`).

## Konsekuensi

- Upgrade mayor berikutnya (Laravel 14) perlu dijadwalkan sebelum Maret 2028.
- Kode referensi tidak dapat disalin mentah. Pola dan aturan bisnisnya dipindahkan ke struktur baru.
