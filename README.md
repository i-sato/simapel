# SIMAPEL

Sistem Informasi Pengelolaan Pelatihan — BLSDM Komdigi Yogyakarta (wilayah layanan DIY, Jawa Tengah, dan Bali).

SIMAPEL adalah sistem pendukung administrasi dan pemantauan pelatihan regional berdasarkan tiga SOP resmi. Sistem ini tidak menggantikan DTS/Digitalent pusat. Repository ini adalah hasil refactor dari aplikasi referensi (`reference-simapel/`, hanya tersedia lokal) dengan teknologi yang sama: Laravel, Blade, dan MySQL.

| Komponen | Versi | Alasan |
|---|---|---|
| Laravel | 13.x | Rilis terbaru; perbaikan keamanan hingga 17 Maret 2028 |
| PHP | 8.5 (minimal 8.4) | Dukungan keamanan hingga 31 Desember 2029 |
| MySQL | 8.4 LTS | Teknologi sama dengan referensi; seri 8.0 sudah EOL |
| Frontend | Blade + CSS/JS statis | Tema gelap referensi dipertahankan; tanpa langkah build |
| Uji UI | Playwright 1.64 di Docker | Lihat [docs/pengujian.md](docs/pengujian.md) |

## Mulai cepat (Docker)

Prasyarat: Docker Desktop (atau Docker Engine + Compose v2), `make`, dan `git`. Pengguna Windows disarankan memakai WSL2.

```sh
make setup   # .env dengan kata sandi acak, build image, composer install, APP_KEY, migrasi
make user    # buat akun pertama (Super Admin Regional), kata sandi diminta tersembunyi
```

Buka <http://localhost:8080> lalu masuk dengan NIP dan kata sandi akun tersebut.

| Perintah | Fungsi |
|---|---|
| `make up` / `make down` | Menjalankan / menghentikan service (data database tetap tersimpan) |
| `make test` | Semua PHPUnit (Unit, Architecture, Feature) |
| `make coverage` | PHPUnit + coverage, gagal bila di bawah 80% |
| `make lint` / `make fix` | Cek / rapikan gaya kode (Pint) |
| `make stan` | Analisis statis Larastan level 8 |
| `make e2e` | Semua uji UI Playwright |
| `make e2e-affected` | Uji UI hanya untuk area yang terdampak perubahan |
| `make ci` | Rangkaian pemeriksaan seperti CI |
| `make tools` | Adminer di <http://localhost:8081> |

Daftar lengkap tersedia lewat `make help`. Untuk perintah tanpa `make`, lihat [docs/pengembangan.md](docs/pengembangan.md).

## Struktur

```
app/
  Domain/          aturan bisnis murni (tanpa framework)
  Application/     use case + port (interface)
  Infrastructure/  implementasi port (Eloquent, hashing, sesi, waktu)
  Models/          model Eloquent (persistensi)
  Http/ View/ Console/   presentasi (controller, request, middleware, view composer, CLI)
docker/            Dockerfile (dev/prod), nginx, inisialisasi MySQL
e2e/               Playwright (spec, peta area terdampak, script)
docs/              dokumentasi teknis, keamanan, kepatuhan, proses bisnis, ADR
tests/             Unit, Architecture, Feature (PHPUnit)
```

## Dokumentasi

Mulai dari [docs/README.md](docs/README.md). Aturan kerja untuk developer dan AI agent ada di [AGENTS.md](AGENTS.md).

## Status

Starter project berisi fondasi berikut:
- clean architecture yang ditegakkan oleh uji arsitektur;
- login dengan NIP, wajib ganti kata sandi awal, jejak audit, dan header keamanan ketat;
- tema UI referensi;
- Docker, Playwright, dan CI.

Modul operasional (SOP, pelatihan, tugas, peserta) menunggu data dukung dan konfirmasi proses bisnis. Lihat [docs/serah-terima.md](docs/serah-terima.md).
