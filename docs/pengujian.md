# Pengujian

## Piramida pengujian

| Suite | Lokasi | Basis | Fokus |
|---|---|---|---|
| Unit | `tests/Unit` | `PHPUnit\Framework\TestCase` (tanpa Laravel) | Domain & Application dengan fake di `tests/Support/Fakes` |
| Architecture | `tests/Architecture` | PHPUnit murni | Aturan dependensi antar-layer |
| Feature | `tests/Feature` | `Tests\TestCase` + `RefreshDatabase` | HTTP, CLI, persistensi, keamanan, seeder |
| UI (E2E) | `e2e/tests` | Playwright di Docker | Alur pengguna nyata di browser |

Aturan proyek:
1. **Setiap perubahan disertai unit test.** Logika baru di Domain/Application wajib punya unit test; perilaku HTTP/CLI yang berubah wajib punya feature test.
2. **Coverage minimal 80% (baris)** ditegakkan oleh `scripts/coverage-check.php` di CI. Kondisi saat ini: 99%.
3. **Uji UI hanya untuk area yang terdampak**, kecuali diminta lain (lihat bagian di bawah).

## Menjalankan

```sh
make test           # semua suite PHPUnit
make test-unit      # cepat, tanpa database
make test-arch      # aturan layer
make coverage       # dengan PCOV + ambang 80%
make e2e            # semua uji UI
make e2e-affected   # uji UI untuk area yang terdampak saja
```

Feature test memakai MySQL yang sama dengan production, tetapi pada database terpisah `simapel_testing`. `tests/TestCase.php` menolak menjalankan tes bila nama database tidak berakhiran `_testing`, sebelum `RefreshDatabase` sempat mengosongkan apa pun.

## Uji UI dengan Playwright di Docker

Playwright berjalan di container resmi `mcr.microsoft.com/playwright` (profil compose `e2e`) dan mengakses aplikasi lewat service `web`. Browser tidak perlu dipasang di host.

Setiap eksekusi `make e2e*` menjalankan langkah berikut:
1. Membuat kata sandi acak untuk akun E2E. Kata sandi ini tidak pernah disimpan ke file.
2. Menjalankan `E2eSeeder` (idempoten, ditolak di production). Seeder membuat empat akun sintetis: Super Admin (`…0001`), akun nonaktif (`…0002`), akun wajib ganti kata sandi (`…0003`), dan pegawai biasa (`…0004`). Data yang dibuat spec (pegawai, keanggotaan) memakai NIP sintetis unik per eksekusi sehingga uji dapat diulang pada database pengembangan.
3. Menjalankan Playwright. Login dilakukan sekali (`tests/auth.setup.ts`) lalu sesinya dipakai bersama melalui *storage state*.

### Tag area UI

| Tag | Spec | Cakupan |
|---|---|---|
| `@auth` | `auth.spec.ts` | Halaman masuk, kredensial salah, akun nonaktif, keluar |
| `@account` | `account.spec.ts` | Wajib ganti kata sandi awal, kebijakan kata sandi |
| `@dashboard` | `dashboard.spec.ts` | Ringkasan akun, NIP tersamar |
| `@layout` | `layout.spec.ts` | Navigasi aktif, tautan lewati konten, tampilan ponsel |
| `@security` | `security.spec.ts` | Header keamanan, nol pelanggaran CSP, no-store |
| `@errors` | `errors.spec.ts` | Halaman galat bertema |
| `@pegawai` | `pegawai.spec.ts` | Daftar pegawai (NIP tersamar), tambah pegawai, impor CSV, akses ditolak untuk pegawai biasa |
| `@tim-kerja` | `tim-kerja.spec.ts` | Kelola anggota tim (tambah, ubah peran, keluarkan), tampilan baca saja untuk pegawai |
| `@pengaturan` | `pengaturan.spec.ts` | Link panduan tim, penolakan link non-http(s), siklus draf ketentuan SOP (buat, validasi, ubah, hapus), badge peringatan, 403 untuk pegawai biasa |
| `@setup` | `auth.setup.ts` | Selalu ikut dijalankan |

### Uji UI terdampak saja

`make e2e-affected` membandingkan perubahan terhadap `BASE_REF` (default `origin/main`, atau `main` bila belum ada remote), termasuk file yang belum di-commit. Pemetaan dilakukan oleh `e2e/impact-map.json`:
- **path tertentu** dipetakan ke tag (mis. `resources/views/auth/**` → `@auth`);
- **file global** (layout, CSS/JS, middleware, provider, route, migrasi, Docker) menjalankan semua uji;
- **spec yang berubah** menjalankan spec itu sendiri;
- **kode aplikasi yang belum dipetakan** menjalankan semua uji dan dilaporkan, sebagai pengingat untuk memperbarui peta;
- **dokumentasi, tes PHP, dan workflow CI** tidak memicu uji UI.

Resolver ini punya unit test sendiri: `docker compose --profile e2e run --rm playwright node --test scripts/`.

Di CI, PR menjalankan uji UI terdampak, sedangkan push ke `main` dan `workflow_dispatch` dengan `e2e_scope=all` menjalankan semua uji.

### Laporan

Laporan HTML ada di `e2e/playwright-report/index.html`. Trace dan screenshot disimpan hanya untuk tes yang gagal, di `e2e/test-results/`.

## Alternatif yang dipertimbangkan

| Opsi | Kelebihan | Alasan tidak dipilih (saat ini) |
|---|---|---|
| Laravel Dusk | PHP, satu bahasa dengan backend | Butuh ChromeDriver/Selenium; tooling (trace viewer, codegen) lebih terbatas |
| Pest 4 browser testing | PHP, berbasis Playwright | Tim referensi memakai PHPUnit; menambah framework uji baru |
| Playwright di host | Tanpa Docker | Versi browser berbeda antar-mesin; tidak sama dengan CI |
