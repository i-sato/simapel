# ADR 0008 — Model peran berlapis

- Status: Diterima (8 November 2026)

## Konteks

Problem statement ("Simapel Business Story" + Additional Features V.1) dan keputusan K1–K4 menetapkan:
- **Super Admin** dapat mengakses seluruh fitur.
- **Tim kerja** (KDD, ASN Digital, DTA) per tahun, masing-masing dengan Ketua, Sekretaris, dan Anggota. Pegawai dapat berada di lebih dari satu tim.
- **Admin** adalah sebagian pegawai yang ditandai Super Admin, lalu ditugaskan per pelatihan.
- **Panitia** dipilih per pelatihan, lintas tim.

Starter memakai tiga peran global (`super_admin_regional`, `admin_regional`, `pegawai`). Model itu tidak dapat menyatakan peran yang bergantung pada tim maupun pelatihan.

## Keputusan

Hak akses dibentuk dari empat lapis yang terpisah:

| Lapis | Tempat | Nilai |
|---|---|---|
| Peran global | `users.role`, enum `App\Domain\Identity\Role` | `super_admin`, `pegawai` |
| Peran di tim kerja | `team_memberships.role`, enum `App\Domain\Organization\TeamRole` | `ketua`, `sekretaris`, `anggota` |
| Kapabilitas | `users.can_be_admin` | dapat ditugaskan sebagai Admin pelatihan |
| Penugasan per pelatihan | Langkah 4 MVP-1 | Admin, Panitia |

- Aturan susunan tim berada di `App\Domain\Organization\TeamComposition`:
  - satu peran per pegawai per tim;
  - Ketua dan Sekretaris masing-masing maksimal satu orang.
- Tim baru dimulai tanpa Ketua dan ditandai "Ketua belum ditetapkan". Karena itu "tepat satu Ketua" ditegakkan sebagai maksimal satu ditambah peringatan, agar susunan bisa diisi bertahap.
- Perubahan susunan tim mengunci baris tim (`lockForUpdate`) sehingga dua permintaan bersamaan tidak menghasilkan dua Ketua.
- Pengelolaan pegawai dan tim memakai gate `manage-organization` (Super Admin aktif). Daftar tim dan anggotanya dapat dilihat semua pegawai tanpa NIP.
- Migrasi data mengubah `super_admin_regional` menjadi `super_admin`. `admin_regional` diubah menjadi `pegawai` dengan `can_be_admin = true` agar haknya tidak hilang.
- Pengaman akses:
  - Super Admin tidak dapat menurunkan peran atau menonaktifkan akunnya sendiri;
  - harus selalu ada minimal satu Super Admin aktif.

## Konsekuensi

- Policy pelatihan di langkah berikutnya menggabungkan keempat lapis, misalnya "Ketua tim pelatihan tersebut atau Super Admin".
- Penghapusan `admin_regional` mengubah nilai `--role` pada `simapel:user:create` menjadi `super_admin | pegawai`.
