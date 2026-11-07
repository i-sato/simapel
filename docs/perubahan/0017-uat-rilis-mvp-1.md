# 0017 — UAT dan rilis MVP-1 (langkah 8, versi 0.1.0)

- Branch: `chore/uat-mvp-1`
- Tanggal: 7 November 2026
- Acuan: [docs/mvp-1/panduan.md](../mvp-1/panduan.md) langkah 8

## Ringkasan

- **`DemoSeeder`** dan **`make demo`**: data demo **sintetis** untuk UAT.
  - Seeder ditolak di production dan ditolak bila data demo sudah ada. `make demo` mengosongkan database **lokal** terlebih dahulu.
  - Isi data:
    - 20 pegawai bernomor NIP 9999…, satu di antaranya nonaktif;
    - tiga tim kerja dengan Ketua, Sekretaris, dan Anggota;
    - ketentuan SOP Berlaku per tim, dengan revisi tengah tahun DTA (1 Juli);
    - dua mitra (Bali dan Jawa Tengah);
    - 9 pelatihan: 6 Final dan 3 Draft, dengan status waktu Selesai, Pelaksanaan, Penetapan peserta, Pendaftaran, dan Akan dibuka;
    - satu learning path tiga level;
    - bentrok penugasan yang disertai alasan;
    - dokumen BAP/SK/ST berstatus Belum, Dalam proses, dan Selesai.
  - Semua data dibuat lewat use case aplikasi, sehingga konsisten dengan aturan bisnis. Tanggal relatif terhadap hari ini.
  - Kata sandi demo dibuat acak dan ditampilkan sekali di terminal.
- **[docs/mvp-1/uat.md](../mvp-1/uat.md):** checklist UAT per peran (Super Admin, Ketua, Sekretaris, Admin, Panitia, Pegawai), dengan akun demo dan hasil yang diharapkan.
- **[docs/mvp-1/panduan-pengguna.md](../mvp-1/panduan-pengguna.md):** panduan singkat per peran dalam bahasa non-teknis, beserta arti badge peringatan.
- **Uji UI penuh** (`make e2e`): 40 lulus, 1 dilewati, tanpa kegagalan. Yang dilewati adalah uji badge "SOP belum dilampirkan", yang memang hanya dijalankan bila ada tim tanpa ketentuan Berlaku.
- **Rilis 0.1.0** dicatat di `CHANGELOG.md`. Tag `v0.1.0` dibuat setelah PR ini di-merge dan disetujui pemilik proyek.

## Tinjauan keamanan dan PDP

Pemeriksaan yang dijalankan:
- `make audit`: Composer dan npm tanpa kerentanan;
- `make ci`: lint, Larastan level 8, coverage, dan uji UI;
- view tanpa `style=""`, `on*=""`, atau `<script>` inline;
- tidak ada `.env` maupun `reference-simapel/` di repository.

### Butir yang belum terpenuhi

| Butir | Sumber | Status | Catatan |
|---|---|---|---|
| Pengerasan production (`APP_DEBUG=false`, HTTPS + `SESSION_SECURE_COOKIE=true`, user DB tanpa DDL, `TRUSTED_PROXIES`, backup terenkripsi di Indonesia) | keamanan.md | Belum | Menunggu informasi server; `.env.example` masih bernilai lokal (`APP_DEBUG=true`, `SESSION_SECURE_COOKIE=false`) |
| Pemantauan log keamanan dan alarm (mis. lonjakan `auth.login_throttled`) | keamanan.md | Belum | Belum ada notifikasi otomatis |
| Penetration test / security review sebelum go-live (koordinasi BSSN/tim keamanan instansi) | keamanan.md | Belum | Disarankan sebelum data nyata dimasukkan |
| MFA untuk Super Admin | keamanan.md | Ditunda (keputusan R6) | Wajib dikaji ulang sebelum go-live |
| Retensi audit log (JRA) | keamanan.md, kepatuhan-pdp.md | Belum | Menunggu JRA instansi |
| Halaman audit log untuk Super Admin | kepatuhan-pdp.md (inventaris) | Belum | Audit tercatat di database, tetapi belum ada tampilannya |
| Dasar pemrosesan resmi data pegawai/peserta | kepatuhan-pdp.md | Perlu konfirmasi | Keputusan hukum/PPDP instansi |
| Teks pemberitahuan privasi final | kepatuhan-pdp.md | Perlu konfirmasi | Teks di halaman masuk masih draf |
| Hak subjek data (ekspor/perbaikan per subjek) | kepatuhan-pdp.md | Direncanakan | Saat ini lewat Super Admin secara manual |
| Penilaian dampak (DPIA) sebelum modul data peserta | kepatuhan-pdp.md | Perlu dilakukan | MVP-1 belum menyimpan data peserta; hanya link Sheet |
| Prosedur insiden dan notifikasi 3×24 jam | kepatuhan-pdp.md | Perlu disusun | — |
| Penunjukan PPDP | kepatuhan-pdp.md | Perlu konfirmasi | — |
| Akses link daftar peserta (Google Sheet) | K14 | Di luar SIMAPEL | Hak akses diatur pemilik berkas di platform penyedia; Sheet contoh di problem statement masih terbuka untuk pemegang tautan |

Butir yang **sudah terpenuhi**:
- otorisasi di backend (gate + use case);
- CSP ketat;
- validasi server;
- NIP tersamar;
- audit perubahan data;
- data uji sintetis;
- tidak ada transfer data ke luar negeri;
- pemindaian rahasia (gitleaks) di CI.

## Isi perubahan

| File | Perubahan |
|---|---|
| `database/seeders/DemoSeeder.php`, `Makefile` (`make demo`) | Data demo sintetis |
| `tests/Feature/Database/DemoSeederTest.php` | Konsistensi data demo, akun demo dapat dipakai, penolakan di production dan saat dijalankan ulang |
| `docs/mvp-1/uat.md`, `docs/mvp-1/panduan-pengguna.md` | Baru |
| `docs/keamanan.md`, `docs/kepatuhan-pdp.md` | Celah yang diketahui diperbarui |
| `docs/serah-terima.md`, `docs/pengembangan.md`, `docs/README.md`, `CHANGELOG.md` | Status MVP-1, `make demo`, rilis 0.1.0 |

## Cara menguji

```sh
make ci
make e2e
make demo     # MENGHAPUS data lokal, lalu ikuti docs/mvp-1/uat.md
```

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 418 tes lulus |
| Coverage baris | 98,67% (ambang 80%) |
| Larastan level 8, Pint, uji arsitektur | Bersih |
| `make e2e` (semua uji UI) | 40 lulus, 1 dilewati (bersyarat) |
| `make audit` | Tanpa kerentanan |

## Area UI terdampak

Tidak ada perubahan UI. Seluruh uji UI tetap dijalankan sesuai kebutuhan langkah 8.

## Keamanan & data pribadi

- `DemoSeeder` hanya berjalan di `local`/`testing` dan hanya membuat data sintetis (NIP 9999…, nama dan mitra fiktif, link `example.test`).
- Kata sandi demo dibuat acak per eksekusi, tidak disimpan ke berkas, dan hanya ditampilkan sekali di terminal.
