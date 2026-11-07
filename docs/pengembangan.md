# Pengembangan

## Lingkungan Docker

| Service | Image | Port host | Fungsi |
|---|---|---|---|
| `app` | `simapel-app:dev` (PHP 8.5-FPM, Composer, PCOV) | — | Aplikasi; kode di-mount dari host |
| `web` | `nginx:1.30-alpine` | `127.0.0.1:8080` | Web server |
| `db` | `mysql:8.4` | `127.0.0.1:3307` | Database `simapel` dan `simapel_testing` |
| `adminer` (profil `tools`) | `adminer:6.3.0-standalone` | `127.0.0.1:8081` | Penelusuran database (opsional) |
| `playwright` (profil `e2e`) | `mcr.microsoft.com/playwright:v1.65.0-noble` | — | Uji UI |

Semua port hanya terikat ke `127.0.0.1` sehingga tidak terbuka ke jaringan kantor. Port bisa diubah lewat `APP_PORT`, `FORWARD_DB_PORT`, dan `ADMINER_PORT` di `.env`.

## Pertama kali

```sh
make setup
make user
```

`make setup` menjalankan langkah berikut:
1. Membuat `.env` dari `.env.example` dengan kata sandi database acak dan izin file `600`, tanpa menimpa `.env` yang sudah ada.
2. Build image, menjalankan `composer install`, membuat `APP_KEY` bila kosong, migrasi, lalu `db:seed` (draf ketentuan SOP 2026, idempoten, tanpa akun).

Tidak ada akun maupun kata sandi bawaan. Akun pertama dibuat lewat `make user` (`php artisan simapel:user:create`).

Tanpa `make`:

```sh
./scripts/setup-env.sh
docker compose build
docker compose up -d --wait
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan simapel:user:create
```

## Membuat akun lewat CLI

```sh
docker compose exec app php artisan simapel:user:create \
  --nip=999900000000000001 --name="Nama Lengkap" --role=super_admin
```

Kata sandi selalu ditanyakan secara tersembunyi, tidak pernah lewat opsi, agar tidak tersimpan di riwayat shell. Secara default akun wajib mengganti kata sandi saat login pertama. Gunakan `--permanent-password` untuk akun yang kata sandinya ditentukan sendiri oleh pemiliknya.

## Konfigurasi penting (`.env`)

| Variabel | Default | Catatan |
|---|---|---|
| `APP_DEBUG` | `true` | **Wajib `false` di production** |
| `SESSION_LIFETIME` | `60` | Menit tanpa aktivitas sebelum sesi berakhir |
| `SESSION_EXPIRE_ON_CLOSE` | `true` | Sesi berakhir saat browser ditutup |
| `SESSION_SECURE_COOKIE` | `false` | **Wajib `true` di production (HTTPS)** |
| `SIMAPEL_LOGIN_MAX_ATTEMPTS` / `_DECAY_SECONDS` | `5` / `300` | Batas percobaan login per NIP+IP |
| `TRUSTED_PROXIES` | kosong | IP reverse proxy kantor, bila ada |

## Pemecahan masalah

| Gejala | Penyebab & solusi |
|---|---|
| `docker-credential-desktop: executable file not found` | Docker CLI dari Homebrew tidak menemukan helper Docker Desktop. Tambahkan `/Applications/Docker.app/Contents/Resources/bin` ke `PATH`. |
| `Isi DB_PASSWORD di .env` saat `docker compose up` | `.env` belum dibuat; jalankan `make setup` atau `./scripts/setup-env.sh`. |
| Port 8080/3307 sudah dipakai | Ubah `APP_PORT`/`FORWARD_DB_PORT` di `.env`, lalu `make restart`. |
| File milik `root` di Linux/WSL2 | Pastikan `HOST_UID`/`HOST_GID` di `.env` sesuai `id -u`/`id -g`, lalu `make build`. |
| Database uji belum ada | Script `docker/mysql/init` hanya berjalan saat volume pertama dibuat. Jalankan `docker compose down -v` (**menghapus data lokal**) lalu `make setup`. |

## Editor

Folder `reference-simapel/` berisi salinan kelas dengan namespace yang sama. Kecualikan folder ini dari indexer agar navigasi kode tidak tertukar, misalnya `intelephense.files.exclude` di VS Code.
