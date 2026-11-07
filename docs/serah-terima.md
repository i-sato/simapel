# Serah terima

Dokumen ini adalah titik awal bagi siapa pun yang melanjutkan proyek. Perbarui di setiap PR yang mengubah status.

## Status saat ini (4 November 2026)

| Area | Status |
|---|---|
| Fondasi teknis (Laravel 13, PHP 8.5, MySQL 8.4, Docker) | Selesai |
| Clean architecture + uji arsitektur | Selesai |
| Autentikasi NIP, keamanan akun, audit login | Selesai |
| Tema UI referensi, layout, halaman galat | Selesai |
| Pengujian: 277 PHPUnit, coverage 98%, 30 uji UI Playwright, 12 uji resolver | Selesai |
| CI/CD GitHub Actions (CI + rilis image) | Siap; berjalan setelah repository remote dibuat |
| Pegawai, tim kerja, model peran berlapis (langkah 1) | Selesai (PR `feat/peran-pegawai-tim`, ADR 0008) |
| Pengaturan link dan ketentuan SOP berversi (langkah 2) | Selesai (PR `feat/pengaturan`, ADR 0010); draf 2026 menunggu tinjauan Super Admin |
| Pelatihan Draft → Final dan learning path (langkah 3) | Selesai (PR `feat/pelatihan`, ADR 0009) |
| Penugasan, template WA, administrasi, beban kerja | Belum: langkah 4–8 di [mvp-1/panduan.md](mvp-1/panduan.md) |
| SOP Builder dan alur tahap SOP | Ditunda dari MVP-1 |
| Assessment & sertifikat | Ditunda (ADR 0007) |
| Continuous Delivery (deploy) | Menunggu informasi server |

## Keputusan pemilik proyek

Tanggal keputusan: 4 November 2026.

1. Laravel memakai versi terbaik dari sisi keamanan, yaitu Laravel 13 dengan PHP 8.5 (ADR 0001).
2. Teknologi database sama dengan referensi, yaitu MySQL (8.4 LTS, ADR 0002). Skema dan migrasi data diputuskan setelah data dukung diterima.
3. Root project berada di folder `simapel/` dengan acuan refactor di `reference-simapel/`. Proyek disiapkan sebagai GitHub project, dan setiap perubahan disiapkan sebagai Pull Request.
4. Assessment dan sertifikat diabaikan dulu (ADR 0007).
5. Aturan tambahan:
   - Playwright di Docker untuk uji UI;
   - unit test di setiap perubahan;
   - uji UI hanya area terdampak;
   - dokumentasi di setiap PR;
   - clean architecture;
   - keamanan OWASP dan kepatuhan UU PDP;
   - CI/CD di GitHub private.
6. MFA (usulan R6) ditunda karena target saat ini adalah MVP 1 berbentuk prototipe.
7. Model peran berlapis diterapkan di langkah 1 (ADR 0008): peran global Super Admin/Pegawai, peran tim Ketua/Sekretaris/Anggota, kapabilitas Admin, dan penugasan per pelatihan.
8. Ketentuan SOP berversi per tim kerja per tahun anggaran diterapkan di langkah 2 (ADR 0010). Pelatihan menyimpan salinannya saat difinalisasi (langkah 3, ADR 0009).

## Cara melanjutkan

1. Baca `AGENTS.md`, lalu [arsitektur.md](arsitektur.md) dan [pengujian.md](pengujian.md).
2. Jalankan `make setup`, `make user`, lalu `make ci` untuk memastikan lingkungan sehat.
3. Buat branch dari `main`, kerjakan satu modul, dan ikuti Definition of Done di `AGENTS.md`.
4. Tambahkan catatan di `docs/perubahan/` dan entri `CHANGELOG.md`, lalu buka PR.

## Rencana MVP-1

Problem statement pemilik proyek ("Simapel Business Story" + "Additional Features V.1" dan "V.2") dan keputusan 7 November 2026 menggantikan usulan urutan modul sebelumnya. Langkah, kriteria selesai, dan prompt per langkah ada di [mvp-1/panduan.md](mvp-1/panduan.md).

Ringkasnya, model organisasi berubah menjadi tim kerja (KDD, ASN Digital, DTA) dengan Ketua/Sekretaris/Anggota. Pusat proses MVP-1 adalah informasi pelatihan (draft → final), penugasan Admin/Panitia dengan peringatan bentrok, template WA, kelengkapan BAP/SK/ST, dan pemetaan beban kerja. Alur tahap SOP dari referensi ditunda; MVP-1 menyimpan ketentuan SOP berversi per tim kerja per tahun anggaran (link SOP dan tenggat administratif), dan setiap pelatihan menyimpan salinan ketentuan saat difinalisasi.

## Pertanyaan terbuka

| Topik | Pertanyaan | Pemilik |
|---|---|---|
| Data | Daftar pegawai dan komposisi tim diinput lewat aplikasi (impor CSV), bukan lewat repository | Pemilik proyek |
| SOP | Tinjau draf ketentuan 2026 di menu Pengaturan: lampirkan link SOP, konfirmasi BAP H+5 dan ST H+6 (KDD) serta ST H+4 (ASN), lalu berlakukan | Pemilik proyek |
| Data peserta | Pembatasan akses Google Sheet peserta yang saat ini terbuka untuk siapa saja yang memiliki tautan | Pemilik proyek |
| Pelatihan | Field wajib finalisasi (link silabus, mitra/lokasi), siapa yang boleh melengkapi pelatihan Final, batch berupa angka | Pemilik proyek |
| PDP | Dasar pemrosesan, PPDP, retensi (JRA), teks pemberitahuan privasi | Hukum/PPDP instansi |
| GitHub | Paket akun (fitur protected branch di repo private butuh Pro/Team) | Pemilik proyek |
| Deploy | Lokasi server (di Indonesia), akses jaringan, HTTPS, backup | Tim infrastruktur |
| Aturan agent | Pilihan dari `agent-rules-usulan.md` | Pemilik proyek |

## Batasan yang diketahui

- MFA belum tersedia (usulan R6, ditunda untuk MVP 1).
- Admin yang ditugaskan per pelatihan belum ada (langkah 4); sampai saat itu hanya Super Admin yang memfinalisasi dan melengkapi pelatihan Final.
- Uji UI berjalan dengan satu worker karena uji Super Admin berbagi satu sesi. Bila jumlah uji membuat waktu tunggu terlalu lama, pertimbangkan sesi admin per worker (perlu menyesuaikan batas login per IP di lingkungan e2e).
- Alarm otomatis untuk event keamanan belum tersedia.
- Ukuran image production sekitar 590 MB (Debian). Optimasi ke Alpine dapat dikaji bila diperlukan.
- `make` membutuhkan lingkungan Unix (macOS, Linux, WSL2). Perintah `docker compose` setara tercantum di [pengembangan.md](pengembangan.md).
