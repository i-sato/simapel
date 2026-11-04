# Changelog

Semua perubahan penting dicatat di sini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Belum dirilis]

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
