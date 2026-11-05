# CI/CD di GitHub (repository private)

**Ya, CI/CD dapat berjalan di repository private.** GitHub Actions memakai GitHub-hosted runner dengan kuota pemakaian bulanan sesuai paket akun/organisasi.

## Workflow

| Workflow | Pemicu | Job |
|---|---|---|
| `ci.yml` | PR, push ke `main`, manual | `quality` (Pint, Larastan level 8, `composer audit`), `tests` (PHPUnit di MySQL 8.4 + coverage ≥ 80%), `e2e` (stack Docker + Playwright untuk area terdampak), `secrets` (gitleaks pada riwayat git), `image` (build image production) |
| `release-image.yml` | Tag `v*.*.*`, manual | Build & push image production ke GHCR (paket private), dengan SBOM dan provenance |
| `dependabot.yml` | Mingguan (Senin) | PR pembaruan Composer, npm (e2e), image Docker, GitHub Actions |

Prinsip keamanan pipeline:
- Semua action di-pin ke commit SHA.
- `permissions` default hanya `contents: read`.
- `persist-credentials: false` saat checkout.
- Kata sandi E2E dibuat acak per eksekusi dan di-mask di log.
- Artefak CI hanya berisi data sintetis.

## Hal yang bergantung pada paket GitHub

Ringkasan dari halaman paket GitHub (diakses 4 November 2026). Periksa kembali sebelum memutuskan paket.

| Fitur | GitHub Free (private) | Pro / Team | Enterprise |
|---|---|---|---|
| GitHub Actions | Ya, dengan kuota *compute* bulanan terbatas | Kuota lebih besar | Kuota terbesar |
| Protected branch, required status checks, required reviewers | **Tidak tersedia** untuk repo private | Tersedia | Tersedia |
| Environment & secret per environment | Tidak untuk repo private | Tersedia | Tersedia |
| Deployment protection rules (persetujuan sebelum deploy) | Tidak | Tidak | Tersedia |
| Penyimpanan GitHub Packages (GHCR) | 500 MB | 2 GB | 50 GB |

Konsekuensi untuk akun Free/private: CI tetap berjalan, tetapi "PR harus hijau sebelum merge" tidak bisa dipaksa oleh sistem dan bergantung pada disiplin reviewer. Bila penegakan dibutuhkan, gunakan organisasi dengan paket Team atau setara.

## Pengaturan yang disarankan setelah repository dibuat

1. Aktifkan protected branch `main` (bila paket mendukung) dengan syarat berikut: wajib lewat PR, status check `quality`, `tests`, `e2e`, `secrets`, dan `image` harus lulus, serta minimal satu reviewer.
2. Aktifkan Dependabot alerts & security updates di **Settings → Code security**.
3. Batasi Actions hanya untuk action terverifikasi/di-pin (**Settings → Actions → General**).
4. Pastikan visibilitas paket GHCR tetap **private**.

## Continuous Delivery (menunggu keputusan)

Target server belum ditentukan. Opsi yang sesuai PP 71/2019 (server di Indonesia):

| Opsi | Cara kerja | Catatan |
|---|---|---|
| A. Self-hosted runner di jaringan kantor | Runner menarik image dari GHCR lalu menjalankan `docker compose` di server | Tidak membuka port masuk dari internet; runner perlu dikelola |
| B. Pull-based | Server memeriksa tag image baru secara terjadwal | Paling sederhana; tidak ada kredensial deploy di GitHub |
| C. Manual terdokumentasi | Operator menarik image bertag rilis | Cocok untuk tahap UAT |

Data yang dibutuhkan untuk menyiapkan CD: lokasi dan OS server, akses jaringan (internet keluar ke `ghcr.io`), reverse proxy/HTTPS, kebijakan backup, dan pihak yang menyetujui deploy.
