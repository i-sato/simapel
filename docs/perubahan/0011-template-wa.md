# 0011 — Template WA (MVP-1 langkah 5)

- Branch: `feat/template-wa`
- Tanggal: 7 November 2026
- Acuan: [docs/mvp-1/panduan.md](../mvp-1/panduan.md) langkah 5, Additional Features V.1 nomor 2c, K1, K9, contoh template VBL pada business story

## Ringkasan

Halaman **Template WA** dibuka dari detail pelatihan (tombol "Template WA").

- **Pratinjau** teks yang sudah terisi data pelatihan, dengan pilihan Default / Template 1–3.
  - Tombol **Salin** memakai `navigator.clipboard` lewat `data-copy-target` di `public/js/simapel.js`. Bila clipboard API tidak tersedia, misalnya di HTTP non-TLS, tombol memakai cadangan `execCommand`.
  - Tautan **Buka di WhatsApp** mengarah ke `https://wa.me/?text=…`. Tidak ada pengiriman otomatis.
- **Template default** tidak disimpan. Template ini disusun ulang setiap kali ditampilkan, mengikuti struktur contoh VBL:
  - sambutan;
  - login dan registrasi Digitalent;
  - link dan jadwal pelatihan, serta daftar peserta;
  - rangkaian learning path (link dan jadwal tiap level) bila pelatihan termasuk learning path;
  - 📌 panduan DTS dan panduan pelatihan tim kerja;
  - NB bahwa pelatihan **gratis**, lalu "Terima kasih".

  Info yang belum ada tampil "(menyusul)" dan otomatis terisi ketika data dilengkapi, misalnya link daftar peserta.
- **Template kustom** milik masing-masing anggota per pelatihan:
  - maksimal **3** (penghitung "x/3");
  - editor menampilkan daftar placeholder dan aturan;
  - setiap template wajib memuat `{{link_pendaftaran}}`, `{{tanggal_pendaftaran}}`, `{{tanggal_pelatihan}}`, `{{link_panduan_pelatihan}}`, dan kata "gratis" di bagian akhir;
  - **Reset** mengembalikan template ke default;
  - setelah simpan atau reset, template yang identik dengan default atau dengan template lain digabung, sehingga yang pertama dipertahankan. Bila semua di-reset, tersisa template utama (default).
- **Akses:** Panitia, Ketua tim kerja terkait, Admin yang ditugaskan pada pelatihan tersebut, dan Super Admin (K1). Pegawai lain mendapat 403.
- **Emoji:** kolom `message_templates.body` eksplisit `utf8mb4`.

## Isi perubahan

| Lapisan | Perubahan |
|---|---|
| Domain | `Messaging\Placeholder` (daftar putih), `MessageValues` ("(menyusul)"), `TemplateRenderer` (`strtr`, tanpa evaluasi), `TemplateBody` (placeholder dikenal, elemen wajib, "gratis" di akhir, maks. 4.000 karakter, normalisasi), `TemplateSet` (maks. 3, penggabungan), `DefaultTemplate`, `CustomTemplate`; `DateRange::longLabel()`; `TrainingActor::$assignedPanitiaTrainingIds`; `TrainingPermissions::canUseMessageTemplates()`; event audit |
| Application | Port `CustomTemplateRepository`; `TrainingMessageValues` (data pelatihan + pengaturan + learning path), `TrainingMessages` (pratinjau + otorisasi), use case `SaveCustomTemplate`, `ResetCustomTemplate`; `AssignmentRepository::panitiaTrainingIdsOf()`; `TrainingRepository::inLearningPath()` |
| Infrastructure | Migrasi `create_message_templates_table` (utf8mb4), model `MessageTemplateRecord`, `EloquentCustomTemplateRepository` |
| Presentation | Gate `use-message-templates`, `MessageTemplateController`, `MessageTemplateRequest`, view `trainings/messages`, tombol "Template WA" di detail; perilaku salin di `public/js/simapel.js` |

## Keputusan implementasi (mohon dikonfirmasi)

1. **Super Admin ikut mengakses** Template WA karena K1 ("Super Admin dapat mengakses seluruh fitur"). Prompt langkah 5 hanya menyebut panitia, Ketua, dan Admin. Bila Super Admin perlu dikecualikan, cukup ubah satu baris di `TrainingPermissions::canUseMessageTemplates()`.
2. **"Bagian akhir"** untuk kata "gratis" didefinisikan sebagai 5 baris berisi terakhir. Template default memenuhinya: "NB", "… alias gratis.", "Terima kasih".
3. **"Link panduan"** yang wajib ada adalah link panduan pendaftaran pelatihan tim kerja (`{{link_panduan_pelatihan}}`). Link panduan DTS tersedia sebagai placeholder tetapi tidak wajib.
4. **Placeholder harus ditulis persis tanpa spasi**, misalnya `{{nama_pelatihan}}`. Bentuk `{{ nama_pelatihan }}` ditolak agar tidak tertukar dengan sintaks Blade.
5. **Template kustom bersifat pribadi.** Anggota lain, termasuk Super Admin, tidak dapat mengubah atau me-reset template milik orang lain.

## Cara menguji

```sh
make migrate
make ci
```

Uji manual:
1. Tugaskan seorang pegawai sebagai Panitia (Atur penugasan), lalu masuk sebagai pegawai tersebut. Buka pelatihan, lalu **Template WA**.
2. Pratinjau default tampil dengan "(menyusul)" untuk info yang belum ada. Tekan **Salin** dan **Buka di WhatsApp**.
3. Buat **Template baru**. Coba tulis `{{ nomor_hp }}`: ditolak. Simpan template beremoji: tersimpan, dan penghitung menjadi 1/3.
4. Lengkapi link daftar peserta di pelatihan (setelah pendaftaran ditutup). Pratinjau langsung terisi.
5. **Reset** template: template digabung dan penghitung menjadi 0/3.
6. Masuk sebagai pegawai yang tidak ditugaskan: tombol tidak tampil, dan `/trainings/{id}/messages` menghasilkan 403.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 344 tes lulus |
| Coverage baris | 98,54% (ambang 80%) |
| Larastan level 8, Pint, uji arsitektur | Bersih |
| Playwright (area terdampak) | 33 lulus, 1 dilewati |
| Resolver area terdampak | 14/14 lulus |

## Area UI terdampak

`@template-wa` (baru), `@pelatihan`, `@penugasan`, dan semua area karena route, migrasi, provider, dan `public/js` berubah.

## Keamanan & data pribadi

- **Injeksi template:** isi template tidak pernah diproses sebagai Blade/PHP. Rincian risiko dan mitigasinya ada di `docs/keamanan.md`, bagian "Risiko injeksi template".
- **Otorisasi** ditegakkan oleh gate `use-message-templates` dan use case. Template dibatasi per pemilik dan per pelatihan, sehingga id milik orang lain ditolak 403.
- **CSP** tetap ketat: tombol Salin memakai `data-*` dan JS statis, tanpa script inline.
- **Data pribadi:** template kustom dicatat di `docs/kepatuhan-pdp.md`. Audit log hanya mencatat id template, panjang, dan status penggabungan, tanpa isi template.
