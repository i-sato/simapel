# 0008 — Ketua tim kerja melengkapi pelatihan Final; keputusan langkah 3

- Branch: `feat/pelatihan-final-ketua`
- Tanggal: 7 November 2026
- Acuan: keputusan pemilik proyek atas catatan [0007](0007-pelatihan.md), [ADR 0009](../adr/0009-status-pelatihan-dan-learning-path.md), K4, K13, K14 di [mvp-1/panduan.md](../mvp-1/panduan.md)

## Ringkasan

Jawaban pemilik proyek atas empat pertanyaan di catatan 0007:

| No | Pertanyaan | Keputusan | Dampak |
|---|---|---|---|
| 1 | Field wajib finalisasi | Sudah sesuai; hanya link daftar peserta yang boleh kosong | Tidak ada perubahan kode |
| 2 | Siapa yang melengkapi pelatihan Final | Ditambah **Ketua tim kerja terkait** dan **Admin yang ditunjuk** | `TrainingPermissions::canEdit()` diperluas |
| 3 | Batch | Berupa angka | Tidak ada perubahan kode |
| 4 | Akses link daftar peserta | SIMAPEL cukup menyimpan tautan; hak akses mengikuti aturan platform penyedia | Dokumen PDP diperbarui |

## Aturan setelah perubahan

| Aksi | Siapa |
|---|---|
| Melihat daftar dan detail | Semua pegawai aktif |
| Membuat Draft | Ketua tim kerja terkait, Super Admin |
| Mengubah Draft, melengkapi Final | Ketua tim kerja terkait, Super Admin, Admin yang ditunjuk (aktif di langkah 4) |
| Memfinalisasi | Super Admin, Admin yang ditunjuk (aktif di langkah 4) |

Ketua tetap tidak memfinalisasi (K5). Isi pelatihan Final tetap dibatasi oleh `Training::revise()`:
- data harus tetap lengkap;
- tim kerja tidak dapat diganti;
- salinan ketentuan SOP tidak berubah;
- link daftar peserta baru dapat diisi setelah pendaftaran ditutup.

Ketua tim lain dan pegawai biasa tetap mendapat 403.

## Isi perubahan

| File | Perubahan |
|---|---|
| `app/Domain/Training/TrainingPermissions.php` | Final dapat diubah oleh Ketua tim terkait (aturan sama dengan Draft) |
| `app/Application/Training/UpdateTraining.php` | Dokumentasi aktor |
| Tes unit dan feature | Ketua melengkapi Final (termasuk link daftar peserta setelah pendaftaran ditutup); Ketua tidak dapat memindahkan tim (403), tidak dapat mengosongkan field wajib, dan tidak melihat tombol Finalisasi; Ketua tim lain dan pegawai biasa 403 |
| `docs/mvp-1/panduan.md` | K4 disesuaikan; K13 dan K14 ditambahkan |
| `docs/adr/0009-*.md`, `docs/serah-terima.md`, `docs/kepatuhan-pdp.md`, `CHANGELOG.md` | Keputusan dicatat |

## Cara menguji

```sh
make ci
```

Uji manual: finalisasi pelatihan sebagai Super Admin, lalu masuk sebagai Ketua tim kerja pelatihan tersebut. Tombol **Lengkapi** tampil, tombol **Finalisasi** tidak tampil, dan perubahan tersimpan selama data tetap lengkap.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 277 tes lulus |
| Coverage baris | 98,60% (ambang 80%) |
| Larastan level 8, Pint, uji arsitektur | Bersih |
| Playwright (area terdampak `@pelatihan`) | 4/4 lulus |

## Area UI terdampak

`@pelatihan` (tombol "Lengkapi" untuk Ketua). Tidak ada migrasi.

## Keamanan & data pribadi

- Otorisasi tetap di backend: gate `update-training` dan use case `UpdateTraining` memakai aturan Domain yang sama. Perluasan hanya untuk Ketua tim kerja **terkait**.
- Tidak ada data pribadi baru. Link daftar peserta tetap hanya tautan; hak akses berkasnya mengikuti platform penyedia (K14).
- Setiap perubahan pelatihan Final tetap tercatat di audit log dengan nilai lama dan baru.
