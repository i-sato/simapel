# 0004 — Model peran, pegawai, dan tim kerja (MVP-1 langkah 1)

- Branch: `feat/peran-pegawai-tim`
- Tanggal: 8 November 2026
- Acuan: [docs/mvp-1/panduan.md](../mvp-1/panduan.md) langkah 1, keputusan K1–K4, [ADR 0008](../adr/0008-model-peran-berlapis.md)

## Ringkasan

Fondasi organisasi MVP-1:
- **Peran global** Super Admin dan Pegawai. `admin_regional` dihapus lewat migrasi data.
- **Profil pegawai** sesuai template daftar pegawai: Pangkat/Gol. Ruang dan Jabatan, ditambah kapabilitas "dapat menjadi Admin".
- **Tim kerja** KDD, ASN Digital, dan DTA per tahun dengan Ketua/Sekretaris/Anggota. Data tiga tim 2026 dibuat lewat migrasi, tanpa data pegawai.
- **Halaman Pegawai** (khusus Super Admin) dan **halaman Tim Kerja** (semua pegawai dapat melihat, hanya Super Admin yang mengubah).

## Isi perubahan

| Lapisan | Perubahan |
|---|---|
| Domain | `Role` (super_admin, pegawai), `EmployeeProfile`, `Organization\TeamComposition`, `TeamRole`, `WorkTeamCode`, event audit baru |
| Application | `UpdateEmployee`, `SetEmployeeActive`, `ResetEmployeePassword`, `ImportEmployees`, `AssignTeamMember`, `RemoveTeamMember`, port `WorkTeamRepository` |
| Infrastructure | Perluasan `EloquentUserAccountRepository` (termasuk lock Super Admin terakhir), `EloquentWorkTeamRepository` (lock baris tim), model `WorkTeam`, `TeamMembership` |
| Database | Migrasi `add_employee_profile_and_simplify_roles` (konversi peran lama) dan `create_work_teams_tables` (+ data 2026) |
| Presentation | Gate `manage-organization`, halaman Pegawai/Impor/Tim Kerja, menu "Tim Kerja" dan "ADMINISTRASI › Pegawai", partial paginasi bertema, `EmployeeCsvReader` (koma/titik koma, BOM) |
| Lingkungan | `compose.yaml` kembali ke `mysql:8.4` dan Dependabot mengabaikan seri MySQL ≥ 9 (lihat catatan di bawah) |

## Keputusan implementasi

- **Aturan Ketua dan Sekretaris.** Prompt meminta "tepat satu Ketua dan maksimal satu Sekretaris". Karena tim baru dimulai kosong, aturan ditegakkan sebagai maksimal satu Ketua dan maksimal satu Sekretaris, disertai peringatan "Ketua belum ditetapkan" di daftar dan detail tim. Untuk mengganti Ketua, ubah Ketua lama menjadi Anggota terlebih dahulu.
- **Akun hasil impor.** Akun dibuat dengan kata sandi acak yang tidak diketahui siapa pun, sehingga pegawai baru dapat login setelah Super Admin menetapkan kata sandi sementara di halaman Ubah. Pengiriman kata sandi lewat email tidak tersedia, sesuai keputusan "notifikasi email ditunda".
- **Data pribadi di daftar tim.** Daftar tim untuk semua pegawai hanya menampilkan nama, jabatan, dan peran, tanpa NIP.
- **MySQL 26.7 dari Dependabot.** PR Dependabot #2 menaikkan MySQL di `compose.yaml` ke 26.7, sementara CI dan ADR 0002 tetap 8.4. Data lokal 8.4 tidak dapat dibuka 26.7, sehingga lingkungan Docker tidak bisa berjalan. Versi dikembalikan ke 8.4. Upgrade seri mayor sebaiknya diputuskan lewat ADR baru dan diuji bersama CI.

## Cara menguji

```sh
make migrate      # dua migrasi baru
make ci           # lint, Larastan, coverage, uji UI terdampak
```

Uji manual:
1. Masuk sebagai Super Admin, buka **Pegawai**, lalu tambah pegawai.
2. Impor CSV dengan header `nama,nip,pangkat_golongan,jabatan,email`.
3. Buka **Tim Kerja**, lalu atur Ketua/Sekretaris/Anggota.
4. Masuk sebagai pegawai biasa dan pastikan menu Pegawai tidak tampil serta Tim Kerja hanya dapat dilihat.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 161 tes lulus |
| Coverage baris | 97,70% (ambang 80%) |
| Larastan level 8, Pint | Bersih |
| Playwright | 22/22 lulus (semua area karena layout/middleware/migrasi berubah) |
| Resolver area terdampak | 10/10 lulus |

## Area UI terdampak

`@setup`, `@auth`, `@account`, `@dashboard`, `@layout`, `@security`, `@errors`, `@pegawai`, `@tim-kerja`. Semua area diuji karena layout, navigasi, dan migrasi berubah.

## Keamanan & data pribadi

- Data pribadi baru (pangkat/golongan, jabatan, keanggotaan tim) sudah tercatat di `docs/kepatuhan-pdp.md`.
- Otorisasi ditegakkan di backend dengan gate `manage-organization`. Super Admin tidak dapat menurunkan atau menonaktifkan dirinya sendiri, dan minimal satu Super Admin aktif dipertahankan dengan lock.
- Menonaktifkan akun dan reset kata sandi mengakhiri semua sesi akun tersebut.
- Audit log mencatat semua perubahan pegawai, impor, dan keanggotaan dengan NIP tersamar.
