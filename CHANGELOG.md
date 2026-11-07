# Changelog

Semua perubahan penting dicatat di sini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Belum dirilis]

### Dokumentasi

- Dokumentasi teknis lengkap: spesifikasi teknis, ERD dan kamus data, use case, matriks hak akses, alur dan diagram status, panduan operasional, dan glosarium ([docs/perubahan/0018-dokumentasi-teknis.md](docs/perubahan/0018-dokumentasi-teknis.md)).

## [0.1.0] - 2026-11-07

MVP-1: prototipe siap UAT. Langkah 1–8 di [docs/mvp-1/panduan.md](docs/mvp-1/panduan.md).

### Ditambahkan (MVP-1 langkah 8)

- `DemoSeeder` dan `make demo`: data demo sintetis untuk UAT (hanya local/testing; kata sandi acak ditampilkan sekali) ([docs/perubahan/0017-uat-rilis-mvp-1.md](docs/perubahan/0017-uat-rilis-mvp-1.md)).
- Checklist UAT per peran ([docs/mvp-1/uat.md](docs/mvp-1/uat.md)) dan panduan pengguna ([docs/mvp-1/panduan-pengguna.md](docs/mvp-1/panduan-pengguna.md)).
- Tinjauan akhir checklist keamanan dan PDP beserta daftar butir yang belum terpenuhi.

### Diubah (K19)

- Bentrok di Beban Kerja: Ketua dikecualikan; Sekretaris kini dinilai bentrok bila tugasnya beririsan dengan tugas Admin/Panitia di pelatihan lain, tetapi tidak antartugas Sekretaris di timnya sendiri ([docs/perubahan/0016-keputusan-beban-kerja.md](docs/perubahan/0016-keputusan-beban-kerja.md)).

### Ditambahkan (MVP-1 langkah 7)

- Halaman Beban Kerja untuk semua pegawai: matriks pegawai × minggu/bulan dengan intensitas warna dan tanda bentrok, filter rentang dan tim kerja, serta daftar penugasan per pegawai ([docs/perubahan/0015-beban-kerja.md](docs/perubahan/0015-beban-kerja.md)).

### Diperbaiki

- Teks pembaca layar di dalam tabel tidak lagi melebarkan halaman di ponsel (`.table-wrap` menjadi acuan posisi).

### Ditambahkan (K18)

- Wilayah kerja (DIY, Jawa Tengah, Bali) dan jam penutupan pendaftaran per pelatihan (default 23.59 WIB); template WA menampilkan batas pendaftaran sesuai zona waktu wilayah (WIB/WITA) ([docs/perubahan/0014-mitra-wilayah-kerja.md](docs/perubahan/0014-mitra-wilayah-kerja.md), ADR 0011).
- Data mitra dengan autocomplete di form pelatihan; wilayah mitra otomatis menjadi wilayah pelatihan; mitra dikelola Super Admin, Ketua, dan pegawai bertanda Admin.

### Diubah

- Teks "Wilayah layanan" menjadi "Wilayah kerja"; isian "Mitra/lokasi" menjadi "Lokasi".

### Ditambahkan (MVP-1 langkah 6)

- Dokumen administrasi BAP/SK/ST yang dibuat otomatis saat pelatihan difinalisasi: status Belum/Dalam proses/Selesai, nomor, tanggal, link berkas, dan delegasi dari Ketua ke Sekretaris tim ([docs/perubahan/0013-administrasi-peringatan.md](docs/perubahan/0013-administrasi-peringatan.md)).
- Peringatan per pelatihan berbasis salinan ketentuan SOP: mendekati/lewat tenggat, administrasi kritis, tenggat belum ditetapkan, link peserta kosong, segera berakhir, dan bentrok penugasan; tampil di daftar, detail, dan dashboard.
- Dashboard: kartu ringkasan, "Perlu tindakan saya", dan ringkasan pelatihan berjalan untuk semua pegawai.
- Keputusan peringatan K17: "segera berakhir" hanya saat berlangsung, "tenggat hari ini" berlaku 00.00–23.59 WIB, "kritis" mulai sehari setelah administrasi siap.

### Dokumentasi

- Keputusan Template WA K16: Super Admin ikut mengakses, "gratis" di 5 baris berisi terakhir, dan link panduan pelatihan tim kerja sebagai panduan wajib ([docs/perubahan/0012-keputusan-template-wa.md](docs/perubahan/0012-keputusan-template-wa.md)).

### Ditambahkan (MVP-1 langkah 5)

- Template WA per pelatihan: template default yang dibentuk dari data pelatihan terkini (struktur contoh VBL, info kosong "(menyusul)", learning path, pernyataan gratis), tombol Salin, dan tautan "Buka di WhatsApp" ([docs/perubahan/0011-template-wa.md](docs/perubahan/0011-template-wa.md)).
- Template kustom maks. 3 per anggota per pelatihan dengan placeholder daftar putih, elemen wajib, reset ke default, dan penggabungan template identik.
- Akses Template WA untuk Panitia, Ketua tim terkait, Admin yang ditugaskan, dan Super Admin.

### Keamanan

- Isi template tidak pernah diproses sebagai Blade/PHP; risiko injeksi template dan mitigasinya didokumentasikan di `docs/keamanan.md`.

### Dokumentasi

- Keputusan penugasan K15: Admin ditetapkan ulang per pelatihan tanpa centang otomatis, Admin boleh sekaligus Panitia, dan perubahan tanggal tidak meminta alasan bentrok baru ([docs/perubahan/0010-keputusan-penugasan.md](docs/perubahan/0010-keputusan-penugasan.md)).

### Ditambahkan (MVP-1 langkah 4)

- Penugasan Admin/Panitia per pelatihan: daftar centang pegawai dengan badge peran tim, "Admin", dan bentrok ([docs/perubahan/0009-penugasan.md](docs/perubahan/0009-penugasan.md)).
- Deteksi bentrok beban kerja lintas tim kerja (awal pendaftaran sampai akhir pelatihan, inklusif); penugasan yang bentrok wajib disertai alasan force yang tercatat di audit log.
- Admin yang ditugaskan dapat memfinalisasi Draft dan melengkapi pelatihan, termasuk link daftar peserta.

### Diubah (keputusan langkah 3)

- Ketua tim kerja terkait dan Admin yang ditunjuk dapat melengkapi pelatihan Final; finalisasi tetap oleh Super Admin dan Admin yang ditunjuk ([docs/perubahan/0008-melengkapi-pelatihan-final.md](docs/perubahan/0008-melengkapi-pelatihan-final.md), K13).
- Keputusan field wajib finalisasi, batch berupa angka, dan akses link daftar peserta dicatat (K13, K14).

### Ditambahkan (MVP-1 langkah 3)

- Menu Pelatihan: daftar dengan filter tim/status/periode, form draft per bagian, detail, dan finalisasi dengan konfirmasi ([docs/perubahan/0007-pelatihan.md](docs/perubahan/0007-pelatihan.md), ADR 0009).
- Status Draft → Final dan status waktu yang diturunkan dari tanggal (Akan dibuka, Pendaftaran, Penetapan peserta, Pelaksanaan, Selesai).
- Salinan ketentuan SOP tim kerja saat finalisasi, peringatan jeda persiapan terhadap "administrasi siap", dan kunci ketentuan yang sudah dipakai pelatihan.
- Learning path per tim kerja (Level 1 Dasar, 2 Menengah, 3 Mahir; satu level satu pelatihan per path).
- Hak akses pelatihan: Ketua tim terkait dan Super Admin menyusun draft, Super Admin memfinalisasi, semua pegawai melihat.

### Diperbaiki

- Uji UI kembali berjalan setelah image Playwright naik ke v1.65.0: `@playwright/test` disamakan ke 1.65.0, pemeriksa versi ditambahkan di `make e2e*` dan CI, dan aturan Dependabot untuk Playwright diperbaiki ([docs/perubahan/0006-playwright-1-65.md](docs/perubahan/0006-playwright-1-65.md)).

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
