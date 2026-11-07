# Dokumentasi SIMAPEL

| Dokumen | Isi |
|---|---|
| [serah-terima.md](serah-terima.md) | Status proyek, cara melanjutkan, keputusan terbuka (mulai dari sini) |
| [mvp-1/panduan.md](mvp-1/panduan.md) | Langkah menuju MVP-1, keputusan pemilik proyek, dan prompt per langkah |
| [mvp-1/uat.md](mvp-1/uat.md) | Checklist UAT per peran dengan data demo (`make demo`) |
| [mvp-1/panduan-pengguna.md](mvp-1/panduan-pengguna.md) | Panduan singkat per peran untuk pengguna |
| [arsitektur.md](arsitektur.md) | Clean architecture, aturan dependensi, cara menambah fitur |
| [pengembangan.md](pengembangan.md) | Lingkungan Docker, perintah harian, pemecahan masalah |
| [pengujian.md](pengujian.md) | Unit/feature/arsitektur test, coverage, Playwright, uji UI terdampak |
| [keamanan.md](keamanan.md) | Kontrol keamanan dipetakan ke OWASP Top 10:2025, target ASVS |
| [kepatuhan-pdp.md](kepatuhan-pdp.md) | UU PDP dan regulasi terkait, inventaris data pribadi |
| [ci-cd.md](ci-cd.md) | GitHub Actions di repository private, rilis image, opsi deploy |
| [proses-bisnis/analisis-referensi.md](proses-bisnis/analisis-referensi.md) | Ringkasan tiga SOP dan temuan dari aplikasi referensi |
| [agent-rules-usulan.md](agent-rules-usulan.md) | Usulan aturan tambahan untuk AI agent (menunggu keputusan) |
| [adr/](adr/) | Catatan keputusan arsitektur (ADR) |
| [perubahan/](perubahan/) | Catatan per pull request untuk serah terima |

Konvensi dokumentasi:
- Dokumen ditulis dalam bahasa Indonesia, sedangkan identifier kode memakai bahasa Inggris.
- Setiap PR memperbarui `CHANGELOG.md`, menambah satu file di `docs/perubahan/`, dan memperbarui dokumen yang terdampak.
