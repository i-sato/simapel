## Ringkasan

<!-- Apa yang berubah dan mengapa. Tautkan dokumen perubahan: docs/perubahan/NNNN-*.md -->

## Jenis perubahan

- [ ] Fitur
- [ ] Perbaikan
- [ ] Refactor
- [ ] Infrastruktur / CI
- [ ] Dokumentasi

## Pengujian

- [ ] Unit test untuk setiap perubahan logika (Domain/Application)
- [ ] Feature test untuk perilaku HTTP/CLI yang berubah
- [ ] `make coverage` lulus (ambang 80%)
- [ ] Area UI terdampak (tag Playwright): <!-- mis. @auth, @account -->
- [ ] `make e2e-affected` lulus; `e2e/impact-map.json` diperbarui bila ada halaman/modul baru

## Keamanan & pelindungan data (OWASP / UU PDP)

- [ ] Otorisasi ditegakkan di backend (policy/middleware), bukan hanya di tampilan
- [ ] Tidak ada style/script inline (CSP); input divalidasi di server
- [ ] Data pribadi baru tercatat di docs/kepatuhan-pdp.md (tujuan, dasar pemrosesan, retensi)
- [ ] Tidak ada data pribadi nyata, kredensial, atau `.env` di commit/fixture/log

## Dokumentasi & serah terima

- [ ] `CHANGELOG.md` diperbarui
- [ ] Dokumen perubahan `docs/perubahan/NNNN-*.md` dibuat
- [ ] Dokumen terkait (arsitektur, pengujian, keamanan, proses bisnis) diperbarui
- [ ] Migrasi database ditinjau (aman diulang, tidak menghapus data)
