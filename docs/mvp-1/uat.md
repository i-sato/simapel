# Skenario UAT MVP-1

Dokumen ini adalah checklist uji penerimaan pengguna (UAT) SIMAPEL MVP-1, disusun per peran. Centang setiap butir yang sesuai harapan. Bila tidak sesuai, catat nomor skenario, langkah, dan apa yang terjadi.

## Persiapan

1. Siapkan data demo **sintetis** di lingkungan lokal/uji:

   ```sh
   make demo
   ```

   > Perintah ini **menghapus seluruh data database lokal**, lalu mengisi data demo. Kata sandi semua akun demo ditampilkan **sekali** di terminal; simpan selama UAT.

2. Buka aplikasi (lokal: http://localhost:8080) dan masuk dengan NIP berikut. Semua NIP diawali 9999 (bukan NIP nyata).

   | Peran untuk UAT | Akun demo | NIP |
   |---|---|---|
   | Super Admin | Demo Super Admin | 999900000000020001 |
   | Ketua tim (KDD) | Demo Ketua KDD | 999900000000020021 |
   | Sekretaris tim (KDD) | Demo Sekretaris KDD | 999900000000020022 |
   | Admin yang ditugaskan | Demo Admin Lintas Tim | 999900000000020043 |
   | Panitia | Demo Pegawai Satu | 999900000000020041 |
   | Pegawai biasa | Demo Pegawai Dua | 999900000000020042 |
   | Akun nonaktif | Demo Pegawai Nonaktif | 999900000000020044 |

   Akun lain: Ketua/Sekretaris DTA (…0011/…0012), Ketua/Sekretaris ASN (…0031/…0032), anggota tiap tim (…0013–0015, …0023–0025, …0033–0035).

3. Gambaran data demo, dengan tanggal relatif terhadap hari ini:

   | Pelatihan | Tim | Status | Yang ingin diperlihatkan |
   |---|---|---|---|
   | Demo Literasi Digital untuk UMKM | KDD | Final, Selesai | Dokumen BAP/SK/ST lengkap, link peserta terisi |
   | Demo Transformasi Digital ASN | ASN | Final, Pelaksanaan | "Segera berakhir", administrasi kritis (ST belum), bentrok |
   | Demo Keamanan Siber Dasar | KDD | Final, Penetapan peserta | Dokumen lewat tenggat, link peserta kosong, mitra Jawa Tengah |
   | Demo Analisis Data Pemerintahan | ASN | Final, Pendaftaran | Mitra Bali (jam penutupan WITA) |
   | Demo Pemasaran Digital | KDD | Final, Akan dibuka | Belum ada peringatan dokumen |
   | Demo VBL Web Developer Level 1–3 | DTA | Level 1 Final, Level 2–3 Draft | Learning path tiga level; DTA memakai ketentuan hasil revisi tengah tahun |
   | Demo Draft Pelatihan Baru | ASN | Draft tanpa jadwal | Draft parsial |

## A. Umum (semua peran)

- [ ] A1. Masuk dengan NIP dan kata sandi demo, lalu keluar lewat tombol **Keluar**.
- [ ] A2. Kata sandi salah menampilkan pesan umum yang tidak menyebut apakah NIP terdaftar.
- [ ] A3. Akun nonaktif (…0044) tidak dapat masuk.
- [ ] A4. Bagian atas halaman menampilkan "Wilayah kerja: DIY, Jawa Tengah, dan Bali".
- [ ] A5. Di ponsel (layar sempit), halaman tidak bergulir ke samping; tabel dapat digulir di dalam kotaknya.

## B. Super Admin (…0001)

- [ ] B1. Menu **ADMINISTRASI › Pegawai** dan **Pengaturan** tampil; NIP di daftar pegawai tersamar.
- [ ] B2. **Pegawai:** tambah pegawai baru, centang "Dapat ditugaskan sebagai Admin", lalu simpan. Pegawai baru wajib mengganti kata sandi saat login pertama.
- [ ] B3. **Pegawai › Impor CSV:** berkas dengan satu baris salah ditolak seluruhnya dan baris salahnya disebutkan.
- [ ] B4. **Tim Kerja:** ubah peran anggota. Tim tidak dapat memiliki dua Ketua atau dua Sekretaris.
- [ ] B5. **Pengaturan:** isi link login/registrasi Digitalent dan link panduan DTS. Link `javascript:` ditolak.
- [ ] B6. **Pengaturan › Ketentuan SOP:** ketentuan DTA tahun ini terdiri dari versi Diganti dan versi Berlaku (revisi 1 Juli). Ketentuan Berlaku tidak dapat diubah.
- [ ] B7. **Pelatihan:** buka "Demo Pemasaran Digital", lalu **Atur penugasan**. Badge peran tim, "Admin", dan bentrok tampil.
- [ ] B8. Finalisasi "Demo VBL Web Developer Level 3". Kartu **Administrasi** muncul dengan BAP/SK/ST "Belum" dan tenggat H+n.
- [ ] B9. **Template WA** pada pelatihan mana pun dapat dibuka (Super Admin dapat bertugas layaknya anggota tim).

## C. Ketua tim (Demo Ketua KDD, …0021)

- [ ] C1. Tombol **＋ Draft pelatihan** tampil. Di form, hanya tim KDD yang dapat dipilih.
- [ ] C2. Buat draft yang hanya berisi nama: tersimpan sebagai Draft dengan daftar isian yang belum lengkap.
- [ ] C3. Lengkapi draft. Tanggal pelatihan sebelum pendaftaran ditutup ditolak.
- [ ] C4. Tombol **Finalisasi** tidak tampil untuk Ketua.
- [ ] C5. Isi **Mitra** dengan nama baru dan wilayah Bali: mitra terdaftar dan wilayah pelatihan menjadi Bali (WITA).
- [ ] C6. Pada "Demo Keamanan Siber Dasar", buka **Atur penugasan**, lalu tambahkan seorang pegawai yang sudah bertugas di pelatihan lain pada rentang yang sama. Penyimpanan tanpa alasan ditolak; dengan alasan, tersimpan.
- [ ] C7. Di kartu **Administrasi**, delegasikan satu dokumen ke Sekretaris tim. Ketua dapat memperbarui dokumen.
- [ ] C8. Ketua dapat melengkapi pelatihan Final timnya, tetapi tidak dapat memindahkannya ke tim lain.
- [ ] C9. Tidak dapat membuka draft milik tim lain untuk diubah (403).

## D. Sekretaris tim (Demo Sekretaris KDD, …0022)

- [ ] D1. **Dashboard › Perlu tindakan saya** memuat dokumen "Demo Keamanan Siber Dasar" yang didelegasikan, beserta tenggatnya.
- [ ] D2. **Perbarui** dokumen menjadi Selesai tanpa nomor ditolak. Setelah nomor, tanggal, dan link diisi, progres bertambah (x/3).
- [ ] D3. Tombol **Perbarui** tidak tampil untuk dokumen yang tidak didelegasikan kepadanya.
- [ ] D4. **Beban Kerja:** Sekretaris yang juga menjadi Panitia di pelatihan tim lain yang beririsan bertanda ⚠ (contoh: Demo Sekretaris DTA, …0012).

## E. Admin yang ditugaskan (Demo Admin Lintas Tim, …0043)

- [ ] E1. Pada "Demo Keamanan Siber Dasar" (Admin), tombol **Lengkapi** tampil. Link daftar peserta dapat diisi karena pendaftaran sudah ditutup.
- [ ] E2. **Dashboard › Perlu tindakan saya** memuat pelatihan yang ia admin-i dengan link peserta kosong, sampai link diisi.
- [ ] E3. Pada "Demo VBL Web Developer Level 2" (Draft, ia sebagai Admin), tombol **Finalisasi** tampil dan berhasil; tombol **Atur penugasan** tidak tampil.
- [ ] E4. Menu **Mitra:** dapat menambah mitra (pegawai bertanda Admin). Nama duplikat (beda huruf besar/kecil) ditolak.

## F. Panitia (Demo Pegawai Satu, …0041)

- [ ] F1. Pada "Demo Keamanan Siber Dasar", tombol **Template WA** tampil.
- [ ] F2. Pratinjau default terisi data pelatihan; info yang belum ada tampil "(menyusul)". Bagian akhir menegaskan pelatihan **gratis**.
- [ ] F3. **Salin** menyalin teks; **Buka di WhatsApp** membuka wa.me dengan teks terisi (tanpa pengiriman otomatis).
- [ ] F4. Buat template kustom beremoji: tersimpan (1/3). Placeholder tak dikenal (mis. `{{nomor_hp}}`) ditolak. Template tanpa kata "gratis" di bagian akhir ditolak.
- [ ] F5. **Reset** template: template kembali ke default (0/3).
- [ ] F6. Pada "Demo Analisis Data Pemerintahan" (mitra Bali), sebagai Super Admin atau Panitianya: baris "Pendaftaran ditutup" menampilkan WITA dengan WIB dalam kurung.

## G. Pegawai biasa (Demo Pegawai Dua, …0042)

- [ ] G1. Dapat melihat **Pelatihan**, **Beban Kerja**, **Mitra**, dan **Tim Kerja**, tanpa tombol ubah.
- [ ] G2. Membuka `/trainings/create`, Template WA pelatihan yang bukan tugasnya, atau `/settings` menghasilkan 403.
- [ ] G3. **Dashboard** menampilkan kartu ringkasan dan daftar pelatihan berjalan beserta peringatannya.
- [ ] G4. **Beban Kerja:** ubah tampilan Mingguan/Bulanan dan filter tim; klik nama pegawai untuk melihat daftar penugasannya.

## Catatan hasil UAT

| No | Skenario | Hasil (sesuai/tidak) | Catatan |
|---|---|---|---|
| | | | |
