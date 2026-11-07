# Matriks hak akses (MVP-1)

Hak akses dibentuk dari empat lapis: peran global, peran di tim kerja, kapabilitas Admin, dan penugasan per pelatihan (ADR 0008). Aturannya berada di Domain (`TrainingPermissions`, `DocumentPermissions`, `PartnerPermissions`). Aturan yang sama dipakai di dua tempat:
1. **gate pada route**, sehingga pengguna tanpa hak mendapat 403;
2. **use case** di lapisan Application, yang memeriksa ulang sebelum menyimpan data.

Menu dan tombol hanya menyesuaikan diri dengan hasil pemeriksaan tersebut. Menyembunyikan menu bukan kontrol keamanan.

Keterangan: ✓ boleh · — tidak boleh · *timnya* = hanya pelatihan tim yang ia pimpin · *ditugaskan* = hanya pelatihan tempat ia ditugaskan · *miliknya* = hanya data miliknya sendiri.

| Fitur / aksi | Pegawai | Ketua tim | Sekretaris tim | Admin | Panitia | Super Admin | Ditegakkan oleh |
|---|---|---|---|---|---|---|---|
| Masuk, ganti kata sandi sendiri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | middleware `auth` + `EnsureAccountIsActive` (grup `web`) |
| Dashboard, daftar & detail pelatihan | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | `auth` (K9) |
| Beban Kerja, Tim Kerja, daftar Mitra | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | `auth` (K9) |
| Kelola pegawai, impor CSV | — | — | — | — | — | ✓ | gate `manage-organization` |
| Atur anggota tim kerja | — | — | — | — | — | ✓ | gate `manage-organization` |
| Pengaturan umum, link panduan tim, ketentuan SOP | — | — | — | — | — | ✓ | gate `manage-organization` |
| Tambah/ubah mitra | — | ✓ (Ketua tim mana pun) | — | ✓ (semua pegawai bertanda Admin) | — | ✓ | gate `manage-partners` + `SavePartner` / `PartnerResolver` |
| Buat draft pelatihan | — | ✓ *timnya* | — | — | — | ✓ | gate `create-training` + `CreateTrainingDraft` |
| Ubah draft pelatihan | — | ✓ *timnya* | — | ✓ *ditugaskan* | — | ✓ | gate `update-training` + `UpdateTraining` |
| Finalisasi pelatihan | — | — | — | ✓ *ditugaskan* | — | ✓ | gate `finalize-training` + `FinalizeTraining` |
| Lengkapi pelatihan Final (mis. link daftar peserta) | — | ✓ *timnya* | — | ✓ *ditugaskan* | — | ✓ | gate `update-training` + `UpdateTraining` (K13) |
| Atur penugasan Admin/Panitia | — | ✓ *timnya* | — | — | — | ✓ | gate `manage-training-assignments` + `UpdateTrainingAssignments` |
| Template WA: lihat, salin, buka WhatsApp | — | ✓ *timnya* | — | ✓ *ditugaskan* | ✓ *ditugaskan* | ✓ | gate `use-message-templates` + `TrainingMessages` (K9, K16) |
| Template WA kustom: buat/ubah/reset | — | ✓ *miliknya* | — | ✓ *miliknya* | ✓ *miliknya* | ✓ *miliknya* | `SaveCustomTemplate`, `ResetCustomTemplate` |
| Delegasikan dokumen BAP/SK/ST | — | ✓ *timnya* | — | — | — | ✓ | `DelegateDocument` |
| Perbarui dokumen BAP/SK/ST | — | ✓ *timnya* | ✓ dokumen yang didelegasikan kepadanya | — | — | ✓ | `UpdateDocument` (+ cek dokumen milik pelatihan pada URL) |
| Buat akun lewat CLI | Operator server dengan akses container (`simapel:user:create`), di luar aplikasi web | | | | | | |

Catatan penting:
- **Admin yang ditugaskan** hanya dihitung bila pegawai tersebut masih bertanda "dapat menjadi Admin". Mencabut tanda itu langsung mencabut hak Admin-nya di semua pelatihan.
- **Ketua** pada kolom *timnya* berarti Ketua tim kerja penyelenggara pelatihan. Ketua tidak dapat memindahkan pelatihan ke tim yang tidak dipimpinnya, dan tidak dapat memfinalisasi (K5).
- **Mendaftarkan mitra baru dari form pelatihan** memerlukan dua hak sekaligus: hak mengubah pelatihan tersebut dan hak mengelola mitra.
- **Akun nonaktif** tidak dapat melakukan apa pun; sesi aktifnya dicabut.
- **Akun yang wajib mengganti kata sandi** hanya dapat membuka halaman ganti kata sandi (middleware `password.change-required`).
- Id dokumen, template, dan penugasan selalu diperiksa terhadap pelatihan dan pemiliknya di server, untuk mencegah IDOR.
