# 0001 — Starter project refactor SIMAPEL

- Branch: `feat/starter-project`
- Tanggal: 4 November 2026

## Ringkasan

PR ini adalah fondasi refactor SIMAPEL dari aplikasi referensi (Laravel 8.83) ke Laravel 13 / PHP 8.5 / MySQL 8.4 LTS, dengan tema UI yang sama. Isinya:
- clean architecture yang ditegakkan oleh uji;
- autentikasi NIP yang diperkeras;
- lingkungan Docker untuk pengembangan dan pengujian prototipe;
- Playwright di Docker dengan pemilihan uji UI terdampak;
- CI/CD GitHub Actions;
- dokumentasi serah terima.

Modul operasional (SOP, pelatihan, tugas, peserta) belum termasuk karena menunggu data dukung. Assessment dan sertifikat ditunda sesuai keputusan pemilik proyek.

## Isi perubahan

| Area | Perubahan |
|---|---|
| Fondasi | Skeleton `laravel/laravel` v13.11.0 (framework v13.40.0) di commit tersendiri; Vite/Tailwind dihapus (aset statis, ADR 0004) |
| Arsitektur | `app/Domain` (Nip, Role, PasswordPolicy, AuditEvent), `app/Application` (CreateUserAccount, ChangeOwnPassword, RecordSuccessfulLogin + port), `app/Infrastructure` (Eloquent, hasher, transaksi, penghenti sesi, jam) |
| Autentikasi | Login NIP, pesan gagal seragam, batas percobaan per NIP+IP dan per IP, sesi akun nonaktif dicabut, wajib ganti kata sandi awal, sesi lain diakhiri setelah ganti kata sandi |
| Audit | Tabel `audit_logs` append-only; event login/logout/sesi dicabut/ganti kata sandi/pembuatan akun; NIP tersamar; kunci sensitif ditolak |
| UI | `public/css/simapel.css` (token referensi), layout + navigasi berbasis peran, dashboard, halaman keamanan akun, halaman galat 403/404/419/429/500/503, terjemahan validasi bahasa Indonesia |
| Keamanan | CSP ketat tanpa `unsafe-inline`, header keamanan, HSTS di HTTPS, `no-store` untuk halaman terautentikasi, mode ketat Eloquent di non-production |
| CLI | `simapel:user:create`: kata sandi selalu tersembunyi, tanpa kredensial bawaan |
| Docker | `docker/php/Dockerfile` (target dev/prod non-root), nginx 1.30, MySQL 8.4 + database `_testing`, Adminer, Playwright; `Makefile`; `scripts/setup-env.sh` (kata sandi DB acak) |
| Pengujian | 101 PHPUnit (Unit, Architecture, Feature), 16 Playwright, 8 uji resolver area terdampak |
| CI/CD | `ci.yml` (quality, tests, e2e, secrets, image), `release-image.yml` (GHCR + SBOM/provenance), Dependabot, template PR |
| Dokumentasi | README, `AGENTS.md`/`CLAUDE.md`, `docs/` (arsitektur, pengembangan, pengujian, keamanan, kepatuhan PDP, CI/CD, serah terima, proses bisnis), ADR 0001–0007 |

## Cara menguji

```sh
make setup
make ci          # lint, Larastan, coverage ≥ 80%, uji UI terdampak
make e2e         # semua uji UI
make user        # buat akun, lalu coba masuk di http://localhost:8080
```

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit (MySQL 8.4) | 101 tes, 317 assertion, lulus |
| Coverage baris | 99,07% (ambang 80%) |
| Larastan level 8 | 0 error |
| Pint | Bersih |
| Playwright (Chromium, Docker) | 16/16 lulus |
| Resolver uji UI terdampak (`node --test`) | 8/8 lulus |
| actionlint (workflow) | Bersih |
| gitleaks (riwayat git) | Tidak ada kebocoran |
| `composer audit` | Tidak ada advisory |
| Image production | Build sukses; berjalan sebagai `www-data`, tanpa dependensi dev, `.env`, maupun folder referensi |

## Area UI terdampak

Semua area, karena ini PR awal: `@setup`, `@auth`, `@account`, `@dashboard`, `@layout`, `@security`, `@errors`.

## Keamanan & data pribadi

- Data pribadi: NIP, nama, email opsional, dan peran (`users`), serta IP/user agent/waktu akses (`sessions`, `audit_logs`). Semuanya tercatat di `docs/kepatuhan-pdp.md`.
- Tidak ada data nyata; NIP uji diawali `9999`.
- `reference-simapel/` diabaikan git.

## Belum termasuk / tindak lanjut

- Modul manajemen pengguna & audit log (UI), SOP Builder, pelatihan, tugas, dan peserta.
- Keputusan data dukung, pemetaan lajur SOP, kalender kerja, retensi (JRA), PPDP, target deploy, dan pilihan aturan agent tambahan.
