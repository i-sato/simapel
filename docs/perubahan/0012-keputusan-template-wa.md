# 0012 — Keputusan Template WA (K16)

- Branch: `docs/keputusan-template-wa`
- Tanggal: 7 November 2026
- Acuan: pertanyaan di catatan [0011](0011-template-wa.md), K1 dan K16 di [mvp-1/panduan.md](../mvp-1/panduan.md)

## Ringkasan

Jawaban pemilik proyek atas tiga pertanyaan di catatan 0011. Ketiganya sesuai dengan perilaku yang sudah berjalan, sehingga **tidak ada perubahan kode**.

| No | Pertanyaan | Keputusan | Perilaku saat ini |
|---|---|---|---|
| 1 | Apakah Super Admin dikecualikan dari Template WA | Tidak; Super Admin dapat bertugas layaknya anggota tim kerja | `TrainingPermissions::canUseMessageTemplates()` mengizinkan Super Admin |
| 2 | Makna "bagian akhir" untuk kata "gratis" | 5 baris berisi terakhir sudah sesuai | `TemplateBody::CLOSING_LINES = 5` |
| 3 | Link panduan yang wajib | Link panduan pendaftaran pelatihan tim kerja | `{{link_panduan_pelatihan}}` wajib; `{{link_panduan_dts}}` opsional |

## Isi perubahan

| File | Perubahan |
|---|---|
| `docs/mvp-1/panduan.md` | K16 ditambahkan |
| `docs/serah-terima.md` | Keputusan 11 ditambahkan; pertanyaan terbuka "Template WA" ditutup |
| `CHANGELOG.md` | Entri dokumentasi |

## Cara menguji

Hanya dokumentasi; tidak ada perubahan kode maupun UI.

## Keamanan & data pribadi

Tidak ada perubahan.
