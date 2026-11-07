# 0013 — Administrasi BAP/SK/ST, peringatan, dan dashboard (MVP-1 langkah 6)

- Branch: `feat/administrasi-peringatan`
- Tanggal: 7 November 2026
- Acuan: [docs/mvp-1/panduan.md](../mvp-1/panduan.md) langkah 6, keputusan K3, K9, K12, business story (kelengkapan administrasi, link pemantauan, peringatan segera berakhir)

## Ringkasan

- **Dokumen administrasi BAP, SK, dan ST** dibuat otomatis (status Belum) saat pelatihan difinalisasi. Migrasi juga melengkapi dokumen untuk pelatihan yang sudah Final; ini hanya menambah baris.
  - Isian: status (Belum/Dalam proses/Selesai), nomor, tanggal, link berkas di penyimpanan kantor, dan penanggung jawab. Tidak ada unggah berkas.
  - Status **Selesai** wajib memiliki nomor, tanggal, dan link.
  - **Ketua tim** (atau Super Admin) mendelegasikan dokumen ke **Sekretaris tim kerja pelatihan**.
  - Pembaruan dapat dilakukan Sekretaris yang ditugaskan, Ketua, dan Super Admin.
- **Kartu Administrasi** di detail pelatihan Final menampilkan:
  - progres x/3;
  - tenggat tiap dokumen (tanggal + H+n);
  - acuan SOP dari salinan ketentuan;
  - tenggat administrasi siap;
  - delegasi dan tombol Perbarui sesuai hak.
- **Peringatan** (`App\Domain\Administration\TrainingAlerts`, murni, memakai "hari ini" dari Clock), tampil sebagai badge di daftar, detail, dan dashboard:

  | Peringatan | Aturan | Tingkat |
  |---|---|---|
  | `{BAP/SK/ST} tenggat hari ini` | Dokumen belum Selesai dan hari ini = akhir pendaftaran + H+n | Info |
  | `{BAP/SK/ST} lewat tenggat` | Dokumen belum Selesai dan hari ini sudah melewati tenggatnya | Peringatan |
  | Administrasi kritis | Ada dokumen belum Selesai setelah tenggat "administrasi siap" atau saat pelatihan sudah dimulai | Kritis |
  | Tenggat belum ditetapkan | Pelatihan Final tanpa salinan ketentuan SOP | Peringatan |
  | Link peserta kosong | Pendaftaran sudah ditutup dan link daftar peserta kosong | Peringatan |
  | Segera berakhir | Pelatihan sedang berlangsung dan berakhir ≤ N hari lagi (Pengaturan, default 7) | Info |
  | Bentrok penugasan | Ada pegawai yang ditugaskan dengan penugasan lain yang beririsan | Peringatan |

  Tenggat selalu dihitung dari **salinan ketentuan pada pelatihan** (langkah 3), bukan dari ketentuan atau pengaturan terkini.
- **Dashboard:**
  - kartu ringkasan: pelatihan berjalan, berkas belum lengkap, segera berakhir, bentrok penugasan;
  - **Perlu tindakan saya**: dokumen yang didelegasikan ke saya dan belum Selesai, beserta tenggatnya; pelatihan yang saya admin-i yang pendaftarannya sudah ditutup tanpa link daftar peserta;
  - **Pelatihan berjalan** (dilihat semua pegawai, K9): pendaftaran sudah dibuka dan pelatihan belum berakhir, lengkap dengan status dan peringatan.

## Isi perubahan

| Lapisan | Perubahan |
|---|---|
| Domain | `Administration\AdministrativeDocument`, `DocumentType`, `DocumentStatus`, `DocumentPermissions`, `DocumentDeadline`, `TrainingAlerts`, `TrainingAlertInput`, `TrainingAlert`, `AlertKind`, `AlertSeverity`; event audit delegasi dan pembaruan dokumen |
| Application | Port `AdministrativeDocumentRepository`; use case `DelegateDocument`, `UpdateDocument`; `DocumentAccess`, `TrainingAlertsQuery`, `DashboardOverview`; `FinalizeTraining` membuat dokumen; `WorkTeamRepository::roleOf()`, `TrainingRepository::running()` |
| Infrastructure | Migrasi `create_administrative_documents_table` (+ pelengkapan untuk pelatihan Final lama), model `AdministrativeDocumentRecord`, `EloquentAdministrativeDocumentRepository` |
| Presentation | `AdministrativeDocumentController` (ubah, perbarui, delegasi; dokumen diperiksa terhadap pelatihan pada URL), `DocumentRequest`, view `trainings/document-form`, kartu Administrasi, komponen `<x-training.alert-badges>`, badge di daftar, dashboard baru |
| E2E | Helper `e2e/support/trainings.ts`; tes resolver lintas modul kini memeriksa tag inti, bukan daftar persis |

## Keputusan implementasi (mohon dikonfirmasi)

1. **"Segera berakhir"** hanya ditampilkan saat pelatihan sedang berlangsung (sudah mulai dan belum selesai). Pelatihan yang belum dimulai tidak ditandai, walaupun berakhir dalam ≤ N hari.
2. **"Mendekati tenggat"** hanya muncul tepat pada hari tenggat, sesuai prompt. Belum ada peringatan H-1 dan seterusnya.
3. **"Kritis"** muncul mulai sehari setelah tenggat administrasi siap, atau pada hari pertama pelatihan.
4. **Delegasi** per dokumen, hanya kepada Sekretaris tim kerja pelatihan (K2: maks. satu Sekretaris per tim). Ketua dan Super Admin tetap dapat memperbarui dokumen yang sudah didelegasikan.
5. **"Berkas belum lengkap"** di dashboard menghitung pelatihan Final yang sedang berjalan dengan dokumen Selesai < 3.

## Cara menguji

```sh
make migrate
make ci
```

Uji manual:
1. Tetapkan Ketua dan Sekretaris tim DTA. Finalisasi pelatihan DTA: kartu Administrasi menampilkan BAP/SK/ST "Belum" dengan tenggat H+n.
2. Sebagai Ketua, delegasikan SK ke Sekretaris. Masuk sebagai Sekretaris: SK muncul di "Perlu tindakan saya". Perbarui SK menjadi Selesai; tanpa nomor/tanggal/link, penyimpanan ditolak.
3. Ubah tanggal sistem atau pakai pelatihan yang pendaftarannya sudah ditutup: badge "lewat tenggat", "Administrasi kritis", dan "Link peserta kosong" muncul di daftar dan dashboard.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 375 tes lulus |
| Coverage baris | 98,63% (ambang 80%) |
| Larastan level 8, Pint, uji arsitektur | Bersih |
| Playwright (area terdampak) | 36 lulus, 1 dilewati; diulang dua kali |
| Resolver area terdampak | 15/15 lulus |

## Area UI terdampak

`@administrasi` (baru), `@dashboard`, `@pelatihan`, dan semua area karena route, migrasi, provider, dan `e2e/support` berubah.

## Keamanan & data pribadi

- **Otorisasi:**
  - delegasi hanya oleh Ketua tim terkait atau Super Admin;
  - pembaruan hanya oleh Sekretaris yang ditugaskan, Ketua, atau Super Admin;
  - keduanya ditegakkan oleh use case;
  - id dokumen diperiksa terhadap pelatihan pada URL (404 bila tidak cocok), sehingga mencegah IDOR.
- **Validasi server:** status enum, nomor ≤ 100 karakter, tanggal `Y-m-d`, link http/https, dan kelengkapan status Selesai.
- **Data pribadi:** hanya link berkas yang disimpan. Berkas SK/BAP yang dapat memuat data peserta tetap di penyimpanan kantor dengan hak aksesnya sendiri. Hal ini dicatat di `docs/kepatuhan-pdp.md`.
- **Audit log** mencatat delegasi (penanggung jawab lama dan baru) dan pembaruan dokumen (nilai lama dan baru per field).
