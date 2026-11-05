# Kepatuhan pelindungan data pribadi

> Dokumen ini adalah **acuan kerja tim teknis**, bukan pendapat hukum. Penerapan dan interpretasi pasal wajib dikonfirmasi oleh unit hukum dan Pejabat/Petugas Pelindungan Data Pribadi (PPDP) instansi sebelum go-live.

## Regulasi acuan

| Regulasi | Relevansi untuk SIMAPEL |
|---|---|
| UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP) | Prinsip pemrosesan, dasar pemrosesan, hak subjek data, keamanan, notifikasi kegagalan pelindungan, PPDP |
| PP No. 71 Tahun 2019 tentang Penyelenggaraan Sistem dan Transaksi Elektronik | SIMAPEL adalah sistem elektronik lingkup publik: pengelolaan dan penyimpanan data di wilayah Indonesia |
| UU No. 11 Tahun 2008 tentang ITE beserta perubahannya (UU 19/2016, UU 1/2024) | Keandalan dan keamanan sistem elektronik |
| Perpres No. 95 Tahun 2018 tentang Sistem Pemerintahan Berbasis Elektronik (SPBE) | Tata kelola aplikasi pemerintah |
| Peraturan BSSN No. 4 Tahun 2021 | Pedoman manajemen keamanan informasi dan standar teknis keamanan SPBE |
| Permenkominfo No. 4 Tahun 2016 tentang Sistem Manajemen Pengamanan Informasi | Pengelolaan keamanan informasi |
| UU No. 43 Tahun 2009 tentang Kearsipan | Retensi dan pemusnahan arsip, termasuk audit log dan dokumen bukti |

## Pemetaan kewajiban UU PDP ke sistem

Rujukan pasal di bawah perlu diverifikasi tim hukum.

| Kewajiban | Penerapan di SIMAPEL | Status |
|---|---|---|
| Prinsip pemrosesan: terbatas, spesifik, sesuai tujuan (Pasal 16) | Hanya field minimum: NIP, nama, email opsional, peran. NIK peserta tidak dikumpulkan (keputusan referensi dipertahankan) | Diterapkan |
| Dasar pemrosesan (Pasal 20) | Usulan: pelaksanaan tugas/kewenangan instansi untuk data pegawai & peserta | **Perlu konfirmasi** |
| Transparansi | Pemberitahuan di halaman masuk bahwa waktu akses, IP, dan perangkat dicatat untuk keamanan | Diterapkan (teks final perlu ditinjau) |
| Hak subjek data: akses, pembaruan, penghapusan (Pasal 5–13) | Prosedur lewat Super Admin; fitur ekspor/perbaikan data per subjek direncanakan | Direncanakan |
| Keberatan atas keputusan otomatis (Pasal 10) | Kelulusan otomatis di referensi **tidak** dibawa; assessment ditunda sampai proses bisnis jelas | Diterapkan (ditunda) |
| Penilaian dampak (Pasal 34) | Wajib sebelum memproses data peserta skala besar atau keputusan otomatis | **Perlu dilakukan** sebelum modul peserta |
| Keamanan dan kerahasiaan (Pasal 35–39) | Lihat [keamanan.md](keamanan.md): kontrol akses, enkripsi sesi, hash kata sandi, audit, minimisasi tampilan (NIP tersamar) | Diterapkan (fondasi) |
| Notifikasi kegagalan pelindungan maks. 3×24 jam (Pasal 46) | Butuh prosedur insiden. Audit log menyediakan jejak untuk investigasi | **Prosedur perlu disusun** |
| Pejabat/Petugas PDP (Pasal 53) | Instansi pelayanan publik: tunjuk PPDP dan cantumkan kontaknya | **Perlu konfirmasi** |
| Transfer data ke luar negeri (Pasal 56) | Data production tidak dikirim ke luar negeri. GitHub hanya memuat kode dan data uji sintetis | Diterapkan |

## Inventaris data pribadi

| Data | Lokasi | Tujuan | Retensi | Kontrol |
|---|---|---|---|---|
| NIP, nama, email (opsional), peran | `users` | Autentikasi & otorisasi | Selama akun dibutuhkan + masa arsip (**JRA**) | NIP tersamar di tampilan & audit; tidak bisa dipakai lewat email |
| Hash kata sandi | `users.password` | Autentikasi | Selama akun ada | bcrypt; tidak pernah ditampilkan, di-log, atau di-flash |
| Alamat IP, user agent, waktu akses | `sessions`, `audit_logs` | Keamanan & jejak audit | Sesi: maks. lama sesi. Audit: **JRA** (usulan 2 tahun) | Audit append-only; akses dibatasi Super Admin (modul audit direncanakan) |
| Data peserta pelatihan | Belum ada | Administrasi pelatihan | — | Menunggu data dukung & penilaian dampak |

## Aturan teknis yang wajib dipatuhi

1. **Data uji selalu sintetis.** NIP uji diawali `9999`. Data nyata, cuplikan database production, dan file `.env` tidak boleh masuk repository, fixture, tiket, atau artefak CI.
2. **Hosting di Indonesia.** Server production dan backup berada di Indonesia (PP 71/2019). Layanan luar negeri yang menerima data pribadi tidak boleh dipakai tanpa persetujuan, termasuk analitik, error tracking, dan pemeriksa kata sandi bocor.
3. **Data pribadi baru wajib didokumentasikan** di tabel inventaris (tujuan, dasar, retensi, kontrol) dalam PR yang sama.
4. **Log dan audit memakai nilai tersamar.** Konteks audit menolak kunci `password`, `token`, `secret`, `nip`, dan `nik` secara otomatis.
5. **Minimalkan tampilan.** Daftar dan ekspor hanya memuat kolom yang dibutuhkan peran pengguna.

## Keputusan yang dibutuhkan dari instansi

- Dasar pemrosesan resmi untuk data pegawai dan peserta.
- Penunjukan PPDP dan teks pemberitahuan privasi final.
- Jadwal retensi (JRA) untuk akun, audit log, dan data peserta.
- Prosedur penanganan insiden dan notifikasi 3×24 jam.
- Kebutuhan penilaian dampak untuk modul peserta.
