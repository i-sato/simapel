# ADR 0009 — Status pelatihan dan learning path

- Status: Diterima (7 November 2026)
- Terkait: keputusan K5, K6, K9, K11, K12 di [mvp-1/panduan.md](../mvp-1/panduan.md), [ADR 0008](0008-model-peran-berlapis.md), [ADR 0010](0010-ketentuan-sop-berversi.md)

## Konteks

Business story menetapkan bahwa Ketua tim (atau Super Admin) menyusun informasi pelatihan sebagai **draft**, lalu Admin melengkapi dan **memfinalisasinya** (K5). Pelatihan berjenjang dibentuk seperti *learning path*: tiap level adalah pelatihan sendiri dengan link dan jadwal sendiri, tetapi saling terhubung (K6). Semua pegawai dapat melihat pelatihan (K9). Tenggat administrasi mengikuti ketentuan SOP tim kerja yang disalin saat finalisasi (K12).

## Keputusan

1. **Dua jenis status yang terpisah.**
   - *Status administrasi* `Draft → Final` disimpan di `trainings.status`. Final tidak dapat kembali menjadi Draft.
   - *Status waktu* (Akan dibuka, Pendaftaran, Penetapan peserta, Pelaksanaan, Selesai) **tidak disimpan**. Status ini diturunkan dari empat tanggal lewat port `Clock` (`TrainingSchedule::phaseOn()`), sehingga tidak pernah basi. Filter daftar memakai padanan SQL-nya (`App\View\TrainingPhases::scope()`).
   - Semua tanggal berupa tanggal saja dan inklusif. Pendaftaran dianggap berakhir sehari setelah tanggal akhir pendaftaran.
2. **Draft boleh parsial, tetapi yang diisi harus benar.**
   - Hanya nama yang wajib saat Draft.
   - Tanggal yang diisi wajib konsisten: awal ≤ akhir, dan pendaftaran ditutup ≤ pelatihan mulai.
   - Angka berupa bilangan bulat 1–100.000, dan link wajib http/https.
   - Finalisasi mewajibkan 18 field lengkap (`TrainingDetails::REQUIRED_FOR_FINAL`). Panitia dan Admin menyusul di langkah 4.
3. **Pelatihan Final masih dapat dilengkapi** oleh Super Admin, Ketua tim kerja terkait, dan Admin yang ditunjuk (langkah 4), misalnya link daftar peserta yang baru tersedia setelah pendaftaran ditutup (K13, keputusan 7 November 2026). Syaratnya:
   - data tetap lengkap;
   - tim kerja tidak berubah;
   - salinan ketentuan SOP tidak berubah.
4. **Link daftar peserta** baru dapat diisi atau diubah setelah periode pendaftaran berakhir. Nilai yang sudah tersimpan tidak diperiksa ulang.
5. **Salinan ketentuan SOP saat finalisasi.**
   - Ketentuan di-resolve untuk (kode tim kerja, tanggal finalisasi) dengan resolver ADR 0010.
   - Yang disalin ke kolom `trainings.sop_*`: id versi, tahun, nomor versi, nomor dan link SOP, tenggat BAP/SK/ST/administrasi siap, serta penanda *fallback*.
   - Bila tim belum punya ketentuan Berlaku sama sekali, finalisasi tetap jalan dengan tenggat kosong dan peringatan.
   - `sop_provision_id` memakai FK `restrict`, dan `SopProvisionRepository::isReferenced()` kini memeriksa relasi ini.
6. **Peringatan jeda persiapan.** Jeda (hari) dari penutupan pendaftaran sampai pelatihan mulai dibandingkan dengan "administrasi siap" (`PreparationGap`).
   - Peringatan tampil di detail pelatihan dan saat finalisasi, tanpa memblokir.
   - Draft memakai ketentuan yang berlaku hari ini; Final memakai salinannya.
7. **Learning path** adalah entitas sendiri (`learning_paths`) milik satu tim kerja.
   - Pelatihan menempatkan diri pada path lewat `learning_path_id` dan levelnya (Dasar = 1, Menengah = 2, Mahir = 3).
   - Aturan "satu level satu pelatihan per path" dijaga di Domain (`LearningPath::assertPlacement`), oleh kunci baris path, dan oleh *unique index* `(learning_path_id, level)`.
   - Path harus satu tim dengan pelatihan.
   - Path dibuat dari form pelatihan dengan mengisi nama path baru, atau dipilih dari yang ada.
   - Detail pelatihan menampilkan level terisi terdekat sebelum dan sesudahnya (`LearningPathSequence`).
8. **Hak akses** ada di Domain (`TrainingPermissions`) dengan fakta pengguna dari `TrainingActorProvider` (peran global dan tim yang dipimpin). Dipakai oleh gate (`create-training`, `update-training`, `finalize-training`) dan **ditegakkan ulang oleh use case** (`TrainingAccessDenied` → 403):

   | Aksi | Siapa |
   |---|---|
   | Melihat daftar dan detail | Semua pegawai aktif |
   | Membuat Draft | Ketua tim kerja terkait, Super Admin |
   | Mengubah Draft, melengkapi Final | Ketua tim kerja terkait, Super Admin, Admin yang ditunjuk (langkah 4) |
   | Memfinalisasi | Super Admin, Admin yang ditunjuk (langkah 4) |

   Titik perluasan langkah 4: `TrainingActor::$assignedAdminTrainingIds` (Admin yang ditugaskan dapat mengubah, memfinalisasi, dan melengkapi pelatihan tersebut). Saat ini daftarnya selalu kosong.

## Alternatif yang ditolak

- **Menyimpan status waktu dan memperbaruinya dengan scheduler**: menambah job, dan status bisa basi bila job terlambat.
- **Mengunci total pelatihan Final**: link daftar peserta dan perbaikan data tidak dapat dilakukan, padahal business story menyebut Admin memperbarui data setelah pendaftaran ditutup.
- **Learning path sebagai kolom "pelatihan sebelumnya"**: urutan rapuh bila satu level dihapus atau disisipkan, dan aturan satu level per path sulit dijaga.

## Konsekuensi

- Langkah 4 mengisi `assignedAdminTrainingIds` dari penugasan, tanpa mengubah aturan.
- Langkah 6 memakai kolom `trainings.sop_*` untuk menghitung tenggat BAP/SK/ST.
- Field wajib finalisasi, batch berupa angka, dan izin melengkapi pelatihan Final sudah dikonfirmasi pemilik proyek (K13, catatan perubahan 0008).
