# 0014 — Mitra, wilayah kerja, dan jam penutupan pendaftaran (K18)

- Branch: `feat/mitra-wilayah-kerja`
- Tanggal: 7 November 2026
- Acuan: keputusan pemilik proyek 7 November 2026 (K18), [ADR 0011](../adr/0011-wilayah-kerja-zona-waktu-mitra.md)

## Ringkasan

- **Wilayah kerja** (DIY, Jawa Tengah, Bali) pada pelatihan:
  - wajib saat finalisasi;
  - menentukan zona waktu (DIY dan Jawa Tengah: WIB; Bali: WITA);
  - otomatis mengikuti wilayah mitra bila mitra dipilih.
- **Jam penutupan pendaftaran** per pelatihan dalam WIB, dengan default **23.59 WIB**. Template WA menampilkannya sesuai zona waktu wilayah kerja, misalnya untuk Bali: "11 Desember 2026 pukul 00.59 WITA (10 Desember 2026 pukul 23.59 WIB)". Tanggal ikut bergeser bila perlu.
- **Template WA:**
  - placeholder baru `{{batas_pendaftaran}}` dan `{{wilayah_kerja}}`;
  - template default menambahkan baris "Pendaftaran ditutup: …";
  - elemen wajib template kustom tidak berubah.
- **Mitra** (menu "Mitra"):
  - daftar mitra dengan wilayah kerja dan jumlah pelatihan, bisa dicari;
  - tambah dan ubah oleh Super Admin, Ketua tim kerja, dan semua pegawai bertanda "dapat menjadi Admin";
  - nama duplikat (beda huruf besar/kecil, tanda baca, atau spasi) ditolak.
- **Form pelatihan:**
  - isian **Mitra** dengan autocomplete dari daftar mitra (`<datalist>`, tanpa script);
  - nama baru didaftarkan sebagai mitra bila pengguna berhak;
  - isian **Wilayah kerja**;
  - **Jam penutupan pendaftaran (WIB)**;
  - "Mitra/lokasi" menjadi **Lokasi** (alamat/tempat pelaksanaan).
- Bila pelaksanaan **Mitra**, mitra wajib saat finalisasi.
- Teks topbar "Wilayah layanan" diganti **"Wilayah kerja"**.
- **Data lama tidak dipetakan otomatis.** Pelatihan lama tanpa wilayah memakai WIB di template dan menampilkan "Wilayah belum diisi".

## Isi perubahan

| Lapisan | Perubahan |
|---|---|
| Domain | `Shared\WorkRegion`, `TimeOfDay`, `RegistrationDeadline`; `Partner\Partner` (normalisasi nama), `PartnerPermissions`; `TrainingDetails` (wilayah, jam penutupan, mitra, kelengkapan); `TrainingActor::$canBeAdmin`; placeholder `batas_pendaftaran`, `wilayah_kerja`; event audit mitra |
| Application | Port `PartnerRepository`; `PartnerResolver` (dipakai `CreateTrainingDraft`/`UpdateTraining`), use case `SavePartner`; `AccountSummary::$canBeAdmin`; nilai placeholder baru |
| Infrastructure | Migrasi `create_partners_and_training_region` (tabel `partners`, kolom `trainings.region`, `registration_closes_at` default 23:59, `partner_id`), model `PartnerRecord`, `EloquentPartnerRepository` |
| Presentation | `PartnerController`, view `partners/index`, gate `manage-partners`, menu "Mitra", isian baru di form pelatihan, detail menampilkan wilayah/mitra/penutupan, topbar "Wilayah kerja" |
| E2E | Spec `@mitra`; helper dan `pelatihan.spec.ts` memilih wilayah kerja |

## Cara menguji

```sh
make migrate
make ci
```

Uji manual:
1. Masuk sebagai pegawai bertanda Admin (tidak sedang ditugaskan), buka **Mitra**, lalu tambahkan mitra di Bali. Coba nama yang sama dengan huruf besar: ditolak.
2. Sebagai Ketua, buat draft pelatihan dan ketik nama mitra tersebut. Wilayah kerja otomatis Bali (WITA), dan jam penutupan default 23.59.
3. Buka **Template WA**: baris "Pendaftaran ditutup" menampilkan WITA dengan tanggal yang bergeser, dan WIB dalam kurung.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 399 tes lulus |
| Coverage baris | 98,60% (ambang 80%) |
| Larastan level 8, Pint, uji arsitektur | Bersih |
| Playwright (area terdampak) | 38 lulus, 1 dilewati |
| Resolver area terdampak | 16/16 lulus |

## Area UI terdampak

`@mitra` (baru), `@pelatihan`, `@template-wa`, dan semua area karena layout, route, migrasi, dan provider berubah.

## Keamanan & data pribadi

- **Otorisasi:** gate `manage-partners` dan use case `SavePartner`/`PartnerResolver` memeriksa hak yang sama. Pegawai tanpa hak tidak dapat mendaftarkan mitra, termasuk lewat form pelatihan.
- **Validasi server:**
  - nama mitra ≤ 150 karakter;
  - wilayah enum;
  - jam `JJ:MM` 00:00–23:59;
  - nama duplikat ditolak di Domain dan oleh *unique index*.
- **Data pribadi:** tabel mitra hanya berisi nama organisasi dan wilayah, tanpa kontak pribadi. Hal ini dicatat di `docs/kepatuhan-pdp.md`.
- **Audit log** mencatat pendaftaran dan perubahan mitra (nama dan wilayah lama/baru).
- **CSP:** autocomplete memakai `<datalist>` bawaan HTML, tanpa script.
