# ADR 0011 — Wilayah kerja, zona waktu, dan data mitra

- Status: Diterima (7 November 2026)
- Terkait: keputusan K18 di [mvp-1/panduan.md](../mvp-1/panduan.md), [ADR 0009](0009-status-pelatihan-dan-learning-path.md)

## Konteks

Wilayah kerja BLSDM Komdigi Yogyakarta mencakup DIY, Jawa Tengah (WIB), dan Bali (WITA, WIB + 1 jam). Template WA perlu menyebut jam penutupan pendaftaran yang benar bagi peserta di wilayah mitra. Sebelumnya isian "mitra/lokasi" berupa teks bebas, sehingga sistem tidak mengetahui wilayah, dan nama mitra ditulis ulang setiap kali.

## Keputusan

1. **Wilayah kerja** menjadi enum Domain `WorkRegion` (DIY, Jawa Tengah, Bali) dengan zona waktunya: Asia/Jakarta (WIB) dan Asia/Makassar (WITA). Wilayah wajib diisi saat finalisasi.
2. **Jam penutupan pendaftaran** disimpan per pelatihan dalam **WIB** (default 23.59).
   - `RegistrationDeadline` menampilkannya dalam zona waktu wilayah kerja, dengan WIB dalam kurung.
   - Tanggal ikut bergeser bila perlu, misalnya 10 Des 23.59 WIB = 11 Des 00.59 WITA.
   - Konversi memakai basis data zona waktu PHP (bukan +1 jam manual), sehingga pergantian bulan dan tahun benar.
3. **Mitra** menjadi data master (`partners`: nama, wilayah kerja).
   - Duplikat dicegah dengan nama yang dinormalisasi (huruf kecil, tanpa tanda baca, spasi tunggal) dan *unique index*.
   - Pelatihan merujuk mitra lewat `partner_id`, dan wilayah kerja pelatihan otomatis mengikuti mitra.
   - Bila pelaksanaan "Mitra", mitra wajib saat finalisasi.
4. **Siapa yang mengelola mitra:** Super Admin, Ketua tim kerja mana pun, dan pegawai bertanda "dapat menjadi Admin" walaupun tidak sedang ditugaskan, agar pekerjaan terbagi. Semua pegawai dapat melihat daftar mitra.
5. **Form pelatihan** memakai `<input list>` + `<datalist>` (autocomplete bawaan HTML; tanpa script, aman untuk CSP). Nama yang belum terdaftar didaftarkan sebagai mitra baru bila pelaku berhak.
6. **Data lama tidak dipetakan otomatis.** Isian "mitra/lokasi" lama tetap sebagai detail lokasi. Pelatihan lama tanpa wilayah memakai WIB di template, dan detailnya menampilkan "Wilayah belum diisi".
7. **Wilayah pelatihan disimpan pada pelatihan**, bukan dibaca dari mitra saat ditampilkan. Mengubah wilayah mitra tidak menggeser pelatihan yang sudah ada; wilayah ikut diperbarui saat pelatihan disimpan ulang.

## Alternatif yang ditolak

- **Selisih jam manual (+1 untuk Bali):** tidak menggeser tanggal dengan benar dan rapuh bila wilayah bertambah.
- **Zona waktu dipilih bebas per pelatihan:** rawan salah; zona waktu ditentukan dari wilayah kerja.
- **Pemetaan otomatis data lama:** hanya berupa tebakan dari teks bebas dan tetap perlu ditinjau. Data yang ada saat ini masih data uji (ditolak pemilik proyek).

## Konsekuensi

- Langkah 7 (beban kerja) dapat memakai wilayah kerja pelatihan.
- Bila wilayah kerja bertambah, cukup menambah kasus pada `WorkRegion` beserta zona waktunya.
- Penggabungan mitra duplikat yang terlanjur ada belum tersedia; pencegahan dilakukan saat input.
