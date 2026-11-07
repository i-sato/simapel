# 0007 — Pelatihan Draft → Final dan learning path (MVP-1 langkah 3)

- Branch: `feat/pelatihan`
- Tanggal: 7 November 2026
- Acuan: [docs/mvp-1/panduan.md](../mvp-1/panduan.md) langkah 3, keputusan K5, K6, K9, K11, K12, [ADR 0009](../adr/0009-status-pelatihan-dan-learning-path.md), [ADR 0010](../adr/0010-ketentuan-sop-berversi.md)

## Ringkasan

Menu **Pelatihan** untuk semua pegawai, berisi:
- **Daftar pelatihan** dengan filter tim kerja, status (Draft/Final atau status waktu), dan periode pelaksanaan. Setiap baris menampilkan badge status.
- **Form draft** yang dikelompokkan per bagian (Identitas, Jadwal, Tautan, Kategori, JP/target/kuota, Learning path), memuat 19 dari 21 field business story. Panitia dan Admin menyusul di langkah 4.
- **Halaman detail** yang memuat:
  - status waktu, tautan SOP tim kerja atau badge "SOP belum dilampirkan";
  - peringatan jeda persiapan, daftar field yang belum lengkap;
  - level sebelum dan sesudahnya dalam learning path;
  - salinan ketentuan SOP setelah Final.
- **Finalisasi** dengan konfirmasi. Ketentuan SOP tim kerja yang berlaku pada tanggal finalisasi disalin ke pelatihan. Bila tim belum punya ketentuan, finalisasi tetap jalan dengan peringatan dan tenggat kosong.
- **Hak akses:**
  - Ketua tim terkait dan Super Admin membuat dan mengubah Draft;
  - Super Admin memfinalisasi dan melengkapi pelatihan Final;
  - semua pegawai melihat daftar dan detail.

## Isi perubahan

| Lapisan | Perubahan |
|---|---|
| Domain | `Training\Training` (Draft → Final), `TrainingDetails` (field + kelengkapan), `TrainingSchedule` (konsistensi tanggal, status waktu, jeda), enum metode/level/sertifikasi/pelaksanaan/peserta/zonasi/status, `SopSnapshot`, `PreparationGap`, `LearningPath`, `LearningPathSequence`, `TrainingActor`, `TrainingPermissions`, event audit baru |
| Application | Port `TrainingRepository`, `LearningPathRepository`; use case `CreateTrainingDraft`, `UpdateTraining`, `FinalizeTraining`; `LearningPathPlacement`, `TrainingPreparation`, `TrainingAccess`, `TrainingActorProvider`; `WorkTeamRepository::teamIdsLedBy()` |
| Infrastructure | `EloquentTrainingRepository` (lock baris, unique level), `EloquentLearningPathRepository`, model `TrainingRecord` dan `LearningPathRecord`; `isReferenced()` ketentuan SOP kini memeriksa pelatihan |
| Database | Migrasi `create_learning_paths_and_trainings_tables` (tanpa mengubah data lama) |
| Presentation | Gate `create-training`, `update-training`, `finalize-training`; `TrainingAccessDenied` → 403; `TrainingController`, `TrainingRequest`; view daftar, form, detail; komponen `<x-sop.team-link>`, `<x-training.status-badges>`, `<x-training.input>`, `<x-training.enum-select>`; menu "Pelatihan"; kelas CSS `.meta-list` |

## Keputusan implementasi (mohon dikonfirmasi)

1. **Field wajib finalisasi.** Semua field selain link daftar peserta, panitia, dan admin wajib diisi (18 field, termasuk link silabus dan mitra/lokasi). *Perlu konfirmasi*: apakah link silabus atau mitra/lokasi boleh kosong untuk pelatihan tertentu?
2. **Pelatihan Final masih dapat dilengkapi oleh Super Admin**, misalnya untuk mengisi link daftar peserta setelah pendaftaran ditutup. Data harus tetap lengkap, sedangkan tim kerja dan salinan ketentuan tidak berubah. Ketua tidak dapat mengubah pelatihan Final. *Perlu konfirmasi*: apakah Ketua juga perlu dapat mengubah pelatihan Final?
3. **Batch** berupa angka (1, 2, 3, …).
4. **Kuota pendaftar dan target peserta** tidak dibandingkan satu sama lain, karena SOP maupun business story tidak menyebut aturannya.
5. **Tanggal finalisasi** (hari ini) menentukan ketentuan SOP yang disalin, sesuai panduan. Bila tahun berjalan belum punya ketentuan Berlaku, yang disalin adalah ketentuan tahun sebelumnya dan ditandai sebagai fallback.

## Cara menguji

```sh
make migrate
make ci
```

Uji manual:
1. Tetapkan seorang pegawai sebagai Ketua tim DTA di **Tim Kerja**, lalu masuk sebagai pegawai tersebut. Buat draft yang hanya berisi nama, lalu lengkapi.
2. Pastikan Ketua tidak melihat tombol **Finalisasi** dan tidak dapat membuat draft untuk tim lain.
3. Masuk sebagai Super Admin, lalu klik **Finalisasi**. Bagian "Ketentuan SOP tersalin saat finalisasi" tampil, dan ketentuan tersebut kini terkunci.
4. Buat pelatihan Level 1 dengan learning path baru, lalu pelatihan Level 3 pada path yang sama. Detail menampilkan tautan antarlevel.
5. Masuk sebagai pegawai biasa. Daftar dan detail tampil tanpa tombol ubah, dan `/trainings/create` menghasilkan 403.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 277 tes lulus |
| Coverage baris | 98,56% (ambang 80%) |
| Larastan level 8, Pint, uji arsitektur | Bersih |
| Playwright (area terdampak) | 29 lulus, 1 dilewati (uji badge `@pengaturan` bila semua tim sudah punya ketentuan); diulang dua kali |
| Resolver area terdampak | 12/12 lulus |

## Area UI terdampak

`@pelatihan` (baru), `@tim-kerja`, `@pengaturan`, dan semua area lain karena `routes/web.php`, migrasi, provider, dan CSS berubah.

## Keamanan & data pribadi

- **Otorisasi** ditegakkan di backend dua kali: oleh gate pada route dan oleh use case (`TrainingPermissions`). Menu dan tombol hanya mengikuti hasilnya.
- **Validasi server:**
  - Form Request memeriksa bentuk input;
  - Domain memeriksa tanggal, angka, link http/https, kelengkapan, learning path, dan aturan link daftar peserta.
- **Data pribadi:**
  - Tabel pelatihan tidak menyimpan data pribadi peserta.
  - *Link daftar peserta* menunjuk ke Google Sheet yang berisi data pribadi peserta. SIMAPEL hanya menyimpan tautannya, dan tautan itu terlihat oleh semua pegawai (K9). Form mengingatkan agar akses Sheet dibatasi. Ini sudah dicatat di `docs/kepatuhan-pdp.md`; pembatasan akses Sheet masih menjadi pertanyaan terbuka.
- **Audit log** mencatat pembuatan draft (snapshot field), perubahan (nilai lama dan baru per field), finalisasi (ketentuan yang disalin, jeda, jumlah peringatan), dan pembuatan learning path.
- **CSP:** tanpa script, style, maupun handler inline. Konfirmasi finalisasi memakai `data-confirm`.
