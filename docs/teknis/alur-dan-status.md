# Alur proses dan diagram status

Diagram di bawah menggambarkan perilaku SIMAPEL 0.1.0. Aturan rincinya ada di Domain dan ADR terkait.

## 1. Alur proses MVP-1 (gambaran besar)

```mermaid
flowchart TD
    A["Super Admin: tetapkan ketentuan SOP tim<br/>(Draf → Berlakukan)"] --> B["Ketua tim: buat draft pelatihan<br/>(boleh bertahap)"]
    B --> C["Ketua tim: atur penugasan Admin & Panitia<br/>(alasan bila bentrok)"]
    C --> D["Admin / Super Admin: lengkapi & finalisasi"]
    D --> E["Sistem: salin ketentuan SOP<br/>+ buat dokumen BAP/SK/ST"]
    E --> F["Panitia: bagikan Template WA<br/>ke grup peserta"]
    E --> G["Ketua: delegasikan dokumen<br/>ke Sekretaris tim"]
    G --> H["Sekretaris: perbarui dokumen<br/>sampai Selesai"]
    D --> I["Admin: isi link daftar peserta<br/>setelah pendaftaran ditutup"]
    F & H & I --> J["Semua pegawai: pantau dashboard,<br/>peringatan, dan beban kerja"]
```

## 2. Status pelatihan

### Status administrasi (disimpan)

```mermaid
stateDiagram-v2
    [*] --> Draft: Ketua/Super Admin membuat
    Draft --> Draft: diubah (boleh belum lengkap)
    Draft --> Final: Finalisasi (data lengkap)
    Final --> Final: dilengkapi (tetap lengkap,<br/>tim & salinan SOP tetap)
    note right of Final
        Tidak dapat kembali ke Draft.
        Saat finalisasi: salinan ketentuan SOP
        disimpan dan BAP/SK/ST dibuat.
    end note
```

### Status waktu (dihitung dari tanggal, tidak disimpan)

```mermaid
stateDiagram-v2
    direction LR
    [*] --> AkanDibuka: hari ini < awal pendaftaran
    AkanDibuka --> Pendaftaran: awal pendaftaran
    Pendaftaran --> PenetapanPeserta: setelah akhir pendaftaran
    PenetapanPeserta --> Pelaksanaan: awal pelatihan
    Pelaksanaan --> Selesai: setelah akhir pelatihan
    Selesai --> [*]
```

Pelatihan yang keempat tanggalnya belum lengkap tampil "Jadwal belum lengkap".

## 3. Status ketentuan SOP (ADR 0010)

```mermaid
stateDiagram-v2
    [*] --> Draf: buat / salin dari tahun sebelumnya
    Draf --> Draf: diubah
    Draf --> [*]: dihapus
    Draf --> Berlaku: Berlakukan (wajib nomor + link SOP)
    Berlaku --> Diganti: versi baru diberlakukan<br/>(mulai tanggal berlaku versi baru)
    Diganti --> [*]
```

Resolver memilih ketentuan untuk (tim, tanggal) sebagai berikut:
1. Versi Berlaku atau Diganti pada tahun yang sama dengan tanggal mulai berlaku ≤ tanggal tersebut; yang terakhir dipilih.
2. Bila tidak ada, dipakai versi terakhir tahun sebelumnya (fallback), disertai badge "Ketentuan 20XX belum ditetapkan".
3. Bila tetap tidak ada, hasilnya kosong dan badge "SOP belum dilampirkan" tampil.

## 4. Status dokumen administrasi

```mermaid
stateDiagram-v2
    [*] --> Belum: dibuat saat finalisasi
    Belum --> DalamProses: diperbarui
    Belum --> Selesai: nomor + tanggal + link diisi
    DalamProses --> Selesai: nomor + tanggal + link diisi
    Selesai --> DalamProses: dikoreksi
    DalamProses --> Belum: dikoreksi
    Selesai --> Belum: dikoreksi
```

Delegasi (penanggung jawab) dapat diubah kapan saja oleh Ketua tim atau Super Admin, dan hanya kepada Sekretaris tim pelatihan.

## 5. Garis waktu tenggat dan peringatan (contoh ketentuan DTA)

Contoh: pendaftaran ditutup 10 Desember, pelatihan 15–17 Desember, ketentuan BAP H+1, SK/ST H+2, administrasi siap H+3.

```mermaid
gantt
    title Contoh tenggat (pendaftaran ditutup 10 Des)
    dateFormat YYYY-MM-DD
    axisFormat %d %b
    section Pendaftaran
    Pendaftaran                 :done, 2026-12-01, 2026-12-11
    section Tenggat dokumen
    BAP (H+1)                   :milestone, 2026-12-11, 0d
    SK & ST (H+2)               :milestone, 2026-12-12, 0d
    Administrasi siap (H+3)     :milestone, 2026-12-13, 0d
    section Pelatihan
    Pelaksanaan                 :active, 2026-12-15, 2026-12-18
```

| Tanggal | Peringatan (bila dokumen belum Selesai) |
|---|---|
| 11 Des, 00.00–23.59 WIB | "BAP tenggat hari ini"; "Link peserta kosong" bila link belum diisi |
| 12 Des | "BAP lewat tenggat", "SK/ST tenggat hari ini" |
| 13 Des | BAP, SK, ST lewat tenggat (belum kritis) |
| 14 Des dan seterusnya | "Administrasi kritis" (lewat tenggat administrasi siap) |
| 15–17 Des | "Segera berakhir" (sedang berlangsung, sisa ≤ ambang hari) |

## 6. Finalisasi pelatihan (sequence)

```mermaid
sequenceDiagram
    actor U as Admin / Super Admin
    participant C as TrainingController
    participant F as FinalizeTraining
    participant R as Repository
    participant S as ResolveSopProvision
    participant D as Domain (Training)
    U->>C: POST /trainings/{id}/finalize
    C->>F: handle(actor, id)
    F->>R: findForUpdate(id) [kunci baris]
    F->>F: periksa hak (TrainingPermissions)
    F->>S: ketentuan berlaku (tim, hari ini)
    S-->>F: ketentuan / fallback / kosong
    F->>D: finalize(salinan SOP)
    D-->>F: Final (atau tolak: belum lengkap)
    F->>R: simpan + buat BAP/SK/ST
    F->>R: catat audit training.finalized
    F-->>C: peringatan kesiapan (tidak memblokir)
    C-->>U: redirect + pesan sukses / peringatan
```

## 7. Penugasan dengan bentrok (sequence)

```mermaid
sequenceDiagram
    actor K as Ketua tim / Super Admin
    participant U as UpdateTrainingAssignments
    participant R as Repository
    participant P as Domain (AssignmentPlan)
    K->>U: centang Admin/Panitia (+ alasan opsional)
    U->>R: kunci pelatihan, ambil penugasan sekarang
    U->>R: penugasan lain yang beririsan (awal daftar s.d. akhir latih)
    U->>P: selisih + kelayakan + bentrok
    alt bentrok tanpa alasan
        P-->>K: ditolak: sebut pegawai, peran, dan bentroknya
    else valid
        P-->>U: ditambah / dicabut (alasan pada yang bentrok)
        U->>R: simpan + audit per penugasan
        U-->>K: "Penugasan disimpan"
    end
```
