# Panduan menyelesaikan MVP-1

Dokumen ini adalah peta jalan dari starter project (PR #1, sudah di-merge) sampai MVP-1 prototipe siap UAT. Setiap langkah berisi tujuan, cakupan, kriteria selesai, dan **prompt siap pakai** untuk AI agent (Claude Code). Satu langkah = satu branch = satu Pull Request.

Acuan kebutuhan: "Simapel Business Story" + "Additional Features V.1" dan "V.2" (problem statement pemilik proyek) dan keputusan di bawah.

## Keputusan pemilik proyek (7 November 2026)

| No | Topik | Keputusan |
|---|---|---|
| K1 | Peran utama | **Super Admin** dapat mengakses seluruh fitur. Peran lain MVP-1: Ketua Tim, Admin, Panitia |
| K2 | Tim kerja | 2026: KDD (Keterampilan Digital Dasar), ASN Digital, Talenta Digital (DTA). Tiap tim: Ketua, Sekretaris, Anggota |
| K3 | Sekretaris | Membuat administrasi (BAP, SK, ST) dan dapat ditetapkan sebagai anggota tim kepesertaan (panitia) |
| K4 | Admin | Super Admin memberi tanda "dapat menjadi Admin" pada pegawai. Admin ditugaskan per pelatihan (admin yang ditunjuk Ketua tercentang otomatis). Hanya Admin yang ditugaskan dan Super Admin yang dapat memfinalisasi draft dan mengubah link daftar peserta |
| K5 | Alur pelatihan | Ketua atau Super Admin membuat **draft**; Admin melengkapi dan **memfinalisasi** |
| K6 | Pelatihan berjenjang | Seperti *learning path*: tiap level adalah pelatihan sendiri dengan link sendiri, saling terhubung. Level 1/2/3 = Dasar/Menengah/Mahir |
| K7 | Link | Link umum (login/registrasi Digitalent, panduan DTS) disimpan di **pengaturan**. Link panduan pelatihan diatur **per tim kerja** |
| K8 | Bentrok jadwal | Dihitung dari **awal pendaftaran hingga akhir pelatihan**. Hanya peringatan; dapat di-force dengan alasan (tercatat di audit log) |
| K9 | Hak lihat | Semua pegawai melihat pelatihan berjalan dan beban kerja secara transparan. Template WA hanya diakses panitia pelatihan tersebut; tiap orang dapat memakai default atau template sendiri |
| K10 | Ditunda dari MVP-1 | MFA, assessment dan sertifikat, alur tahap SOP/SOP Builder, integrasi Google Sheets/WhatsApp otomatis, notifikasi email |
| K11 | Link SOP tim kerja | Setiap tim kerja menyimpan link SOP yang berlaku sebagai panduan dan bahan literasi (V.2 nomor 1). Bila belum dilampirkan, tampil badge peringatan dengan keterangan saat kursor diarahkan, di setiap titik akses: membuat pelatihan, melihat informasi pelatihan, dan sejenisnya |
| K12 | Durasi kritis administratif | Diisi secara **global** di pengaturan dengan mengacu pada SOP yang berlaku (V.2 nomor 2). Default mengikuti contoh SOP TalDig (No. 437/BLSDM.34/OT.02.02/07/2026): BAP paling lambat **H+1**, SK dan ST paling lambat **H+2**, dan administrasi siap **H+3** setelah pendaftaran ditutup |

## Ringkasan langkah

| Langkah | Branch | Isi | Ukuran |
|---|---|---|---|
| 1 | `feat/peran-pegawai-tim` | Model peran baru, data pegawai, tim kerja dan keanggotaan | Sedang |
| 2 | `feat/pengaturan` | Pengaturan link umum, link panduan dan SOP per tim kerja, durasi kritis administratif | Kecil |
| 3 | `feat/pelatihan` | Pelatihan draft → final (21 field), learning path | Besar |
| 4 | `feat/penugasan` | Penugasan Admin/Panitia, badge peran, deteksi bentrok, force | Sedang |
| 5 | `feat/template-wa` | Template WA default dinamis dan kustom | Sedang |
| 6 | `feat/administrasi-peringatan` | BAP/SK/ST dengan tenggat dari durasi kritis, link pemantauan peserta, peringatan, dashboard | Sedang |
| 7 | `feat/beban-kerja` | Pemetaan beban kerja pegawai dan tim | Sedang |
| 8 | `chore/uat-mvp-1` | Uji menyeluruh, data contoh, dokumentasi UAT, rilis `v0.1.0` | Kecil |

Langkah 2 boleh dikerjakan paralel dengan langkah 1. Langkah selanjutnya berurutan karena saling bergantung.

## Cara memakai prompt

1. Pastikan PR sebelumnya sudah di-merge, lalu mulai sesi baru Claude Code di folder `simapel/`.
2. Salin prompt langkah tersebut apa adanya. Aturan umum (arsitektur, tes, keamanan, dokumentasi, PR) sudah ada di `AGENTS.md`, jadi prompt hanya memuat hal spesifik.
3. Agent akan bertanya bila menemukan hal yang ambigu. Jawab, lalu tinjau PR yang dibuat.
4. Setelah review, merge PR, lalu lanjut ke langkah berikutnya.

---

## Langkah 1 — Model peran, pegawai, dan tim kerja

**Tujuan:** fondasi organisasi sesuai K1–K4.

**Cakupan:**
- Peran global: Super Admin dan Pegawai. Hapus `admin_regional`.
- Data pegawai: NIP, nama, pangkat/golongan ruang, jabatan, email, tanda "dapat menjadi Admin", status aktif.
- Tim kerja (KDD, ASN Digital, DTA) per tahun, dengan keanggotaan berperan Ketua/Sekretaris/Anggota. Satu pegawai boleh tercatat di lebih dari satu tim.
- Halaman Super Admin: daftar pegawai (cari, tambah, ubah, nonaktifkan, reset kata sandi dengan wajib ganti saat login), impor CSV sesuai template pegawai, dan halaman tim kerja.

**Kriteria selesai:**
- [ ] Hanya Super Admin yang dapat mengelola pegawai dan tim (policy + feature test).
- [ ] Impor CSV memvalidasi seluruh baris sebelum menyimpan; NIP duplikat ditolak; tidak ada impor parsial.
- [ ] ADR 0008 (model peran berlapis) dan inventaris data pribadi diperbarui.

<details>
<summary>Prompt langkah 1</summary>

```text
Kerjakan Langkah 1 di docs/mvp-1/panduan.md (model peran, pegawai, tim kerja). Ikuti AGENTS.md.

Kebutuhan:
1. Peran global hanya Super Admin dan Pegawai. Hapus admin_regional dari enum Role beserta pemakaiannya, lewat migrasi baru (jangan mengubah migrasi lama).
2. Lengkapi data pegawai pada tabel users: pangkat_golongan (mis. "Penata Muda - III/a"), jabatan, can_be_admin (boolean). Kolom template pegawai: No., Nama, NIP, Pangkat/Gol Ruang, Jabatan, email.
3. Domain baru Organization:
   - WorkTeam dengan kode (KDD, ASN, DTA), nama, dan tahun;
   - TeamMembership (pegawai, tim, peran Ketua/Sekretaris/Anggota);
   - aturan: tepat satu Ketua dan maksimal satu Sekretaris per tim per tahun; pegawai boleh menjadi anggota beberapa tim.
   Seed tiga tim kerja 2026 lewat migrasi data atau seeder idempoten, tanpa data pegawai.
4. Halaman Super Admin dengan tema yang ada:
   - "Pegawai": daftar dengan pencarian dan paginasi, NIP tersamar di daftar, tambah/ubah, nonaktifkan (sesi langsung dicabut lewat SessionTerminator), reset kata sandi (wajib ganti saat login).
   - Impor CSV dengan header persis "nama,nip,pangkat_golongan,jabatan,email", maks. 2 MB / 1000 baris. Validasi semua baris dulu, transaksi semua-atau-tidak, laporan baris yang gagal. Tanpa dependensi baru.
   - "Tim Kerja": daftar tim per tahun, atur Ketua/Sekretaris/Anggota.
5. Semua pegawai yang login dapat melihat daftar tim dan anggotanya (read-only).
6. Audit log untuk semua perubahan pegawai, tim, dan keanggotaan.
7. Tes: unit test aturan keanggotaan, feature test policy (Pegawai tidak bisa mengelola) dan impor CSV, spec Playwright @pegawai dan @tim-kerja, entri impact-map.json.
8. Dokumentasi: ADR 0008 (model peran berlapis: peran global, peran tim, kapabilitas Admin, penugasan per pelatihan), update kepatuhan-pdp.md (pangkat/golongan, jabatan), CHANGELOG, docs/perubahan/NNNN-*.md (nomor berikutnya).

Jalankan make ci, lalu commit di branch feat/peran-pegawai-tim, push, dan buka PR ke main.
```

</details>

---

## Langkah 2 — Pengaturan link, SOP, dan durasi kritis

**Tujuan:** sesuai K7, K11, dan K12.

**Cakupan:**
- Pengaturan global oleh Super Admin: link login Digitalent, link registrasi Digitalent, link panduan pendaftaran DTS.
- Link panduan pendaftaran pelatihan dan **link SOP per tim kerja**.
- **Durasi kritis administratif global** (dalam hari setelah pendaftaran ditutup) beserta acuan SOP-nya:

  | Pengaturan | Default | Acuan (SOP TalDig) |
  |---|---|---|
  | Tenggat BAP penetapan peserta | H+1 | Tahap 6, 1 hari |
  | Tenggat SK dan ST peserta | H+2 | Tahap 7, 1 hari |
  | Administrasi siap (data peserta) | H+3 | Tahap 8, 1 hari |
  | Ambang "segera berakhir" | 7 hari sebelum akhir pelatihan | Business story |

- Badge peringatan "SOP belum dilampirkan" (dengan keterangan saat kursor diarahkan) sebagai komponen yang dipakai ulang di langkah 3 dan seterusnya.
- Semua link wajib http/https.

**Kriteria selesai:** nilai tersimpan dan dipakai lewat port Application (bukan dibaca langsung dari view); perubahan tercatat di audit log.

<details>
<summary>Prompt langkah 2</summary>

```text
Kerjakan Langkah 2 di docs/mvp-1/panduan.md (pengaturan link, SOP, dan durasi kritis). Ikuti AGENTS.md.

Kebutuhan:
1. Pengaturan global yang dikelola Super Admin: link login Digitalent, link registrasi Digitalent, link panduan pendaftaran DTS. Simpan di tabel settings (key/value tervalidasi) dengan port Application SettingsRepository.
2. Per tim kerja (diatur Super Admin): link panduan pendaftaran pelatihan dan link SOP yang berlaku (nomor SOP dan tanggal efektif opsional).
3. Durasi kritis administratif global, dalam hari setelah pendaftaran ditutup, dengan teks acuan SOP:
   - BAP H+1, SK dan ST H+2, administrasi siap H+3 (default dari SOP TalDig No. 437/BLSDM.34/OT.02.02/07/2026 tahap 6–8);
   - ambang "segera berakhir" 7 hari;
   - validasi: bilangan bulat 0–60 dan urutan BAP ≤ SK/ST ≤ siap.
   Simpan sebagai value object Domain AdministrativeDeadlinePolicy agar dipakai langkah 3 dan 6.
4. Komponen Blade badge "SOP belum dilampirkan" (badge warning + atribut title berisi keterangan dan teks yang bisa dibaca screen reader, tanpa script/style inline) untuk dipakai ulang.
5. Validasi URL hanya http/https, maks. 2048 karakter.
6. Halaman "Pengaturan" khusus Super Admin dengan tema yang ada; pegawai lain mendapat 403.
7. Audit log untuk setiap perubahan (simpan nilai lama dan baru, karena link dan durasi bukan data pribadi).
8. Tes: unit test validasi dan AdministrativeDeadlinePolicy, feature test akses, spec Playwright @pengaturan, entri impact-map.json.
9. Dokumentasi: CHANGELOG, docs/perubahan/NNNN-*.md (nomor berikutnya).

Jalankan make ci, lalu commit di branch feat/pengaturan, push, dan buka PR ke main.
```

</details>

---

## Langkah 3 — Pelatihan draft → final dan learning path

**Tujuan:** sesuai K5, K6, K9 (bagian pelatihan), K11, dan K12.

**21 field informasi pelatihan:** nama/tema, tim kerja, link pendaftaran/pelatihan, periode pendaftaran (awal–akhir), tanggal pelatihan (awal–akhir), mitra/lokasi, link daftar peserta (aktif setelah pendaftaran ditutup), metode (Luring/Daring/Hybrid), batch, panitia, admin, link silabus, level (Dasar/Menengah/Mahir), sertifikasi (Global/SKKNI/Industri/Tidak Ada), Certificate of Completion menampilkan nilai (Ya/Tidak), pelaksanaan (Swakelola/Mitra), peserta (Umum/Disabilitas), zonasi (Wilayah Kerja/Nasional), jumlah JP, target peserta, kuota pendaftar.

Panitia dan admin dikerjakan di langkah 4.

**Cakupan:**
- Status **Draft → Final**. Draft boleh belum lengkap. Finalisasi mewajibkan field wajib lengkap dan tanggal konsisten (pendaftaran selesai ≤ pelatihan mulai, awal ≤ akhir).
- Status waktu turunan dari tanggal: Akan dibuka, Pendaftaran, Penetapan peserta, Pelaksanaan, Selesai.
- Learning path: rangkaian pelatihan per level (Dasar → Menengah → Mahir), tiap level dengan link dan jadwal sendiri.
- Badge "SOP belum dilampirkan" pada form dan detail pelatihan bila tim kerjanya belum punya link SOP; bila ada, tampil tautan "SOP tim kerja".
- Peringatan (bukan blokir) bila jeda antara akhir pendaftaran dan awal pelatihan lebih pendek dari durasi kritis "administrasi siap" (default 3 hari).
- Hak akses:
  - Ketua tim dan Super Admin membuat draft.
  - Admin yang ditugaskan dan Super Admin memfinalisasi.
  - Semua pegawai dapat melihat.

**Kriteria selesai:**
- [ ] Aturan status dan validasi finalisasi ada di Domain dengan unit test lengkap.
- [ ] Pegawai yang bukan Ketua tidak dapat membuat draft (feature test).

<details>
<summary>Prompt langkah 3</summary>

```text
Kerjakan Langkah 3 di docs/mvp-1/panduan.md (pelatihan draft → final dan learning path). Ikuti AGENTS.md.

Kebutuhan:
1. Domain Training:
   - 21 field sesuai daftar di panduan (panitia/admin menyusul di langkah 4). Pilihan berupa enum: metode, level, sertifikasi, pelaksanaan, kategori peserta, zonasi. JP, target, dan kuota berupa bilangan bulat positif. Link http/https.
   - Status Draft → Final. Draft boleh parsial. Finalisasi memvalidasi kelengkapan dan konsistensi tanggal (awal ≤ akhir; akhir pendaftaran ≤ mulai pelatihan) dan mengunci status.
   - Status waktu turunan dari tanggal lewat port Clock: Akan dibuka, Pendaftaran, Penetapan peserta, Pelaksanaan, Selesai.
   - Link daftar peserta baru bisa diisi setelah periode pendaftaran berakhir.
   - Peringatan bila jeda akhir pendaftaran → mulai pelatihan lebih pendek dari durasi "administrasi siap" (AdministrativeDeadlinePolicy dari langkah 2). Peringatan tidak memblokir finalisasi.
2. LearningPath:
   - rangkaian pelatihan dalam satu tim kerja, tiap pelatihan menempati satu level (Dasar = Level 1, Menengah = Level 2, Mahir = Level 3);
   - satu level satu pelatihan per path;
   - detail pelatihan menampilkan level sebelum/sesudahnya.
3. Hak akses (policy):
   - Ketua tim terkait dan Super Admin membuat dan mengubah draft;
   - finalisasi oleh Super Admin (Admin yang ditugaskan menyusul di langkah 4, siapkan titik perluasannya);
   - semua pegawai dapat melihat daftar dan detail.
4. UI dengan komponen tema yang ada:
   - daftar pelatihan (filter tim, status, periode) dengan badge status;
   - form draft dua kolom yang dikelompokkan per bagian;
   - halaman detail;
   - tombol Finalisasi dengan konfirmasi;
   - badge "SOP belum dilampirkan" (komponen langkah 2) di form dan detail bila tim kerja belum punya link SOP, atau tautan SOP bila sudah ada.
5. Audit log untuk buat, ubah, dan finalisasi.
6. Tes:
   - unit test aturan status, validasi finalisasi, dan learning path;
   - feature test policy;
   - spec Playwright @pelatihan;
   - entri impact-map.json.
7. Dokumentasi: ADR 0009 (status pelatihan dan learning path), CHANGELOG, docs/perubahan/NNNN-*.md (nomor berikutnya).

Jalankan make ci, lalu commit di branch feat/pelatihan, push, dan buka PR ke main.
```

</details>

---

## Langkah 4 — Penugasan Admin/Panitia dan bentrok jadwal

**Tujuan:** sesuai K3, K4, K8, dan Additional Features V.1 nomor 1.

**Cakupan:**
- Pilihan Admin dan Panitia dari daftar pegawai, lintas tim kerja, termasuk Ketua dan Sekretaris tim lain.
- Admin hanya dari pegawai bertanda "dapat menjadi Admin". Admin yang ditunjuk Ketua tercentang otomatis.
- Badge di setiap nama: peran tim (mis. "Ketua Tim DTA", "Sekretaris Tim KDD"), "Admin", dan penugasan yang beririsan (mis. "Panitia DTA · 1–9 Nov").
- Irisan dihitung dari awal pendaftaran sampai akhir pelatihan, baik untuk pelatihan yang dibuat maupun pelatihan lain.
- Penugasan yang bentrok tetap boleh dengan **force + alasan**; alasan tercatat di audit log.
- Admin yang ditugaskan dapat memfinalisasi draft dan mengubah link daftar peserta.

**Kriteria selesai:** deteksi irisan adalah aturan Domain murni dengan unit test batas tanggal (bersinggungan satu hari, di dalam, di luar).

<details>
<summary>Prompt langkah 4</summary>

```text
Kerjakan Langkah 4 di docs/mvp-1/panduan.md (penugasan Admin/Panitia dan bentrok jadwal). Ikuti AGENTS.md.

Kebutuhan:
1. Domain:
   - TrainingAssignment (pegawai, pelatihan, peran Admin/Panitia, alasan force opsional).
   - Value object DateRange dengan aturan irisan inklusif.
   - Rentang penugasan = awal pendaftaran sampai akhir pelatihan.
   - WorkloadConflictDetector mengembalikan daftar pelatihan lain yang beririsan untuk seorang pegawai, lintas tim kerja.
2. Aturan:
   - Admin hanya dari pegawai dengan can_be_admin = true.
   - Panitia boleh siapa saja yang aktif, termasuk Ketua dan Sekretaris tim lain.
   - Penugasan yang bentrok wajib menyertakan alasan (force); tanpa alasan ditolak dengan pesan yang menyebut bentroknya.
3. UI pada form/detail pelatihan:
   - daftar centang pegawai dengan badge peran tim (mis. "Ketua Tim DTA"), badge "Admin", dan badge bentrok (mis. "Panitia DTA · 1–9 Nov");
   - admin yang ditunjuk Ketua tercentang otomatis;
   - kotak peringatan + isian alasan saat ada bentrok.
4. Hak akses:
   - Ketua tim terkait dan Super Admin mengatur penugasan;
   - Admin yang ditugaskan dapat memfinalisasi draft dan mengubah link daftar peserta (sambungkan ke titik perluasan dari langkah 3).
5. Audit log untuk tambah/hapus penugasan, termasuk alasan force.
6. Tes:
   - unit test DateRange dan detektor (bersinggungan satu hari, di dalam, di luar, lintas tahun);
   - feature test aturan Admin dan force;
   - spec Playwright @penugasan;
   - entri impact-map.json.
7. Dokumentasi: CHANGELOG, docs/perubahan/NNNN-*.md (nomor berikutnya).

Jalankan make ci, lalu commit di branch feat/penugasan, push, dan buka PR ke main.
```

</details>

---

## Langkah 5 — Template WA

**Tujuan:** sesuai Additional Features V.1 nomor 2c dan K9.

**Cakupan:**
- Template default disusun dari data pelatihan setiap kali ditampilkan, sehingga otomatis memuat info yang menyusul (mis. link daftar peserta). Info yang belum ada tampil sebagai "(menyusul)".
- Learning path ikut ditampilkan (link dan jadwal tiap level).
- Template kustom: maks. 3 per panitia per pelatihan, wajib memuat link pelatihan, tanggal pendaftaran/pelatihan, link panduan, dan pernyataan **gratis** di bagian akhir.
- Reset ke default; template yang identik digabung (bila semua di-reset, tersisa satu template utama).
- Tombol **Salin** dan **Buka di WhatsApp**; tanpa pengiriman otomatis.
- Hanya panitia pelatihan tersebut (termasuk Ketua dan Admin yang ditugaskan) yang dapat mengakses.

**Kriteria selesai:**
- [ ] Template kustom tidak pernah dieksekusi sebagai Blade/kode, hanya penggantian placeholder dari daftar putih (diuji).

<details>
<summary>Prompt langkah 5</summary>

```text
Kerjakan Langkah 5 di docs/mvp-1/panduan.md (template WA). Ikuti AGENTS.md.

Kebutuhan:
1. Domain MessageTemplate:
   - Placeholder daftar putih, mis. {{nama_pelatihan}}, {{link_pendaftaran}}, {{tanggal_pendaftaran}}, {{tanggal_pelatihan}}, {{link_daftar_peserta}}, {{link_panduan_dts}}, {{link_panduan_pelatihan}}, {{learning_path}}.
   - Renderer hanya mengganti placeholder. Placeholder tak dikenal ditolak saat menyimpan. Info kosong tampil "(menyusul)".
   - Isi template TIDAK PERNAH diproses sebagai Blade/PHP.
2. Template default:
   - dibentuk dari data pelatihan + pengaturan langkah 2 setiap kali ditampilkan (tidak disimpan sebagai teks tetap);
   - struktur mengikuti contoh template VBL di problem statement;
   - diakhiri pernyataan bahwa pelatihan gratis.
3. Template kustom per panitia per pelatihan:
   - maks. 3;
   - wajib memuat placeholder link pelatihan, tanggal pendaftaran, tanggal pelatihan, link panduan, dan kata "gratis" di bagian akhir;
   - reset ke default;
   - setelah simpan/reset, template yang identik dengan default atau dengan template lain dihapus (tersisa satu).
4. Akses: hanya panitia/Ketua/Admin yang ditugaskan pada pelatihan tersebut (policy). Pegawai lain 403.
5. UI:
   - pratinjau terisi data dan pilihan template;
   - tombol Salin (navigator.clipboard di public/js/simapel.js lewat atribut data-*, tanpa script inline);
   - tautan "Buka di WhatsApp" (https://wa.me/?text=...);
   - editor dengan daftar placeholder dan penghitung template (x/3).
6. Pastikan kolom template mendukung emoji (utf8mb4).
7. Tes:
   - unit test renderer (placeholder, menyusul, injeksi seperti {{ }} berisi kode dan @php tidak dieksekusi), validasi elemen wajib, batas 3, reset dan dedup;
   - feature test akses;
   - spec Playwright @template-wa;
   - entri impact-map.json.
8. Dokumentasi: update keamanan.md (risiko injeksi template dan mitigasinya), CHANGELOG, docs/perubahan/NNNN-*.md (nomor berikutnya).

Jalankan make ci, lalu commit di branch feat/template-wa, push, dan buka PR ke main.
```

</details>

---

## Langkah 6 — Administrasi BAP/SK/ST, link pemantauan, peringatan, dashboard

**Tujuan:** sesuai K3, K12, dan business story (kelengkapan administrasi, link pemantauan peserta, peringatan segera berakhir).

**Cakupan:**
- Per pelatihan final, tiga dokumen BAP, SK, dan ST. Ketua mendelegasikan ke Sekretaris tim. Isian: status (Belum/Dalam proses/Selesai), nomor dan tanggal dokumen, link berkas di penyimpanan kantor. Tanpa upload.
- Peringatan (badge di daftar dan detail pelatihan):
  - dokumen lewat tenggat durasi kritis (BAP H+1, SK/ST H+2 setelah pendaftaran ditutup), dan **kritis** bila administrasi belum lengkap melewati H+3 atau pelatihan sudah dimulai;
  - link daftar peserta belum diisi setelah pendaftaran ditutup;
  - pelatihan segera berakhir (≤ 7 hari, dapat diatur di pengaturan);
  - bentrok penugasan.
- Dashboard: kartu ringkasan (pelatihan berjalan, berkas belum lengkap, segera berakhir, bentrok) dan daftar "Perlu tindakan saya".

**Kriteria selesai:** semua aturan peringatan ada di Domain (berbasis Clock) dengan unit test batas waktu.

<details>
<summary>Prompt langkah 6</summary>

```text
Kerjakan Langkah 6 di docs/mvp-1/panduan.md (administrasi BAP/SK/ST, link pemantauan, peringatan, dashboard). Ikuti AGENTS.md.

Kebutuhan:
1. Domain AdministrativeDocument:
   - jenis BAP/SK/ST, status Belum/Dalam proses/Selesai, nomor, tanggal, link berkas (http/https), penanggung jawab;
   - dibuat otomatis (3 dokumen) saat pelatihan difinalisasi;
   - Ketua mendelegasikan ke Sekretaris timnya;
   - Sekretaris yang ditugaskan, Ketua, dan Super Admin dapat memperbarui;
   - status Selesai mewajibkan nomor, tanggal, dan link.
2. Domain TrainingAlerts (murni, memakai Clock) menghasilkan daftar peringatan per pelatihan:
   - tiap dokumen punya tenggat dari AdministrativeDeadlinePolicy (langkah 2): BAP H+1, SK dan ST H+2 setelah akhir pendaftaran;
   - status peringatan: "mendekati tenggat" (hari tenggat), "lewat tenggat" (melewati tenggat), "kritis" (administrasi belum lengkap setelah H+3 atau saat pelatihan sudah dimulai);
   - link daftar peserta kosong setelah pendaftaran ditutup;
   - segera berakhir bila akhir pelatihan ≤ N hari (pengaturan langkah 2, default 7);
   - bentrok penugasan (dari langkah 4).
3. UI:
   - kartu Administrasi di detail pelatihan (progres x/3, tenggat tiap dokumen, acuan SOP);
   - badge peringatan di daftar pelatihan;
   - dashboard dengan kartu ringkasan dan daftar "Perlu tindakan saya" (dokumen yang didelegasikan ke saya, pelatihan yang saya admin-i tanpa link peserta);
   - semua pegawai melihat ringkasan pelatihan berjalan.
4. Audit log untuk perubahan dokumen dan delegasi.
5. Tes:
   - unit test seluruh aturan peringatan dengan tanggal batas;
   - feature test hak akses Sekretaris;
   - spec Playwright @administrasi dan @dashboard;
   - entri impact-map.json.
6. Dokumentasi: CHANGELOG, docs/perubahan/NNNN-*.md (nomor berikutnya).

Jalankan make ci, lalu commit di branch feat/administrasi-peringatan, push, dan buka PR ke main.
```

</details>

---

## Langkah 7 — Pemetaan beban kerja

**Tujuan:** sesuai business story ("melihat pemetaan beban kerja/tugas pegawai/tim kerja") dan K9.

**Cakupan:** halaman yang dapat dilihat semua pegawai:
- matriks pegawai × periode (minggu/bulan) berisi jumlah penugasan dan badge bentrok, dengan filter tim kerja dan rentang tanggal;
- detail penugasan per pegawai.

**Kriteria selesai:** query efisien tanpa N+1 (mode ketat Eloquent aktif); tampilan ponsel tanpa gulir horizontal halaman (tabel boleh bergulir di dalam wadahnya).

<details>
<summary>Prompt langkah 7</summary>

```text
Kerjakan Langkah 7 di docs/mvp-1/panduan.md (pemetaan beban kerja). Ikuti AGENTS.md.

Kebutuhan:
1. Use case WorkloadMap:
   - input rentang tanggal (default bulan berjalan + 2 bulan), granularitas minggu/bulan, filter tim kerja;
   - output per pegawai per periode: jumlah penugasan (Admin/Panitia, plus Ketua/Sekretaris dari pelatihan timnya) dan tanda bentrok;
   - rentang penugasan memakai aturan langkah 4 (awal pendaftaran sampai akhir pelatihan).
2. Halaman "Beban Kerja" untuk semua pegawai:
   - tabel matriks memakai komponen tabel tema;
   - kelas CSS baru untuk intensitas sel (token warna yang sama, tanpa style inline);
   - klik pegawai untuk melihat daftar penugasannya.
3. Kinerja: hindari N+1, uji dengan 100 pegawai × 50 pelatihan data sintetis.
4. Tes:
   - unit test pengelompokan periode;
   - feature test akses dan kinerja dasar (jumlah query);
   - spec Playwright @beban-kerja (termasuk viewport ponsel);
   - entri impact-map.json.
5. Dokumentasi: CHANGELOG, docs/perubahan/NNNN-*.md (nomor berikutnya).

Jalankan make ci, lalu commit di branch feat/beban-kerja, push, dan buka PR ke main.
```

</details>

---

## Langkah 8 — UAT dan rilis MVP-1

**Tujuan:** prototipe siap diuji pengguna.

**Cakupan:**
- Seeder data **sintetis** untuk demo: tiga tim kerja, sekitar 20 pegawai, sekitar 8 pelatihan dengan berbagai status, learning path, dan bentrok.
- Uji Playwright penuh (`make e2e`) dan skenario UAT per peran (Super Admin, Ketua, Sekretaris, Admin, Panitia, Pegawai).
- Panduan pengguna singkat per peran.
- Pemeriksaan keamanan akhir (checklist `keamanan.md`).
- Tag rilis `v0.1.0`.

<details>
<summary>Prompt langkah 8</summary>

```text
Kerjakan Langkah 8 di docs/mvp-1/panduan.md (UAT dan rilis MVP-1). Ikuti AGENTS.md.

Kebutuhan:
1. DemoSeeder:
   - hanya local/testing (tolak production);
   - data sintetis (NIP diawali 9999): tiga tim kerja 2026 dengan Ketua/Sekretaris/Anggota, sekitar 20 pegawai, sekitar 8 pelatihan (draft, final, berbagai status waktu), satu learning path tiga level, beberapa bentrok penugasan, dan dokumen administrasi dengan status beragam;
   - kata sandi demo dibuat acak dan ditampilkan sekali di terminal.
2. Jalankan make e2e (semua uji UI) dan perbaiki kegagalan.
3. Dokumen docs/mvp-1/uat.md: skenario uji per peran (Super Admin, Ketua, Sekretaris, Admin, Panitia, Pegawai), langkah, dan hasil yang diharapkan, sebagai checklist untuk pengguna.
4. Dokumen docs/mvp-1/panduan-pengguna.md: panduan singkat per peran dengan bahasa non-teknis.
5. Tinjau checklist keamanan.md dan kepatuhan-pdp.md; laporkan butir yang belum terpenuhi.
6. Update serah-terima.md (status MVP-1), CHANGELOG (rilis 0.1.0), docs/perubahan/NNNN-*.md (nomor berikutnya).

Jalankan make ci, lalu commit di branch chore/uat-mvp-1, push, dan buka PR ke main. Setelah PR di-merge, tanyakan dulu sebelum membuat tag v0.1.0.
```

</details>

---

## Masukan yang masih dibutuhkan dari pemilik proyek

| Kapan | Masukan |
|---|---|
| Sebelum langkah 1 | Daftar pegawai dan komposisi tim **diinput langsung di aplikasi** (impor CSV), bukan dikirim ke repository, karena berisi data pribadi |
| Setelah langkah 2 | Link SOP tiap tim kerja (KDD, ASN Digital, DTA) diisi Super Admin di halaman Pengaturan; konfirmasi bila durasi kritis default (H+1/H+2/H+3 dari SOP TalDig) perlu diubah |
| Sebelum langkah 8 | Daftar penguji UAT per peran dan jadwal UAT |
| Kapan saja | Pembatasan akses Google Sheet peserta (saat ini dapat dibuka siapa saja yang memiliki tautan) |
