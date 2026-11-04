# Aturan kerja SIMAPEL (developer & AI agent)

SIMAPEL adalah sistem pendukung administrasi dan pemantauan pelatihan regional BLSDM Komdigi Yogyakarta, berdasarkan tiga SOP resmi. SIMAPEL tidak menggantikan DTS/Digitalent pusat.

- Komunikasi dengan pemilik proyek dalam **bahasa Indonesia**. Identifier kode dalam bahasa Inggris; teks UI dan dokumen dalam bahasa Indonesia.
- `reference-simapel/` (hanya lokal, diabaikan git) adalah **acuan read-only**. Jangan diubah dan jangan disalin mentah; pindahkan pola atau aturannya ke struktur baru.
- Ringkasan SOP dan temuan: `docs/proses-bisnis/analisis-referensi.md`. Status dan keputusan terbuka: `docs/serah-terima.md`.

## Aturan wajib

Ditetapkan pemilik proyek pada 4 November 2026.

1. **Stack tetap**: Laravel 13, PHP 8.5, Blade, MySQL 8.4, CSS/JS statis di `public/`. Tanpa framework/build frontend baru. UI memakai token dan komponen `public/css/simapel.css` (tema gelap referensi).
2. **Clean architecture** (`docs/arsitektur.md`):
   - Domain murni, tanpa framework.
   - Application berisi use case dan port; tanpa framework, Eloquent, maupun Infrastructure.
   - Infrastructure mengimplementasikan port dan mendaftarkannya di `AppServiceProvider::$bindings`.
   - Presentation tidak memakai `App\Infrastructure`.
   - Semua perubahan data lewat Application. `make test-arch` wajib lulus.
3. **Setiap perubahan punya unit test.** Logika Domain/Application wajib punya unit test (pakai fake di `tests/Support/Fakes`). Perilaku HTTP/CLI baru wajib punya feature test. Coverage baris minimal 80% (`make coverage`). Tes hanya memakai database `*_testing`.
4. **Uji UI = Playwright di Docker, hanya area terdampak** (`make e2e-affected`) kecuali diminta lain. Halaman/modul baru wajib punya spec dengan tag area dan entri di `e2e/impact-map.json`.
5. **Keamanan (OWASP Top 10:2025) & UU PDP** (`docs/keamanan.md`, `docs/kepatuhan-pdp.md`):
   - otorisasi di backend (policy/middleware), bukan hanya menu;
   - tanpa `style=""`, `on*=""`, atau `<script>` inline (CSP);
   - validasi input di server;
   - tanpa data pribadi nyata, kredensial, atau `.env` di commit, fixture, log, maupun artefak CI;
   - NIP ditampilkan/dicatat tersamar (`Nip::masked()`);
   - data pribadi baru didokumentasikan dalam PR yang sama;
   - data production tidak keluar dari Indonesia.
6. **Setiap perubahan siap Pull Request.** Buat branch dari `main` (`feat/…`, `fix/…`, `chore/…`, `docs/…`), commit kecil dengan pesan jelas, lalu siapkan deskripsi PR. Jangan commit/push langsung ke `main` dan jangan merge PR. Review manusia wajib.
7. **Dokumentasi serah terima di setiap PR**:
   - entri `CHANGELOG.md`;
   - file `docs/perubahan/NNNN-judul.md` (dipakai sebagai deskripsi PR);
   - pembaruan dokumen yang terdampak;
   - ADR di `docs/adr/` untuk keputusan arsitektur/teknologi.
8. **Proses bisnis mengikuti SOP resmi.** Jangan mengarang durasi, aktor, validator, atau aturan kelulusan. Hal yang belum pasti ditandai "perlu konfirmasi" dan ditanyakan ke pemilik proyek. **Assessment dan sertifikat ditunda** (ADR 0007).
9. **Lingkungan dan CI.** Semua perintah dijalankan lewat Docker (`make …`). Sebelum PR, `make ci` (lint, analisis statis, coverage, uji UI terdampak) harus lulus.

## Perintah

| Tujuan | Perintah |
|---|---|
| Lingkungan pertama kali / akun pertama | `make setup` / `make user` |
| PHPUnit semua / unit / arsitektur | `make test` / `make test-unit` / `make test-arch` |
| Coverage ≥ 80% | `make coverage` |
| Gaya kode / analisis statis | `make lint` (`make fix`) / `make stan` |
| Uji UI terdampak / semua | `make e2e-affected` / `make e2e` |
| Semua pemeriksaan | `make ci` |

## Definition of Done

- [ ] Kode di lapisan yang benar; `make test-arch` lulus
- [ ] Unit test + feature test; `make coverage` ≥ 80%
- [ ] `make lint` dan `make stan` bersih
- [ ] Spec Playwright + `impact-map.json` diperbarui (bila UI berubah); `make e2e-affected` lulus
- [ ] Checklist keamanan & PDP di template PR terpenuhi
- [ ] `CHANGELOG.md`, `docs/perubahan/NNNN-*.md`, dokumen terkait, dan ADR (bila perlu) diperbarui

## Tanyakan dulu ke pemilik proyek sebelum

- menambah dependensi (Composer/npm/image) atau layanan eksternal;
- mengubah kontrol keamanan (CSP, sesi, kebijakan kata sandi, pembatasan login);
- migrasi yang mengubah/menghapus data yang sudah ada;
- menafsirkan SOP yang ambigu atau menambah aturan bisnis di luar SOP;
- membuat/push repository remote, membuat PR di GitHub, atau melakukan deploy.

Usulan aturan tambahan yang belum diputuskan: `docs/agent-rules-usulan.md`. Usulan tersebut **belum berlaku**.
