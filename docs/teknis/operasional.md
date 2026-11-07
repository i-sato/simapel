# Panduan operasional

Panduan ini ditujukan bagi petugas teknis yang menjalankan SIMAPEL: instalasi, akun pertama, pembaruan versi, backup, log, dan pemeriksaan kesehatan. Server production **belum ditentukan** (lihat [ci-cd.md](../ci-cd.md)). Karena itu, langkah production di bawah bersifat umum dan perlu disesuaikan dengan server yang dipilih (wajib berada di Indonesia, PP 71/2019).

## 1. Lingkungan

| Lingkungan | Cara menjalankan | Data |
|---|---|---|
| Pengembangan/UAT lokal | Docker Compose + `make` ([pengembangan.md](../pengembangan.md)) | Data uji atau data demo sintetis (`make demo`) |
| Production | Image `ghcr.io/i-sato/simapel:<versi>` + nginx + MySQL 8.4 | Data nyata; hanya setelah checklist go-live terpenuhi |

## 2. Instalasi lokal

```sh
make setup      # .env dengan kata sandi DB acak, build image, dependensi, APP_KEY, migrasi, seeder
make user       # akun pertama (interaktif; kata sandi diminta tersembunyi)
make up         # menjalankan semua layanan; aplikasi di http://localhost:8080
```

Data demo untuk UAT:

```sh
make demo       # MENGHAPUS seluruh data lokal, lalu mengisi data sintetis
```

Kata sandi akun demo ditampilkan **sekali** di terminal. Skenarionya ada di [mvp-1/uat.md](../mvp-1/uat.md).

Perintah harian lain: `make down`, `make logs`, `make shell`, `make migrate`, `make ci`. Daftar lengkap: `make help`.

## 3. Akun pertama dan pemulihan akses

- **Tidak ada akun maupun kata sandi bawaan.** Akun pertama dibuat dari server:

  ```sh
  docker compose exec app php artisan simapel:user:create --role=super_admin
  ```

- **Bila satu-satunya Super Admin lupa kata sandi**, buat Super Admin baru dengan perintah di atas. Setelah itu, reset kata sandi akun lama dari menu Pegawai. Pembuatan akun lewat CLI tercatat di audit dengan kanal `cli`.
- **Minimal satu Super Admin aktif** selalu dipertahankan oleh aplikasi. Super Admin tidak dapat menurunkan peran atau menonaktifkan dirinya sendiri.

## 4. Pembaruan versi

### Lokal

```sh
git pull
make build      # bila Dockerfile/dependensi berubah
make migrate
```

### Production (umum, sesuaikan dengan server)

1. Baca `CHANGELOG.md` dan catatan `docs/perubahan/` untuk versi tujuan, terutama bagian migrasi.
2. **Backup database** (bagian 5) sebelum pembaruan.
3. Tarik image bertag versi, misalnya `ghcr.io/i-sato/simapel:0.1.0`. Jangan memakai `latest` di production.
4. Jalankan migrasi dari container aplikasi versi baru:

   ```sh
   php artisan migrate --force
   ```

5. Opsional, untuk kinerja: `php artisan optimize`, yang meng-cache konfigurasi, route, dan view. Jalankan ulang setiap kali `.env` berubah.
6. Pastikan pemeriksaan kesehatan `GET /up` merespons 200 dan login berjalan.
7. Bila gagal, pulihkan backup dan jalankan image versi sebelumnya.

`DB::prohibitDestructiveCommands()` aktif di production, sehingga perintah seperti `migrate:fresh` ditolak.

## 5. Backup dan pemulihan database

Contoh untuk lingkungan Docker Compose proyek ini (sudah diuji). Untuk production, gunakan pengguna database dan lokasi penyimpanan sesuai kebijakan instansi.

```sh
# Backup (konsisten tanpa mengunci tabel)
docker compose exec -T db sh -c 'exec mysqldump --single-transaction --no-tablespaces \
  --default-character-set=utf8mb4 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  > simapel-$(date +%Y%m%d-%H%M).sql

# Pemulihan (MENIMPA isi database tujuan)
docker compose exec -T db sh -c 'exec mysql --default-character-set=utf8mb4 \
  -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < simapel-YYYYMMDD-HHMM.sql
```

Ketentuan:
- **Berkas backup memuat data pribadi** (nama, NIP, jabatan, audit). Enkripsi berkasnya, simpan di Indonesia, batasi aksesnya, dan jangan pernah memasukkannya ke repository, tiket, atau artefak CI.
- **Uji pemulihan secara berkala** di lingkungan terpisah.
- **Frekuensi dan masa simpan** mengikuti kebijakan instansi (JRA), yang saat ini masih menunggu keputusan.
- **`APP_KEY` ikut menentukan** apakah sesi dan data terenkripsi masih bisa dibaca. Simpan `APP_KEY` production di tempat aman, terpisah dari backup database.

## 6. Log dan audit

| Sumber | Lokasi | Isi |
|---|---|---|
| Log aplikasi | `storage/logs/laravel-YYYY-MM-DD.log` (harian, 30 hari) | Galat aplikasi; level `warning` atau lebih tinggi di production |
| Log container | `docker compose logs <layanan>` | nginx, PHP-FPM, MySQL |
| Audit log | Tabel `audit_logs` (append-only) | Login, perubahan data, penugasan, dokumen, dan lainnya ([katalog](spesifikasi-teknis.md#9-katalog-peristiwa-audit)) |

Halaman audit untuk Super Admin belum tersedia. Contoh query investigasi (jalankan dengan akses baca saja):

```sql
-- Login gagal/dibatasi 24 jam terakhir
SELECT occurred_at, event_type, ip_address, description
FROM audit_logs
WHERE event_type IN ('auth.login_failed', 'auth.login_throttled')
  AND occurred_at >= NOW() - INTERVAL 1 DAY
ORDER BY occurred_at DESC;

-- Riwayat perubahan satu pelatihan
SELECT a.occurred_at, a.event_type, u.name AS pelaku, a.description, a.context
FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_user_id
WHERE a.subject_type = 'training' AND a.subject_id = 123
ORDER BY a.occurred_at;
```

## 7. Pemeriksaan kesehatan

- `GET /up`: endpoint kesehatan Laravel (tanpa login).
- `docker compose ps`: status layanan. `db` memiliki healthcheck `mysqladmin ping`; image production memiliki `HEALTHCHECK php-fpm -t`.
- Tanda masalah yang perlu dipantau:
  - lonjakan `auth.login_throttled`;
  - galat 5xx di log nginx;
  - ruang disk untuk database dan log.

## 8. Sebelum go-live

Lengkapi semua butir berikut sebelum memasukkan data nyata:
1. Checklist pengerasan production di [keamanan.md](../keamanan.md):
   - `APP_DEBUG=false`, `LOG_LEVEL=warning`;
   - HTTPS, `SESSION_SECURE_COOKIE=true`, HSTS;
   - pengguna DB tanpa hak DDL;
   - `TRUSTED_PROXIES`;
   - backup terenkripsi di Indonesia;
   - pemantauan dan alarm;
   - penetration test.
2. Butir keamanan dan PDP yang belum terpenuhi di [perubahan/0017-uat-rilis-mvp-1.md](../perubahan/0017-uat-rilis-mvp-1.md): MFA, retensi audit (JRA), keputusan PPDP/dasar pemrosesan, prosedur insiden 3×24 jam.
3. **Jangan menjalankan `make demo` atau seeder demo/E2E di production.** Seeder tersebut menolak berjalan di production.

## 9. Pemecahan masalah

Lihat bagian pemecahan masalah di [pengembangan.md](../pengembangan.md). Kasus yang sering muncul:

| Gejala | Penyebab / solusi |
|---|---|
| `docker-credential-desktop: executable file not found` | Tambahkan `/Applications/Docker.app/Contents/Resources/bin` ke `PATH` (macOS) |
| Docker tidak merespons | Jalankan Docker Desktop (`open -a Docker` di macOS) |
| Uji UI gagal karena versi Playwright | Versi image di `compose.yaml` harus sama dengan `@playwright/test`; jalankan `./scripts/check-playwright-version.sh` |
| `Data demo sudah ada` | `make demo` sudah pernah dijalankan; jalankan ulang `make demo` (menghapus data lokal) |
