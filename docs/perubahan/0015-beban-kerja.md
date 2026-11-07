# 0015 — Pemetaan beban kerja (MVP-1 langkah 7)

- Branch: `feat/beban-kerja`
- Tanggal: 7 November 2026
- Acuan: [docs/mvp-1/panduan.md](../mvp-1/panduan.md) langkah 7, K8, K9, business story ("melihat pemetaan beban kerja/tugas pegawai/tim kerja")

## Ringkasan

Menu **Beban Kerja** untuk semua pegawai (K9):
- **Matriks pegawai × periode** (minggu Senin–Minggu atau bulan kalender).
  - Isi sel: jumlah pelatihan yang ditangani pegawai pada periode itu, beserta tanda ⚠ bila ada bentrok.
  - Warna sel memakai kelas CSS baru (`load-0` s.d. `load-3`, `load-conflict`) dengan token warna tema, tanpa style inline.
  - Kolom pegawai tetap terlihat saat tabel digulir.
- **Filter:**
  - rentang tanggal: bawaan bulan berjalan + 2 bulan, maksimal 12 bulan;
  - tampilan mingguan atau bulanan;
  - tim kerja.
- **Klik nama pegawai** untuk melihat daftar penugasannya (pelatihan, tim, peran, rentang, tanda bentrok).
- **Rentang setiap pelatihan** mengikuti aturan langkah 4: awal pendaftaran sampai akhir pelatihan, inklusif.

## Aturan perhitungan

| Hal | Aturan |
|---|---|
| Beban | Admin/Panitia dari penugasan, ditambah Ketua/Sekretaris dari pelatihan timnya |
| Jumlah per sel | Pelatihan **berbeda** yang rentangnya beririsan dengan periode. Admin sekaligus Panitia pada satu pelatihan dihitung satu |
| Bentrok | Dua penugasan **Admin/Panitia** pada pelatihan berbeda saling beririsan, dan irisannya jatuh di periode tersebut (sama dengan langkah 4) |
| Ketua/Sekretaris | Menambah beban, tetapi **tidak** dinilai bentrok, karena Ketua memang membawahi beberapa pelatihan timnya sekaligus |
| Filter tim | Hanya pelatihan tim tersebut yang dihitung. Baris berisi anggota tim ditambah pegawai lintas tim yang bertugas pada pelatihan tim itu |
| Baris | Semua pegawai aktif (yang tanpa tugas ikut tampil dengan 0), diurutkan dari yang paling sibuk |

## Isi perubahan

| Lapisan | Perubahan |
|---|---|
| Domain | `Workload\Granularity`, `Period`, `PeriodGrouping` (minggu/bulan, rentang bawaan, batas 60 kolom), `WorkloadRole`, `WorkloadItem`, `WorkloadCell`, `WorkloadMatrix`, `InvalidWorkloadRange` |
| Application | Port `WorkloadRepository`; `WorkloadMap` (validasi rentang, matriks, detail pegawai); `WorkloadEmployee` |
| Infrastructure | `EloquentWorkloadRepository`: jumlah query tetap (satu untuk penugasan, satu untuk peran Ketua/Sekretaris, satu untuk pegawai) |
| Presentation | `WorkloadController`, view `workload/index` dan `workload/show`, menu "Beban Kerja", kelas CSS intensitas dan kolom lengket; `.table-wrap` kini `position: relative` agar teks `.sr-only` di dalam tabel tidak melebarkan halaman di ponsel |

## Kinerja

Feature test membuat **100 pegawai × 50 pelatihan** sintetis (200 penugasan + Ketua/Sekretaris). Jumlah query halaman Beban Kerja **sama persis** dengan saat data kosong, dan ≤ 12 query termasuk sesi dan autentikasi. Mode ketat Eloquent aktif, sehingga *lazy loading* akan gagal.

## Keputusan implementasi (mohon dikonfirmasi)

1. **Ketua/Sekretaris** dihitung sebagai beban pada semua pelatihan timnya, tetapi tidak menimbulkan tanda bentrok.
2. **Jumlah** dihitung per pelatihan, bukan per peran.
3. **Filter tim** menghitung pelatihan tim itu saja, dan tetap menampilkan Panitia lintas tim yang terlibat.
4. **Rentang maksimal 12 bulan**, dan tampilan mingguan dibatasi 60 kolom agar halaman tetap ringan.

## Cara menguji

```sh
make ci
```

Uji manual: buka **Beban Kerja**, ganti tampilan Mingguan/Bulanan dan filter tim, lalu klik nama pegawai. Di ponsel, halaman tidak bergulir ke samping; hanya tabel yang dapat digulir.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit 13 (MySQL 8.4) | 413 tes lulus |
| Coverage baris | 98,67% (ambang 80%) |
| Larastan level 8, Pint, uji arsitektur | Bersih |
| Playwright (area terdampak, termasuk viewport ponsel) | 40 lulus, 1 dilewati |
| Resolver area terdampak | 17/17 lulus |

## Area UI terdampak

`@beban-kerja` (baru), dan semua area karena route, provider, layout navigasi, dan CSS berubah.

## Keamanan & data pribadi

- **Akses:** halaman hanya untuk pegawai yang login (K9: semua pegawai melihat beban kerja). Tidak ada aksi ubah data.
- **Data yang ditampilkan** hanya nama, jabatan, pelatihan, peran, dan rentang tanggal, tanpa NIP. Ini sejalan dengan baris "Penugasan pegawai pada pelatihan" di `docs/kepatuhan-pdp.md`.
- **Validasi server:** tanggal `Y-m-d`, rentang ≤ 12 bulan, tampilan dan tim berupa enum.
- **CSP:** warna sel memakai kelas CSS, tanpa style inline.
