# 0016 — Keputusan beban kerja: bentrok Sekretaris (K19)

- Branch: `fix/beban-kerja-sekretaris`
- Tanggal: 7 November 2026
- Acuan: pertanyaan di catatan [0015](0015-beban-kerja.md), K19 di [mvp-1/panduan.md](../mvp-1/panduan.md)

## Ringkasan

Jawaban pemilik proyek atas tiga pertanyaan di catatan 0015:

| No | Pertanyaan | Keputusan | Dampak |
|---|---|---|---|
| 1 | Ketua/Sekretaris dinilai bentrok? | Ketua dikecualikan. Sekretaris dapat dinilai bentrok karena mungkin dilibatkan pada kepesertaan tim lain; pembuatan administrasi relatif mudah | Aturan bentrok di Beban Kerja diubah |
| 2 | Jumlah per pelatihan atau per peran | Per pelatihan (inti: penugasan Panitia kepesertaan dan Admin) | Tidak ada perubahan |
| 3 | Filter tim menampilkan Panitia lintas tim | Ya | Tidak ada perubahan |

## Aturan bentrok beban kerja setelah perubahan

Dua peran seorang pegawai pada pelatihan **berbeda** yang rentangnya beririsan dinilai bentrok sebagai berikut (`WorkloadRole::conflictsWith()`):

| Peran A | Peran B | Bentrok? |
|---|---|---|
| Admin/Panitia | Admin/Panitia | Ya (sama dengan langkah 4) |
| Sekretaris | Admin/Panitia | **Ya** (baru): Sekretaris yang juga bertugas di kepesertaan pelatihan lain |
| Sekretaris | Sekretaris | Tidak: administrasi tim sendiri relatif mudah |
| Ketua | apa pun | Tidak |

Interpretasi "Sekretaris vs Sekretaris tidak bentrok" diambil dari kalimat "pembuatan administratif relatif mudah". Bila dua tugas Sekretaris yang beririsan di timnya sendiri juga perlu ditandai bentrok, cukup ubah satu kondisi di `WorkloadRole::conflictsWith()`.

Peringatan bentrok penugasan di detail pelatihan, dashboard, dan halaman Atur penugasan (langkah 4 dan 6) tidak berubah: aturan itu hanya membandingkan penugasan Admin/Panitia.

## Isi perubahan

| File | Perubahan |
|---|---|
| `app/Domain/Workload/WorkloadRole.php`, `WorkloadMatrix.php` | Aturan bentrok per pasangan peran |
| `resources/views/workload/*.blade.php` | Keterangan legenda dan badge |
| Tes unit dan feature | Sekretaris vs Panitia (bentrok), Sekretaris vs Sekretaris (tidak), Ketua vs Admin (tidak) |
| `docs/mvp-1/panduan.md`, `docs/serah-terima.md`, `CHANGELOG.md` | K19 dicatat |

## Cara menguji

```sh
make ci
```

Uji manual: tetapkan seorang Sekretaris tim DTA, lalu tugaskan dia sebagai Panitia pelatihan KDD yang rentangnya beririsan dengan pelatihan DTA. Di **Beban Kerja**, sel tersebut bertanda ⚠ dan detail pegawai menampilkan "Bentrok".

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 415 tes lulus |
| Larastan level 8, Pint | Bersih |
| Playwright (`@beban-kerja`) | 3/3 lulus |

## Area UI terdampak

`@beban-kerja`.

## Keamanan & data pribadi

Tidak ada perubahan.
