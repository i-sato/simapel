# Changelog

Semua perubahan penting dicatat di sini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Belum dirilis]

### Ditambahkan (MVP-1 langkah 2)

- Halaman Pengaturan khusus Super Admin: link login/registrasi Digitalent, link panduan pendaftaran DTS, ambang "segera berakhir" (default 7 hari), dan link panduan pendaftaran per tim kerja ([docs/perubahan/0005-pengaturan-ketentuan-sop.md](docs/perubahan/0005-pengaturan-ketentuan-sop.md)).
- Ketentuan SOP berversi per tim kerja per tahun anggaran: status Draf → Berlaku → Diganti, revisi di tengah tahun, salin dari tahun sebelumnya, dan resolver dengan fallback ke tahun sebelumnya (ADR 0010).
- Badge peringatan "SOP belum dilampirkan" dan "Ketentuan 20XX belum ditetapkan" di halaman Pengaturan dan Tim Kerja.
- Seeder draf ketentuan 2026 untuk DTA, KDD, dan ASN dari transkripsi SOP. Draf tidak diberlakukan otomatis, dan nilai yang belum pasti ditandai "Perlu konfirmasi".

### Diubah (MVP-1 langkah 2)

- `make setup` menjalankan `db:seed` setelah migrasi.
- Playwright berjalan dengan satu worker agar uji yang berbagi sesi Super Admin tidak saling bertukar pesan flash.

### Ditambahkan (MVP-1 langkah 1)

- Model peran berlapis (ADR 0008): peran global Super Admin/Pegawai, tim kerja KDD/ASN/DTA per tahun dengan Ketua/Sekretaris/Anggota, kapabilitas Admin pada pegawai ([docs/perubahan/0004-peran-pegawai-tim.md](docs/perubahan/0004-peran-pegawai-tim.md)).
- Halaman Pegawai untuk Super Admin: daftar dengan pencarian dan NIP tersamar, tambah/ubah, aktifkan/nonaktifkan (sesi langsung diakhiri), reset kata sandi sementara, impor CSV semua-atau-tidak.
- Halaman Tim Kerja yang dapat dilihat semua pegawai; Super Admin mengatur anggota dan perannya.
- Data tiga tim kerja 2026 lewat migrasi.

### Diubah

- Peran `super_admin_regional` menjadi `super_admin`; `admin_regional` dihapus dan dipetakan menjadi `pegawai` dengan kapabilitas Admin.
- Image MySQL lokal dikembalikan ke 8.4 LTS (sesuai ADR 0002 dan CI) setelah pembaruan Dependabot ke 26.7; Dependabot tidak lagi mengusulkan seri mayor MySQL.

### Dokumentasi

- Ketentuan tenggat administratif berubah menjadi ketentuan SOP berversi per tim kerja per tahun anggaran, dengan salinan pada pelatihan ([docs/perubahan/0003-ketentuan-sop-berversi.md](docs/perubahan/0003-ketentuan-sop-berversi.md)).
- Panduan langkah MVP-1 beserta keputusan pemilik proyek dan prompt per langkah ([docs/mvp-1/panduan.md](docs/mvp-1/panduan.md)).

### Ditambahkan

- Starter project refactor SIMAPEL di atas Laravel 13 (PHP 8.5) dan MySQL 8.4 LTS ([docs/perubahan/0001-starter-project.md](docs/perubahan/0001-starter-project.md)).
- Clean architecture (Domain, Application, Infrastructure, Presentation) beserta uji arsitektur yang menegakkan arah dependensi.
- Login dengan NIP 18 digit, pembatasan percobaan per NIP+IP, pencabutan sesi akun nonaktif, kebijakan kata sandi, wajib ganti kata sandi awal, dan pengakhiran sesi lain setelah ganti kata sandi.
- Jejak audit append-only untuk login, logout, sesi dicabut, ganti kata sandi, dan pembuatan akun, dengan NIP tersamar.
- Perintah `simapel:user:create` untuk membuat akun tanpa kredensial bawaan.
- Tema gelap referensi dalam `public/css/simapel.css`, halaman galat bertema, navigasi berbasis peran, dan tautan lewati konten.
- Header keamanan dan Content Security Policy ketat tanpa `unsafe-inline`.
- Lingkungan Docker (PHP-FPM, nginx, MySQL, Adminer, Playwright) dan Makefile.
- Uji UI Playwright di Docker dengan pemilihan area terdampak (`e2e/impact-map.json`).
- CI GitHub Actions (lint, Larastan level 8, audit dependensi, PHPUnit + coverage ≥ 80%, Playwright, gitleaks, build image), workflow rilis image, dan Dependabot.
- Dokumentasi serah terima, analisis proses bisnis tiga SOP, ADR 0001–0007, aturan agent (`AGENTS.md`), dan usulan aturan tambahan.

### Catatan

- Model peran sementara: hanya Super Admin yang menjadi prioritas; peran lain disesuaikan setelah data dukung diterima. MFA (R6) ditunda untuk MVP 1.
