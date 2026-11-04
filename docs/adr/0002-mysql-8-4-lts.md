# ADR 0002 — MySQL 8.4 LTS

- Status: Diterima (4 November 2026)

## Konteks

Referensi berjalan di MySQL 8.0 (Laragon). Pemilik proyek meminta teknologi database yang sama untuk memudahkan transisi tim. Seri MySQL 8.0 sudah mencapai akhir masa dukungan (April 2026).

## Keputusan

- MySQL 8.4 LTS untuk pengembangan, pengujian, dan production. Teknologinya sama, seri yang masih didukung.
- Feature test berjalan di MySQL (`simapel_testing`), bukan SQLite, agar perilaku query sama dengan production. Referensi pernah memakai fungsi khusus MySQL (`DATE_FORMAT`) yang tidak teruji di SQLite.
- Keputusan migrasi data dari `db_simapel` lama menunggu data dukung.

## Konsekuensi

- Pengujian membutuhkan service MySQL (tersedia di Docker dan CI).
- Query khusus MySQL tetap terlindungi oleh pengujian.
