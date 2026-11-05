# ADR 0004 — Aset statis tanpa build dan CSP ketat

- Status: Diterima (4 November 2026)

## Konteks

UI wajib memakai style dan tema yang sama dengan referensi: tema gelap dengan CSS inline di layout dan JavaScript vanilla. Skeleton Laravel 13 membawa Vite dan Tailwind. CSS/JS inline mencegah penerapan Content Security Policy yang ketat.

## Keputusan

- Token warna dan komponen referensi dipindahkan apa adanya ke `public/css/simapel.css`. Perilaku formulir dipindahkan ke `public/js/simapel.js`.
- Vite, Tailwind, dan `package.json` aplikasi dihapus, sehingga tidak ada langkah build frontend (sama seperti referensi).
- CSP `default-src 'self'` tanpa `unsafe-inline`. Atribut `style=""`, handler `on*=""`, dan `<script>` inline dilarang, dan larangan ini diuji otomatis.
- Cache browser diperbarui lewat versi berbasis waktu modifikasi file (`App\View\Asset`).

## Konsekuensi

- Gaya baru ditambahkan sebagai kelas, bukan atribut `style`.
- Tema terang/gelap otomatis (usulan PRD lama) dapat ditambahkan kemudian lewat token CSS tanpa mengubah markup.
