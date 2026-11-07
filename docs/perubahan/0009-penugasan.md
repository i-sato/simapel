# 0009 — Penugasan Admin/Panitia dan bentrok jadwal (MVP-1 langkah 4)

- Branch: `feat/penugasan`
- Tanggal: 7 November 2026
- Acuan: [docs/mvp-1/panduan.md](../mvp-1/panduan.md) langkah 4, keputusan K3, K4, K8, K13, Additional Features V.1 nomor 1, [ADR 0009](../adr/0009-status-pelatihan-dan-learning-path.md)

## Ringkasan

- **Halaman "Atur penugasan"** dibuka dari detail pelatihan dan hanya dapat diakses Ketua tim kerja terkait dan Super Admin. Isinya daftar centang pegawai aktif, dengan kolom Admin dan Panitia, serta badge:
  - peran tim kerja pada tahun pelatihan (mis. "Ketua Tim DTA", "Sekretaris Tim KDD");
  - "Admin" untuk pegawai bertanda dapat menjadi Admin;
  - bentrok, mis. "⚠ Panitia DTA · 1–9 Nov". Nama pelatihan yang bentrok muncul saat kursor diarahkan.
- **Aturan:**
  - Admin hanya dari pegawai aktif bertanda "dapat menjadi Admin". Kotak centangnya nonaktif untuk pegawai lain, dan server tetap menolaknya.
  - Panitia boleh pegawai aktif mana pun, lintas tim, termasuk Ketua dan Sekretaris tim lain.
  - Bentrok dihitung dari **awal pendaftaran sampai akhir pelatihan** (inklusif; bersinggungan satu hari pun dihitung bentrok), lintas tim kerja, baik untuk pelatihan Draft maupun Final.
  - **Penugasan baru yang bentrok wajib disertai alasan (force).** Tanpa alasan, penyimpanan ditolak dengan pesan yang menyebut pegawai, peran, dan bentroknya. Alasan disimpan pada penugasan dan di audit log.
- **Kotak peringatan + isian alasan** tampil bila ada pegawai yang bentrok, atau bila penyimpanan ditolak karena bentrok.
- **Detail pelatihan** menampilkan daftar Admin dan Panitia beserta badge peran tim, badge bentrok, dan alasan force.
- **Admin yang ditugaskan** kini dapat memfinalisasi Draft dan melengkapi pelatihan (termasuk link daftar peserta). Ini menyambungkan titik perluasan langkah 3 (`TrainingActor::$assignedAdminTrainingIds`). Hak ini hilang bila tanda "dapat menjadi Admin" dicabut. Admin tidak mengatur penugasan.

## Isi perubahan

| Lapisan | Perubahan |
|---|---|
| Domain | `Shared\DateRange` (irisan inklusif, label "1–9 Nov"), `Assignment\TrainingAssignment`, `AssignmentRole`, `AssignmentCandidate` (kelayakan), `AssignedTraining`, `WorkloadConflictDetector`, `AssignmentPlan` (selisih + kelayakan + force), `AssignmentConflict`; `TrainingSchedule::assignmentRange()`; `TrainingPermissions::canManageAssignments()`; event audit tambah/cabut penugasan |
| Application | Port `AssignmentRepository`, `AssignmentCandidateRepository`; use case `UpdateTrainingAssignments`; query `TrainingWorkload`; `TrainingActorProvider` mengisi pelatihan tempat pegawai menjadi Admin; `TrainingAccess::canManageAssignments()` |
| Infrastructure | Migrasi `create_training_assignments_table` (unique pelatihan+pegawai+peran), model `TrainingAssignmentRecord`, `EloquentAssignmentRepository` (query irisan di SQL), `EloquentAssignmentCandidateRepository` |
| Presentation | Gate `manage-training-assignments`, `TrainingAssignmentController`, `TrainingAssignmentsRequest`, view `trainings/assignments`, bagian Penugasan di detail, `View\TeamRoleBadges` |

## Keputusan implementasi (mohon dikonfirmasi)

1. **"Admin yang ditunjuk Ketua tercentang otomatis"** diterapkan sebagai berikut: pegawai yang sudah ditugaskan tampil tercentang setiap kali halaman dibuka. Penugasan disimpan oleh Ketua (atau Super Admin) dan menjadi dasar hak Admin. *Perlu konfirmasi*: apakah yang dimaksud adalah pencentangan awal yang lain, misalnya Admin pelatihan sebelumnya di tim yang sama otomatis tercentang untuk pelatihan baru?
2. **Satu pegawai boleh menjadi Admin sekaligus Panitia** pada pelatihan yang sama. Keduanya tidak dihitung saling bentrok.
3. **Bentrok diperiksa saat penugasan dibuat.** Bila tanggal pelatihan diubah kemudian sehingga muncul irisan baru, penugasan lama tidak dibatalkan dan tidak diminta alasan. Badge bentrok tetap tampil di detail dan halaman penugasan.
4. **Pelatihan dengan jadwal belum lengkap** (awal pendaftaran atau akhir pelatihan kosong) dapat diberi penugasan tanpa pemeriksaan bentrok. Halaman penugasan menampilkan peringatannya.
5. **Satu alasan per penyimpanan** berlaku untuk semua penugasan baru yang bentrok pada penyimpanan tersebut.

## Cara menguji

```sh
make migrate
make ci
```

Uji manual:
1. Tandai seorang pegawai "dapat menjadi Admin" di menu Pegawai.
2. Sebagai Ketua tim DTA, buka pelatihan DTA, lalu **Atur penugasan**. Centang pegawai tersebut sebagai Admin, lalu simpan.
3. Buat pelatihan tim lain dengan rentang yang beririsan. Badge "⚠ Admin DTA · …" tampil di baris pegawai tersebut. Centang dia sebagai Panitia dan simpan tanpa alasan: penyimpanan ditolak. Isi alasan, lalu simpan.
4. Masuk sebagai pegawai Admin tadi. Tombol **Finalisasi** tampil pada pelatihan DTA, tetapi **Atur penugasan** tidak.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 308 tes lulus |
| Coverage baris | 98,64% (ambang 80%) |
| Larastan level 8, Pint, uji arsitektur | Bersih |
| Playwright (area terdampak) | 31 lulus, 1 dilewati; diulang dua kali |
| Resolver area terdampak | 13/13 lulus |

## Area UI terdampak

`@penugasan` (baru), `@pelatihan`, `@tim-kerja`, `@pegawai`, dan semua area karena route, migrasi, dan provider berubah.

## Keamanan & data pribadi

- **Otorisasi:** pengaturan penugasan dibatasi gate `manage-training-assignments` dan ditegakkan ulang oleh use case. Hak Admin per pelatihan diturunkan dari data penugasan di server, bukan dari isian formulir.
- **Validasi server:**
  - kelayakan Admin/Panitia dan keaktifan pegawai;
  - alasan force maksimal 500 karakter;
  - pelatihan dikunci selama penyimpanan agar perubahan bersamaan tidak saling menimpa.
- **Data pribadi baru:** relasi pegawai ↔ pelatihan (peran) dan alasan force. Keduanya sudah dicatat di `docs/kepatuhan-pdp.md`.
  - Alasan force adalah teks bebas. Form tidak memintanya berisi data pribadi.
  - Halaman penugasan hanya menampilkan nama, jabatan, dan badge, tanpa NIP.
- **Audit log** mencatat penugasan yang ditambahkan dan dicabut (id pegawai, peran, alasan force, jumlah dan id pelatihan yang bentrok), tanpa NIP.
- **CSP:** tanpa script atau style inline. Keterangan badge memakai atribut `title` dan teks `sr-only`.
