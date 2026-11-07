# 0003 — Ketentuan SOP berversi per tim kerja per tahun anggaran

- Branch: `docs/ketentuan-tenggat-sop`
- Tanggal: 8 November 2026

## Ringkasan

Sebelumnya durasi kritis administratif diatur sebagai satu nilai global. Keputusan pemilik proyek (8 November 2026) menggantinya karena dua alasan: SOP berlaku minimal satu tahun anggaran, dan tiap tim kerja punya SOP dengan waktu yang berbeda. Penggantinya:

- **ketentuan SOP berversi per tim kerja per tahun anggaran**, berisi acuan SOP dan tenggat BAP, SK, ST, serta administrasi siap;
- status Draf → Berlaku → Diganti, dengan revisi di tengah tahun dibuat sebagai versi baru;
- **salinan ketentuan disimpan pada pelatihan** saat difinalisasi, sehingga perubahan berikutnya tidak menggeser pelatihan berjalan;
- fallback ke ketentuan tahun sebelumnya, disertai badge "Ketentuan 20XX belum ditetapkan";
- draf 2026 per tim diisi dari transkripsi tiga SOP; nilai yang tidak tertulis eksplisit di SOP ditandai "perlu konfirmasi".

## Isi perubahan

| File | Perubahan |
|---|---|
| `docs/mvp-1/panduan.md` | K11 dan K12 diperbarui; langkah 2 (ketentuan berversi, draf 2026 per tim, resolver, ADR 0010), langkah 3 (salinan saat finalisasi), langkah 6 (tenggat dari salinan), langkah 8 (data demo), dan masukan yang dibutuhkan |
| `docs/serah-terima.md`, `CHANGELOG.md` | Ringkasan rencana dan pertanyaan terbuka disesuaikan |

## Cara menguji

Hanya dokumentasi; tidak ada perubahan kode.

## Keamanan & data pribadi

Tidak ada data pribadi.
