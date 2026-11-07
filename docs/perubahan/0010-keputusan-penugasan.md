# 0010 — Keputusan penugasan (K15)

- Branch: `docs/keputusan-penugasan`
- Tanggal: 7 November 2026
- Acuan: pertanyaan di catatan [0009](0009-penugasan.md), K4 dan K15 di [mvp-1/panduan.md](../mvp-1/panduan.md)

## Ringkasan

Jawaban pemilik proyek atas tiga pertanyaan di catatan 0009. Ketiganya sesuai dengan perilaku yang sudah berjalan, sehingga **tidak ada perubahan kode**.

| No | Pertanyaan | Keputusan | Perilaku saat ini |
|---|---|---|---|
| 1 | Makna "Admin yang ditunjuk Ketua tercentang otomatis" | Admin ditetapkan ulang oleh Ketua untuk setiap pelatihan baru, tanpa centang otomatis | Halaman penugasan hanya mencentang penugasan yang sudah disimpan untuk pelatihan tersebut; pelatihan baru dimulai tanpa centang |
| 2 | Admin sekaligus Panitia | Diperbolehkan | Kedua peran dapat dipegang pada pelatihan yang sama dan tidak dihitung saling bentrok |
| 3 | Bentrok setelah tanggal pelatihan diubah | Cukup; tidak perlu alasan baru | Bentrok diperiksa saat penugasan dibuat; perubahan tanggal kemudian hanya memunculkan badge bentrok |

## Isi perubahan

| File | Perubahan |
|---|---|
| `docs/mvp-1/panduan.md` | K4 dan cakupan langkah 4 disesuaikan; K15 ditambahkan |
| `docs/serah-terima.md` | Keputusan 10 ditambahkan; pertanyaan terbuka "Penugasan" ditutup |
| `CHANGELOG.md` | Entri dokumentasi |

## Cara menguji

Hanya dokumentasi; tidak ada perubahan kode maupun UI.

## Keamanan & data pribadi

Tidak ada perubahan.
