# Analisis proses bisnis: SOP resmi dan aplikasi referensi

Sumber yang dibaca (tersedia lokal di `reference-simapel/docs/referensi/`, tidak di-commit):
- tiga PDF SOP resmi;
- TOR SIMAPEL (Tim IKEA, 15 Oktober 2026);
- dokumen rencana pengembangan lama (PRD);
- seluruh source aplikasi referensi (Laravel 8.83).

Transkripsi di bawah adalah **bahan kerja**. Dokumen SOP resmi tetap menjadi sumber kebenaran.

## Ringkasan tiga SOP

Setiap SOP memakai tiga lajur pelaksana: **Kepala Balai**, **Ketua Tim (Kerja)**, dan **Tim Fokus**. Tim Fokus terdiri atas Tim Admin, Tim Kepesertaan, Admin LMS/DTS, Sekretaris, dan Panitia. Alur ketiganya berurutan (linear).

### Pengelolaan Pelatihan Keterampilan Digital Dasar (KeDaS)

No. 438/BLSDM.32/OT.02.02/05/2026, efektif 24 September 2026. 11 tahap, total waktu tercantum **29 hari** (memakai batas atas 5 hari pada tahap 9).

| No | Aktivitas | Lajur | Waktu | Output |
|---|---|---|---|---|
| 1 | Menyusun rencana pelatihan (permintaan Pemda/Mitra dan/atau evaluasi tahun lalu) | Ketua Tim | 1 hari | Rencana Pelatihan |
| 2 | Menetapkan rencana kegiatan pelatihan — **keputusan Ya/Tidak; Tidak → kembali ke tahap 1** | Ka Balai | 1 hari | Rencana pelatihan yang ditetapkan |
| 3 | Menugaskan tim pelaksana | Ka Balai | 1 hari | ST Tim pelaksana |
| 4 | Mempersiapkan kegiatan (koordinasi Pemda/Mitra, pengajar, jadwal, persuratan, survei) | Ketua Tim | 5 hari | Persuratan dan bahan publikasi |
| 5 | Membuka link pendaftaran | Tim Fokus | 1 hari | Link Pendaftaran |
| 6 | Seleksi dan verifikasi pendaftar, termasuk konfirmasi kehadiran | Tim Fokus | 5 hari | Daftar peserta hasil seleksi/verifikasi |
| 7 | Menetapkan calon peserta | Ka Balai | 1 hari | SK Peserta |
| 8 | Mengolah data peserta, LMS, dan presensi | Tim Fokus | 3 hari | Daftar pengumuman peserta, daftar nama peserta |
| 9 | Menyelenggarakan pelatihan (oleh Ketua dan Anggota Tim) | Ketua Tim | 3–5 hari | Pelatihan yang telah terlaksana |
| 10 | Mengubah status peserta di DTS: **Lulus, Tidak lulus, Tidak hadir** | Ketua Tim | 1 hari | Perubahan status peserta di DTS |
| 11 | Laporan pelaksanaan pelatihan | Ketua Tim | 5 hari | Laporan kegiatan pelatihan |

### Pengelolaan Pelatihan Aparatur Digital (GTA)

No. 439/BLSDM.32/OT.02.02/05/2026, efektif 24 September 2026. 13 tahap, total waktu tercantum **37 hari** (tidak termasuk tahap "sesuai kesepakatan/juknis").

| No | Aktivitas | Lajur | Waktu | Output |
|---|---|---|---|---|
| 1 | Menyusun perencanaan pelatihan | Ketua Tim | 5 hari | Draft rencana pelatihan |
| 2 | Menetapkan rencana pelatihan — **keputusan Ya/Tidak; Tidak → kembali ke tahap 1** | Kepala Balai | 1 hari | Rencana pelatihan yang ditetapkan |
| 3 | Menugaskan tim pelaksana | Kepala Balai | 1 hari | ST tim pelaksana |
| 4 | Mempersiapkan pelatihan | Ketua Tim | 10 hari | Persuratan dan administrasi |
| 5 | Membuka link pendaftaran (Admin DTS meminta persetujuan Pokja GTA) | Tim Fokus | 1 hari | Link Pendaftaran |
| 6 | Pendaftaran dan pendampingan (Tim Kepesertaan) | Tim Fokus | Sesuai kesepakatan Mitra Pemda | Data peserta terdaftar |
| 7 | Seleksi, verifikasi, konfirmasi. Status peserta: **Tidak lulus administrasi / Diterima**. Sekretaris menyiapkan dokumen kepesertaan, Mitra menyiapkan SPT | Tim Fokus | 3 hari | Daftar calon peserta hasil seleksi |
| 8 | Menetapkan peserta (pengumuman otomatis melalui Digitalent) | Kepala Balai | 1 hari | BAP, SK, dan Pengumuman Peserta |
| 9 | Mengolah data peserta, LMS, dan presensi | Tim Fokus | 2 hari | Daftar hadir, enrollment LMS |
| 10 | Menyelenggarakan pelatihan (oleh Panitia) | Tim Fokus | Sesuai Juknis | Pelatihan terlaksana dan administrasi pertanggungjawaban |
| 11 | Penetapan kelulusan (tugas peserta maks. 2 hari, penilaian pengajar maks. 5 hari setelah pelatihan) | Ketua Tim | 7 hari | Daftar kelulusan peserta |
| 12 | Mengubah status peserta di DTS (Admin LMS) | Tim Fokus | 1 hari | Perubahan status peserta di DTS |
| 13 | Menyusun laporan pelaksanaan | Ketua Tim | 5 hari | Laporan kegiatan pelatihan |

### Pengelolaan Kegiatan Talenta Digital (TalDig)

No. 437/BLSDM.34/OT.02.02/07/2026, efektif 6 Juli 2026. 11 aktivitas (**nomor 8 tercantum dua kali**), total waktu tidak dicantumkan, dan tidak ada titik keputusan.

| No | Aktivitas | Lajur | Waktu | Output |
|---|---|---|---|---|
| 1 | Inisiasi kerja sama dan kemitraan | Kabalai | 1 hari | Rencana kegiatan pelatihan |
| 2 | Koordinasi dengan institusi pendidikan/industri | Ketua Tim Kerja | 1 hari | Laporan hasil koordinasi |
| 3 | Penandatanganan Implementation Agreement (VBL, F-DEX, AITF oleh mitra; P-DEX oleh tiap peserta) | Tim Fokus | 1 hari | Surat Komitmen (IA) |
| 4 | Membuat link pendaftaran (Tim Admin, sesuai tema) | Tim Fokus | 1 hari | Link pendaftaran |
| 5 | Sosialisasi dan pendaftaran (Tim Kepesertaan) | Tim Fokus | 1 minggu | Data pendaftar |
| 6 | Seleksi, verifikasi, dan Berita Acara penetapan (Tim Admin) | Tim Fokus | 1 hari | Data verifikasi dan BA penetapan peserta |
| 7 | Menetapkan daftar peserta | Kabalai | 1 hari | SK dan Surat Tugas peserta |
| 8 | Menyiapkan data peserta (pengumuman, status/enrollment, daftar hadir) | Tim Fokus | 1 hari | Peserta terverifikasi pada link pelatihan |
| 8 | Mengikuti pelatihan | Tim Fokus | Sesuai kesepakatan mitra | Peserta yang mengikuti pelatihan |
| 9 | Menetapkan peserta lulus (Tim Admin) | Tim Fokus | 1 hari | Bahan laporan pelatihan |
| 10 | Mendokumentasikan laporan dan arsip | Ketua Tim Kerja | 5 hari | Laporan pelatihan, daftar arsip |

## Temuan pada aplikasi referensi

| # | Temuan | Dampak |
|---|---|---|
| 1 | Seeder tiga SOP tidak membuat satu pun prasyarat, sehingga pemeriksaan prasyarat tugas tidak pernah aktif | Tahap dapat dikerjakan acak, padahal SOP linear |
| 2 | Keputusan Ya/Tidak Kepala Balai hanya menjadi catatan teks | Penolakan rencana tidak punya alur kembali ke perencanaan |
| 3 | Tahap keputusan Kepala Balai (penetapan rencana, ST tim, SK peserta) dikerjakan peran Super Admin lalu divalidasi Ketua Pelatihan | Hierarki validasi terbalik |
| 4 | Pemetaan lajur tidak selalu sama dengan swimlane SOP (contoh: KeDaS tahap 10 di lajur Ketua Tim di-seed ke Admin LMS; GTA tahap 10 di lajur Tim Fokus di-seed ke Ketua) | Penanggung jawab tugas bisa keliru |
| 5 | Modul peserta memakai bank soal pilihan ganda internal dan admin mengetik jawaban tiap peserta. SOP justru memakai status seleksi (Diterima/Tidak lulus administrasi) dan status akhir DTS (Lulus/Tidak lulus/Tidak hadir) dari penilaian pengajar dan data LMS | Beban input manual besar dan duplikasi LMS; TOR menaruh assessment di luar MVP |
| 6 | Tahap "Menetapkan peserta", "Penetapan kelulusan", dan "Ubah status DTS" hanya meminta tautan bukti, tanpa terhubung ke data peserta | Data peserta dan alur SOP tidak saling memvalidasi |
| 7 | Tenggat hanya rencana, tanpa waktu mulai/selesai aktual | Keterlambatan tidak menggeser tahap berikutnya; realisasi terhadap mutu baku (29/37 hari) tidak terukur |
| 8 | Admin dan Ketua pelatihan tidak dapat diganti setelah pelatihan dibuat; status draf/arsip/batal tidak dapat diset | Penugasan kaku saat terjadi pergantian pegawai |
| 9 | Tidak ada antrean "Tugas Saya"/"Menunggu validasi saya", notifikasi, maupun peringatan beban kerja (TOR 4.3/4.6) | Pengguna harus menelusuri tiap pelatihan |
| 10 | Peran kontekstual (Ketua/Anggota) masih ditawarkan sebagai peran global | Bertentangan dengan TOR 2.1 |

## Arah perbaikan (usulan, menunggu data dukung)

- **Mesin alur SOP** dengan prasyarat default linear mengikuti nomor tahap, tahap keputusan (setuju/tolak dengan tujuan kembali), dan validator sesuai hierarki lajur.
- **Realisasi waktu.** Waktu mulai/selesai aktual per tahap, selisih terhadap mutu baku SOP, serta opsi hari kerja (menunggu keputusan kalender libur).
- **Siklus status peserta mengikuti SOP**: pendaftar → hasil seleksi (Diterima / Tidak lulus administrasi) → ditetapkan (BAP/SK) → hadir → Lulus / Tidak lulus / Tidak hadir. Pengisian lewat impor rekap DTS/LMS, bukan input jawaban soal.
- **Tahap SOP terhubung ke data.** Contoh: tahap penetapan peserta selesai bila daftar peserta dikunci; tahap ubah status DTS memakai rekap kelulusan.
- **Penugasan dinamis.** Ganti Admin/Ketua dengan riwayat; status pelatihan lengkap (draf, aktif, ditunda, dibatalkan, selesai, arsip).
- **Kerja harian.** Antrean tugas pribadi, antrean validasi, dan peringatan beban kerja lintas pelatihan.
- **Assessment & sertifikat** ditunda sampai proses bisnis disepakati (keputusan pemilik proyek, 4 November 2026).

## Hal yang perlu dikonfirmasi pemilik proses

- Pemetaan lajur → peran aplikasi dan validator per tahap, terutama tahap Kepala Balai: dicatat oleh siapa, dan dengan bukti apa.
- Penomoran final SOP TalDig (nomor 8 ganda) dan varian program (VBL, F-DEX, AITF, P-DEX).
- Hari kalender atau hari kerja untuk tenggat; sumber kalender libur.
- Format data DTS/LMS (contoh CSV/XLSX pendaftar, peserta, kehadiran, nilai).
- Kebutuhan dokumen BA/SK/ST: hanya dicatat sebagai tautan, atau dibuat dari templat.
