# Usulan aturan tambahan untuk AI agent

> Status: **menunggu keputusan pemilik proyek**. Aturan di bawah belum berlaku. Aturan yang disetujui akan dipindahkan ke `AGENTS.md` dan berlaku untuk developer maupun agent.

| ID | Usulan | Manfaat | Biaya / catatan |
|---|---|---|---|
| R1 | **Conventional Commits** (`feat:`, `fix:`, `docs:` …) dan nama branch berawalan sama | Riwayat mudah dibaca; CHANGELOG/rilis bisa semi-otomatis | Disiplin pesan commit |
| R2 | **Satu topik per PR, ukuran ±400 baris** (di luar file generated/lock) | Review lebih teliti, risiko regresi lebih kecil | Fitur besar dipecah menjadi beberapa PR |
| R3 | **Glosarium istilah domain** (pelatihan = training, tahap = stage, keluaran = output, …) di `docs/` | Penamaan kode konsisten antara tim dan agent | Perlu dirawat |
| R4 | **Dependensi baru wajib justifikasi**: lisensi permisif, `composer audit` bersih, aktif dirawat, dan ADR bila signifikan | Mengurangi risiko rantai pasok (OWASP A03) | Sedikit lebih lambat menambah paket |
| R5 | **Migrasi yang sudah di-merge tidak boleh diubah**; migrasi destruktif wajib rencana backup/rollback dan persetujuan | Mencegah kehilangan data | Butuh migrasi korektif terpisah |
| R6 | **MFA (TOTP) wajib** untuk Super Admin/Admin Regional — **ditunda** (keputusan 4 Nov 2026: fokus MVP 1 prototipe) | Sesuai praktik ASVS L2 untuk akun berhak tinggi | Dikaji ulang sebelum go-live |
| R7 | **Aksesibilitas WCAG 2.1 AA** untuk halaman baru, diuji otomatis dengan axe-core di Playwright | Layanan publik lebih inklusif; regresi aksesibilitas tertangkap | Tambah paket `@axe-core/playwright` |
| R8 | **Threat model singkat (STRIDE)** di dokumen perubahan untuk modul yang memproses data pribadi | Risiko keamanan dipikirkan sejak desain (OWASP A06) | ±30 menit per modul |
| R9 | **Laravel Boost** (MCP resmi Laravel) untuk agent | Agent mendapat panduan sesuai versi Laravel dan akses ke dokumentasi, log, dan skema lokal | Dev dependency baru; agent dapat membaca database lokal (hanya data sintetis) |
| R10 | **Pre-commit hook** opsional (`make hooks`: Pint + unit test cepat) | Kesalahan gaya/unit tertangkap sebelum commit | Commit sedikit lebih lambat |
| R11 | **Rilis bertag Semantic Versioning** (`vX.Y.Z`) dengan catatan dari CHANGELOG | Jelas versi mana yang di-deploy; memicu `release-image.yml` | Perlu penanggung jawab rilis |
| R12 | **Agent wajib menyebut sumber SOP** (nomor SOP dan tahap) di PR yang menyentuh aturan bisnis | Keterlacakan ke dokumen resmi (TOR 4.1) | — |
