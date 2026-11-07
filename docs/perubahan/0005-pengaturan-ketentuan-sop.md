# 0005 — Pengaturan link dan ketentuan SOP per tim kerja (MVP-1 langkah 2)

- Branch: `feat/pengaturan`
- Tanggal: 7 November 2026
- Acuan: [docs/mvp-1/panduan.md](../mvp-1/panduan.md) langkah 2, keputusan K7, K11, K12, [ADR 0010](../adr/0010-ketentuan-sop-berversi.md)

## Ringkasan

Halaman **Pengaturan** khusus Super Admin, berisi:
- **Link umum:** login Digitalent, registrasi Digitalent, panduan pendaftaran DTS, dan ambang "segera berakhir" (default 7 hari).
- **Link panduan pendaftaran per tim kerja.** Link ini tampil di halaman detail tim.
- **Ketentuan SOP berversi per tim kerja per tahun anggaran:**
  - isi: nomor/tanggal/link SOP, tanggal mulai berlaku, tenggat BAP/SK/ST/administrasi siap (H+n setelah pendaftaran ditutup);
  - status Draf → Berlaku → Diganti;
  - "Salin dari tahun sebelumnya".
- **Badge peringatan** yang menjelaskan masalahnya saat kursor diarahkan:
  - "SOP belum dilampirkan" bila tim tidak punya ketentuan berlaku dengan link SOP;
  - "Ketentuan 20XX belum ditetapkan" (khusus Super Admin) bila sistem memakai ketentuan tahun sebelumnya.
- **Draf 2026** untuk DTA, KDD, dan ASN dari transkripsi SOP. Semuanya berstatus Draf tanpa link SOP dan harus ditinjau lalu diberlakukan oleh Super Admin.

## Isi perubahan

| Lapisan | Perubahan |
|---|---|
| Domain | `Shared\HttpUrl` (http/https, ≤ 2048), `Settings\GlobalSetting`, `Sop\SopProvision`, `AdministrativeDeadlines`, `SopReference`, `SopProvisionHistory`, `SopProvisionResolver`, event audit baru |
| Application | Port `SettingsRepository` dan `SopProvisionRepository`; use case `UpdateGlobalSettings`, `UpdateTeamGuideUrl`, `CreateSopProvisionDraft`, `UpdateSopProvisionDraft`, `DeleteSopProvisionDraft`, `ActivateSopProvision`, `CopySopProvisionFromPreviousYear`, `ResolveSopProvision`; pembaca `GlobalSettings` |
| Infrastructure | `EloquentSettingsRepository`, `EloquentSopProvisionRepository` (kunci baris per tim), model `Setting`, `SopProvisionRecord` |
| Database | Migrasi `create_settings_and_sop_provisions_tables` (tabel `settings`, `sop_provisions`, kolom `work_teams.registration_guide_url`); seeder `SopProvisionDraftSeeder` dipanggil `DatabaseSeeder` |
| Presentation | `SettingsController`, `SopProvisionController`, Form Request, menu ADMINISTRASI › Pengaturan, komponen Blade `<x-sop.missing-badge>` dan `<x-sop.fallback-badge>`, `View\TeamSopStatus`, halaman Tim Kerja menampilkan link SOP, link panduan, dan badge |
| Lingkungan | `make setup` menjalankan `db:seed` setelah migrasi; Playwright memakai satu worker (lihat catatan reviewer) |

## Keputusan implementasi

- **Kunci ketentuan** adalah kode tim + tahun anggaran, bukan baris `work_teams`, agar fallback lintas tahun sederhana (ADR 0010).
- **Pemberlakuan mensyaratkan nomor dan link SOP.** Karena seeder sengaja tidak mengisi link, ketiga tim tetap menampilkan badge "SOP belum dilampirkan" sampai Super Admin melampirkan link dan memberlakukan draf.
- **Revisi di tengah tahun** harus mulai berlaku setelah versi yang sedang berlaku. Versi lama menjadi Diganti dan tetap dipakai resolver untuk tanggal sebelum revisi.
- **Pengunci "sudah dirujuk pelatihan"** sudah dipanggil oleh use case ubah dan hapus. `isReferenced()` mengembalikan `false` sampai relasi pelatihan dibuat di langkah 3.
- **Ambang "segera berakhir"** berasal dari business story, bukan SOP, dan disimpan sebagai pengaturan global.
- **Nilai bertanda "Perlu konfirmasi"** pada draf KDD (BAP H+5, ST H+6) dan ASN (ST H+4) tidak disebut sebagai output di SOP tim tersebut. Nilai ini tetap Draf sampai dikonfirmasi pemilik proyek.

## Cara menguji

```sh
make migrate && docker compose exec -T app php artisan db:seed --force   # lingkungan yang sudah ada
make ci
```

Uji manual:
1. Masuk sebagai Super Admin, lalu buka **ADMINISTRASI › Pengaturan**.
2. Isi link umum dan link panduan tim. Coba `javascript:...`; isian tersebut harus ditolak.
3. Pada **Ketentuan SOP 2026**, ubah draf DTA, isi link SOP, lalu **Berlakukan**. Badge DTA di halaman Tim Kerja hilang.
4. Buat draf v2 DTA dengan tanggal mulai lebih akhir, lalu berlakukan. v1 menjadi **Diganti**.
5. Pilih tahun 2027, lalu **Salin dari 2026**. Draf 2027 terbentuk.
6. Masuk sebagai pegawai biasa. Menu Pengaturan tidak tampil dan `/settings` menghasilkan 403.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 220 tes lulus |
| Coverage baris | 98,29% (ambang 80%) |
| Larastan level 8, Pint | Bersih |
| Playwright | 27/27 lulus, satu worker, ±24 detik (semua area karena route dan migrasi berubah) |
| Resolver area terdampak | 11/11 lulus |

## Area UI terdampak

`@pengaturan` (baru) dan `@tim-kerja`. Semua area ikut diuji karena `routes/web.php`, migrasi, dan provider berubah.

## Keamanan & data pribadi

- Tidak ada data pribadi baru. Tabel `settings` dan `sop_provisions` hanya menyimpan link, nomor SOP, dan angka hari. Kolom `updated_by`/`created_by` merujuk id akun.
- Otorisasi di backend memakai gate `manage-organization` untuk semua route Pengaturan dan Form Request.
- Semua link divalidasi di server: hanya `http`/`https`, maksimal 2048 karakter. Skema `javascript:` dan `data:` ditolak, dan link dibuka dengan `rel="noopener noreferrer"`.
- Badge memakai atribut `title` dan teks `sr-only`, tanpa style, script, atau handler inline (CSP tetap ketat).
- Audit log mencatat setiap perubahan pengaturan (nilai lama dan baru), link panduan tim, serta pembuatan, perubahan, penghapusan, pemberlakuan, dan penggantian ketentuan.

## Catatan untuk reviewer

- **Dependabot #8** (image Playwright v1.65.0) jangan di-merge sendiri. Versi image harus sama dengan `@playwright/test` di `e2e/package.json` (saat ini 1.64.1); naikkan keduanya dalam satu PR.
- **Dependabot #9** (PHPUnit 13.4.6) adalah patch dan aman di-merge setelah CI hijau.
- **Playwright kini berjalan dengan satu worker.** Uji Super Admin berbagi satu sesi (`auth.setup.ts`) karena login dibatasi 20 kali per menit per IP. Saat uji berjalan paralel, pesan flash dan error formulir tertukar antaruji: terlihat di `pegawai.spec.ts` secara lokal dan di `tim-kerja.spec.ts` pada CI run pertama PR ini. Retry di CI juga gagal karena perubahan dari percobaan pertama sudah tersimpan. Dengan satu worker, semua uji tetap selesai sekitar 24 detik (27 uji).
