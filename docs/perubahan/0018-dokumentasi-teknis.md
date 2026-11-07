# 0018 — Dokumentasi teknis lengkap (ERD, use case, spesifikasi)

- Branch: `docs/dokumentasi-teknis`
- Tanggal: 7 November 2026
- Acuan: permintaan pemilik proyek setelah rilis 0.1.0

## Ringkasan

Dokumentasi teknis versi 0.1.0, disusun dari kode dan migrasi yang sebenarnya:

| Dokumen | Isi |
|---|---|
| [teknis/spesifikasi-teknis.md](../teknis/spesifikasi-teknis.md) | Ruang lingkup, teknologi dan versi, arsitektur per modul, runtime Docker dan image production, variabel lingkungan, ringkasan aturan, daftar endpoint HTTP dan aksesnya, kontrol keamanan, katalog peristiwa audit, kebutuhan non-fungsional, mutu, CI/CD |
| [teknis/model-data.md](../teknis/model-data.md) | **ERD** (Mermaid) dan kamus data seluruh tabel: tipe, kunci, nilai enum, aturan hapus kunci asing |
| [teknis/use-case.md](../teknis/use-case.md) | Aktor, **diagram use case** (3 diagram + hierarki aktor), katalog UC-01 s.d. UC-24 dengan prasyarat, alur, dan aturan |
| [teknis/hak-akses.md](../teknis/hak-akses.md) | Matriks hak akses per peran per aksi, beserta titik penegakannya (gate/use case) |
| [teknis/alur-dan-status.md](../teknis/alur-dan-status.md) | Alur proses MVP-1, diagram status (pelatihan, status waktu, ketentuan SOP, dokumen), garis waktu tenggat, sequence diagram finalisasi dan penugasan |
| [teknis/operasional.md](../teknis/operasional.md) | Instalasi, akun pertama dan pemulihan akses, pembaruan versi, backup/pemulihan (perintah diuji), log dan query audit, pemeriksaan kesehatan, checklist go-live, pemecahan masalah |
| [glosarium.md](../glosarium.md) | Istilah bisnis dan teknis (BAP, SK, ST, H+n, JRA, WIB/WITA, dan lain-lain) |

Diagram memakai Mermaid, sehingga dirender langsung oleh GitHub dan dapat diperbarui sebagai teks. Use case digambar dengan flowchart Mermaid, karena Mermaid belum memiliki jenis diagram use case UML. Seluruh 14 diagram sudah divalidasi dengan parser Mermaid 11 sebelum PR.

## Isi perubahan

| File | Perubahan |
|---|---|
| `docs/teknis/*.md`, `docs/glosarium.md` | Baru |
| `docs/README.md` | Indeks dokumentasi teknis |
| `docs/serah-terima.md`, `CHANGELOG.md` | Rujukan dokumentasi teknis |

## Cara menguji

Hanya dokumentasi. Buka berkas di GitHub untuk melihat diagram yang dirender.

## Area UI terdampak

Tidak ada.

## Keamanan & data pribadi

- Tidak ada data pribadi.
- Contoh perintah backup memuat peringatan bahwa berkas backup berisi data pribadi dan harus dienkripsi serta disimpan di Indonesia.
