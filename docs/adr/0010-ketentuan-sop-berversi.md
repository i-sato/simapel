# ADR 0010 — Ketentuan SOP berversi per tim kerja per tahun anggaran

- Status: Diterima (7 November 2026)
- Terkait: keputusan K11 dan K12 di [mvp-1/panduan.md](../mvp-1/panduan.md), [docs/perubahan/0003](../perubahan/0003-ketentuan-sop-berversi.md)

## Konteks

Setiap tim kerja (KDD, ASN Digital, DTA) punya SOP sendiri dengan tenggat administratif yang berbeda, yaitu BAP, SK, ST, dan administrasi siap dalam hari setelah pendaftaran ditutup. SOP berlaku minimal satu tahun anggaran, tetapi bisa direvisi di tengah tahun. Pelatihan yang sudah berjalan tidak boleh ikut bergeser bila ketentuan berubah.

Satu nilai global tidak dapat menyatakan perbedaan antartim. Satu nilai per tim tanpa riwayat juga tidak dapat menyatakan revisi di tengah tahun maupun pergantian tahun.

## Keputusan

1. **Ketentuan (`SopProvision`) dikunci oleh kode tim + tahun anggaran + nomor versi**, bukan oleh baris `work_teams`. Kunci ini membuat resolver dapat mencari lintas tahun meskipun tim kerja dibuat per tahun.
2. **Status Draf → Berlaku → Diganti.**
   - Hanya Draf yang dapat diubah atau dihapus.
   - Pemberlakuan mensyaratkan nomor SOP dan link SOP.
   - Memberlakukan versi baru menandai versi Berlaku sebelumnya di tahun yang sama sebagai Diganti mulai tanggal berlaku versi baru. Tanggal itu wajib lebih akhir daripada versi yang sedang berlaku.
   - Aturan ini berada di `App\Domain\Sop\SopProvision` dan `SopProvisionHistory`. Use case mengunci semua versi tim (`lockForUpdate`) agar dua pemberlakuan bersamaan tidak menghasilkan dua versi Berlaku.
3. **Resolver (`SopProvisionResolver`)** memilih ketentuan untuk tim dan tanggal tertentu:
   - versi Berlaku atau Diganti di tahun yang sama dengan tanggal mulai berlaku ≤ tanggal acuan, diambil yang terakhir;
   - bila tidak ada, dipakai ketentuan tahun sebelumnya yang terakhir (*fallback*), dan Super Admin melihat badge "Ketentuan 20XX belum ditetapkan";
   - bila tetap tidak ada, hasilnya kosong dan badge "SOP belum dilampirkan" tampil.

   Versi Diganti tetap dipakai untuk tanggal sebelum revisi, sehingga pelatihan yang dihitung ulang dengan tanggal lamanya tetap memakai ketentuan yang benar.
4. **Ketentuan yang sudah dirujuk pelatihan dikunci.** Port `SopProvisionRepository::isReferenced()` sudah dipakai oleh use case ubah dan hapus. Implementasinya mengembalikan `false` sampai relasi pelatihan dibuat di langkah 3.
5. **Pelatihan menyimpan salinan ketentuan saat difinalisasi** (langkah 3). Revisi berikutnya tidak menggeser pelatihan yang sudah final.
6. **"Salin dari tahun sebelumnya"** membuat Draf baru dari ketentuan yang berlaku pada 31 Desember tahun sebelumnya, dengan tanggal mulai 1 Januari. Draf hasil salinan tetap harus ditinjau dan diberlakukan secara manual.
7. **Draf awal 2026** dibuat oleh seeder idempoten `SopProvisionDraftSeeder` dari transkripsi tiga SOP, tanpa link SOP dan tanpa pemberlakuan otomatis. Nilai yang tidak tertulis eksplisit di SOP diberi catatan "Perlu konfirmasi".

## Alternatif yang ditolak

- **Satu nilai global**: tidak sesuai karena tiap tim punya SOP sendiri.
- **Satu nilai per tim tanpa versi**: revisi di tengah tahun akan menggeser pelatihan berjalan dan riwayat hilang.
- **Ketentuan terikat ke baris `work_teams` per tahun**: fallback lintas tahun menjadi rumit, karena tim tahun baru belum tentu dibuat saat ketentuan dibutuhkan.

## Konsekuensi

- Langkah 3 menambah relasi pelatihan → ketentuan dan menyalin nilai tenggat saat finalisasi. `isReferenced()` diisi dengan pemeriksaan relasi tersebut, dan feature test "ketentuan yang dipakai pelatihan tidak dapat diubah" ditambahkan di sana.
- Pengingat tenggat di langkah 6 membaca salinan pada pelatihan, bukan ketentuan terkini.
- Super Admin perlu meninjau dan memberlakukan ketentuan tiap awal tahun anggaran. Badge peringatan menjadi pengingatnya.
