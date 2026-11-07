# 0006 — Menyamakan versi Playwright 1.65 dan pengaman versi

- Branch: `chore/playwright-1.65`
- Tanggal: 7 November 2026

## Ringkasan

PR Dependabot #8 menaikkan image Docker Playwright ke v1.65.0, sementara `@playwright/test` di `e2e/package.json` masih 1.64.1. Browser di image tidak cocok dengan paket npm, sehingga semua uji UI di `main` gagal (`Executable doesn't exist at /ms-playwright/chromium_headless_shell-1248/...`).

Perbaikan:
- `@playwright/test` dinaikkan ke **1.65.0** (exact) beserta `package-lock.json`.
- `scripts/check-playwright-version.sh` memastikan versi image di `compose.yaml` sama dengan `@playwright/test`. Pemeriksaan ini dijalankan di awal `make e2e*` dan sebagai langkah CI sebelum stack Docker dibangun, sehingga selisih versi langsung gagal dengan pesan jelas.
- Dependabot:
  - aturan ignore image memakai nama `playwright`, nama yang dipakai Dependabot. Aturan lama memakai nama lengkap `mcr.microsoft.com/playwright` sehingga tidak berlaku, dan karena itu #8 tetap dibuat;
  - `@playwright/test` dan `playwright` di ekosistem npm juga diabaikan;
  - pembaruan Playwright dilakukan manual dan bersamaan (lihat `docs/pengujian.md`).

## Isi perubahan

| File | Perubahan |
|---|---|
| `e2e/package.json`, `e2e/package-lock.json` | `@playwright/test` 1.64.1 → 1.65.0 |
| `scripts/check-playwright-version.sh` | Baru: pemeriksa kesamaan versi |
| `Makefile`, `.github/workflows/ci.yml` | Menjalankan pemeriksa sebelum uji UI |
| `.github/dependabot.yml` | Ignore Playwright diperbaiki (docker-compose dan npm) |
| `e2e/tests/tim-kerja.spec.ts` | Mengeluarkan sisa anggota sintetis "E2E Anggota …" dari eksekusi gagal sebelumnya, karena sisa Sekretaris membuat uji berikutnya gagal pada aturan satu Sekretaris |
| `docs/pengujian.md`, `docs/pengembangan.md` | Aturan dan cara menaikkan versi Playwright |

## Cara menguji

```sh
./scripts/check-playwright-version.sh   # "Versi Playwright sama: 1.65.0"
make e2e                                # semua uji UI
make ci
```

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| Pemeriksa versi | "Versi Playwright sama: 1.65.0" |
| PHPUnit 13, coverage | 220 tes lulus, 98,29% |
| Larastan level 8, Pint | Bersih |
| Playwright 1.65.0 | Lulus dua kali berturut-turut; uji badge `@pengaturan` dilewati bila semua tim sudah punya ketentuan Berlaku |

## Area UI terdampak

Semua area, karena `e2e/package*.json` dan `docker/`/config Playwright dipetakan ke `*`.

## Keamanan & data pribadi

- Tidak ada data pribadi.
- Rantai pasok: versi dipin exact, dan lock file diperbarui lewat `npm install` di container resmi.
