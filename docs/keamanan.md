# Keamanan

Acuan yang dipakai:
- [OWASP Top 10:2025](https://top10.owasp.org/2025) sebagai daftar risiko minimum;
- OWASP ASVS sebagai checklist verifikasi, dengan **target Level 2** untuk aplikasi pemerintah yang memproses data pribadi;
- Peraturan BSSN No. 4 Tahun 2021 sebagai standar teknis keamanan SPBE yang perlu diselaraskan dengan tim keamanan instansi.

Kepatuhan pelindungan data dibahas di [kepatuhan-pdp.md](kepatuhan-pdp.md).

## Kontrol yang sudah diterapkan, dipetakan ke OWASP Top 10:2025

| Risiko | Kontrol di starter project | Bukti uji |
|---|---|---|
| A01 Broken Access Control | Semua halaman di balik `auth`; tamu dialihkan ke login; logout hanya POST; akun wajib ganti kata sandi awal tidak bisa membuka halaman lain; CSRF + pemeriksaan origin (`PreventRequestForgery`); pengelolaan pegawai, tim, pengaturan, dan ketentuan SOP hanya untuk Super Admin aktif (gate `manage-organization`); hak pelatihan dan penugasan per aksi (`TrainingPermissions`) diperiksa gate dan ditegakkan ulang oleh use case; hak Admin per pelatihan diturunkan dari data penugasan di server; dokumen administrasi hanya diperbarui Sekretaris yang ditugaskan, Ketua tim, atau Super Admin, dan id dokumen diperiksa terhadap pelatihan pada URL (404 bila tidak cocok, mencegah IDOR); Super Admin tidak dapat menurunkan/menonaktifkan diri sendiri dan minimal satu Super Admin aktif dipertahankan | `LoginTest`, `ChangePasswordTest`, `SessionLifecycleTest`, `EmployeeManagementTest`, `WorkTeamTest`, `SettingsTest`, `SopProvisionTest`, `Feature\Training\TrainingTest`, `TrainingAssignmentTest`, `MessageTemplateTest`, `AdministrationTest` |
| A02 Security Misconfiguration | CSP ketat tanpa `unsafe-inline`, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, COOP/CORP, HSTS di HTTPS; `server_tokens off`, `expose_php Off`; dotfile ditolak nginx; port Docker hanya `127.0.0.1`; `DB::prohibitDestructiveCommands()` dan `URL::forceHttps()` di production | `SecurityHeadersTest`, `security.spec.ts` |
| A03 Software Supply Chain Failures | Lock file Composer/npm; `composer audit` di CI; Dependabot; GitHub Actions di-pin ke SHA; versi image di-pin; image rilis dengan SBOM + provenance; gitleaks untuk riwayat git | `ci.yml`, `release-image.yml` |
| A04 Cryptographic Failures | bcrypt 12 rounds; sesi terenkripsi (`SESSION_ENCRYPT=true`); cookie `HttpOnly`, `SameSite=Lax`, `Secure` di production; `APP_KEY` dibuat per lingkungan | konfigurasi `.env.example` |
| A05 Injection | Query lewat Eloquent/parameter binding; Blade auto-escape; validasi Form Request; NIP divalidasi sebagai value object; template WA hanya diganti placeholder daftar putih (lihat bagian Risiko injeksi template); link yang disimpan hanya `http`/`https` (value object `HttpUrl`, menolak `javascript:`/`data:`) dan dibuka dengan `rel="noopener noreferrer"` | `LoginTest`, `NipTest`, `HttpUrlTest` |
| A06 Insecure Design | Aturan bisnis di Domain/Application, ditegakkan di use case, tidak hanya di UI; perubahan data + audit dalam satu transaksi; pemisahan layer diuji otomatis | `LayerDependencyTest`, unit test use case |
| A07 Authentication Failures | Login NIP; pesan gagal seragam (tanpa enumerasi akun); `Auth::attempt` ber-Timebox; batas percobaan per NIP+IP (5/5 menit) dan per IP (20/menit); kebijakan kata sandi (min. 12, huruf besar/kecil, angka, simbol); regenerasi sesi saat login; sesi akun nonaktif dicabut; sesi lain diakhiri saat ganti kata sandi; tanpa *remember me* dan tanpa kredensial bawaan; menonaktifkan akun dan reset kata sandi oleh Super Admin langsung mengakhiri semua sesi akun tersebut; akun hasil impor memakai kata sandi acak yang tidak diketahui siapa pun | `LoginTest`, `PasswordPolicyTest`, `PersistenceTest` |
| A08 Software or Data Integrity Failures | Audit log append-only (update/delete ditolak); seeder E2E ditolak di production; tes menolak database non-`_testing` | `PersistenceTest`, `E2eSeederTest` |
| A09 Security Logging and Alerting Failures | Audit event untuk login berhasil/gagal/dibatasi, logout, sesi dicabut, ganti kata sandi, pembuatan akun; identitas disamarkan; konteks audit menolak kunci sensitif | `AuditEventTest`, `LoginTest` |
| A10 Mishandling of Exceptional Conditions | Pelanggaran aturan bisnis dipetakan ke error formulir tanpa stack trace; halaman galat bertema; data sensitif tidak di-flash kembali ke formulir | `ChangePasswordTest`, `SecurityHeadersTest` |

## Aturan untuk pengembangan berikutnya

- **Tanpa style/script inline.** CSP memblokirnya. Tambahkan kelas di `public/css/simapel.css` dan perilaku di `public/js/simapel.js` (pakai atribut `data-*`). `SecurityHeadersTest` memeriksa seluruh view.
- **Otorisasi di backend.** Setiap route modul memakai policy/middleware; menu hanya menyembunyikan tautan.
- **Batasi akses berdasarkan penugasan.** Setiap query data per-pelatihan wajib dibatasi untuk pengguna yang ditugaskan, dan route model binding diperiksa terhadap induknya untuk mencegah IDOR.
- **Unggahan berkas** (bila nanti disetujui) wajib divalidasi tipe dan ukuran, disimpan di luar `public/`, dan dipindai.
- **Ekspor CSV** wajib menetralkan formula spreadsheet (`=`, `+`, `-`, `@`), seperti `Csv::safe` di referensi.
- **Jangan mencatat data pribadi mentah** di log atau audit. Gunakan nilai tersamar (`Nip::masked()`).
- **Teks buatan pengguna tidak pernah dikompilasi.** Jangan memakai `Blade::render()`, `eval`, `view()->make()` dari string, maupun mesin template lain untuk isi yang ditulis pengguna. Lihat bagian Template WA di bawah.

## Risiko injeksi template (Template WA)

Panitia, Ketua, dan Admin dapat menulis template pesan WA sendiri. Isi itu adalah teks buatan pengguna yang akan diisi data pelatihan, sehingga ada beberapa risiko:

| Risiko | Contoh | Mitigasi | Bukti uji |
|---|---|---|---|
| Server-side template injection / eksekusi kode (A05) | `{{ system('id') }}`, `{!! $x !!}`, `@php … @endphp`, `<?php … ?>` | Isi template **tidak pernah** diproses sebagai Blade/PHP. `TemplateRenderer` hanya memakai `strtr()` untuk token persis dari daftar putih `Placeholder` (mis. `{{nama_pelatihan}}`). Penyimpanan menolak setiap `{{…}}` atau `{!!…!!}` yang bukan placeholder daftar putih, termasuk yang berspasi. Teks seperti `@php` boleh disimpan karena hanya dianggap teks dan tidak pernah dieksekusi | `TemplateRendererTest`, `TemplateBodyTest`, `MessageTemplateTest` |
| Injeksi lewat data | Nama pelatihan berisi `{{link_pendaftaran}}` | `strtr()` mengganti sekali jalan; nilai yang dimasukkan tidak dipindai ulang | `TemplateRendererTest::test_values_are_not_expanded_again` |
| XSS saat pratinjau (A05) | `<script>` di template atau data | Pratinjau dan editor memakai `{{ }}` Blade (escape HTML) di dalam `<textarea>`; tidak ada `{!! !!}` untuk isi template | `MessageTemplateTest` (tag `<?php` tampil ter-escape) |
| Tautan WhatsApp | Teks memecah URL `wa.me` | Teks di-`rawurlencode` lalu di-escape sebagai atribut; tautan memakai `rel="noopener noreferrer"`; tidak ada pengiriman otomatis | `MessageTemplateTest` |
| Akses template orang lain (A01) | Mengubah/reset template milik pegawai lain lewat id | Template dibatasi per pemilik dan per pelatihan; id di luar milik pengguna ditolak 403; akses halaman lewat gate `use-message-templates` dan use case | `MessageTemplateTest`, `MessageTemplateUseCasesTest` |
| Penyalahgunaan penyimpanan | Template sangat panjang atau tak terbatas | Maks. 4.000 karakter dan 3 template per anggota per pelatihan; baris pelatihan dikunci saat menyimpan | `TemplateBodyTest`, `MessageTemplateUseCasesTest` |
| Clipboard tanpa script inline | — | Tombol Salin memakai `data-copy-target` di `public/js/simapel.js` (CSP tetap ketat) | `SecurityHeadersTest`, `template-wa.spec.ts` |

## Pengerasan production (checklist)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL` minimal `warning`
- [ ] HTTPS dengan sertifikat valid; `SESSION_SECURE_COOKIE=true`; HSTS aktif
- [ ] Database: user aplikasi tanpa hak DDL di production; migrasi dijalankan user terpisah
- [ ] `TRUSTED_PROXIES` diisi IP reverse proxy, bila ada
- [ ] Backup terenkripsi dan uji pemulihan berkala; server dan backup berada di Indonesia (PP 71/2019)
- [ ] Pemantauan log keamanan dan alarm, mis. lonjakan `auth.login_throttled`
- [ ] Penetration test / security review sebelum go-live; koordinasi dengan tim keamanan instansi/BSSN

## Celah yang diketahui (rencana)

| Celah | Rencana |
|---|---|
| Belum ada MFA untuk Super Admin | Ditunda untuk MVP 1 (prototipe); wajib dikaji ulang sebelum go-live |
| Belum ada alarm otomatis pada event keamanan | Notifikasi ke admin bila percobaan login dibatasi berulang |
| Retensi audit log belum ditetapkan | Menunggu Jadwal Retensi Arsip (JRA) instansi |
| Pemeriksaan kata sandi bocor (HIBP) tidak aktif | Sengaja dimatikan karena mengirim hash parsial ke layanan luar negeri; aktifkan hanya bila disetujui |
