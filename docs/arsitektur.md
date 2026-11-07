# Arsitektur

SIMAPEL memakai **clean architecture pragmatis** di atas konvensi Laravel. Tujuannya ada dua:
- aturan bisnis SOP dapat diuji tanpa framework;
- struktur tetap mudah dikenali tim yang terbiasa dengan pola *controller → service → model* di aplikasi referensi.

## Lapisan

```
 Presentation             Application              Domain
 (Http, View, Console) -> (use case + port) ------> (aturan bisnis murni)
                               ^
 Infrastructure ---------------+  mengimplementasikan port
 (Infrastructure, Models, Providers)
```

| Lapisan | Folder | Isi | Boleh bergantung pada |
|---|---|---|---|
| Domain | `app/Domain` | Value object (`Nip`), enum (`Role`, `AuditEventType`), kebijakan (`PasswordPolicy`), exception aturan bisnis | Domain saja |
| Application | `app/Application` | Use case (`CreateUserAccount`, `ChangeOwnPassword`), DTO input, port di `*/Contracts` | Domain, Application |
| Infrastructure | `app/Infrastructure`, `app/Models`, `app/Providers` | Repository Eloquent, hasher, transaksi, penghenti sesi, jam sistem, binding container | Semua kecuali Presentation |
| Presentation | `app/Http`, `app/View`, `app/Console` | Controller, Form Request, middleware, view composer, perintah artisan, Blade | Application, Domain, Models (untuk baca), framework |

Aturan ini **ditegakkan otomatis** oleh `tests/Architecture/LayerDependencyTest.php`:
- Domain dan Application tidak boleh memakai `Illuminate\*`, `Carbon\*`, `Symfony\*`, `App\Models`, maupun `App\Infrastructure`.
- Presentation tidak boleh memakai `App\Infrastructure` secara langsung, melainkan lewat port yang di-resolve container.
- Isi folder `Contracts` wajib berupa interface.
- `tests/Feature/Infrastructure/ContainerBindingsTest.php` memastikan setiap port punya binding di `AppServiceProvider::$bindings`.

## Pemetaan dari aplikasi referensi

| Referensi (`reference-simapel`) | SIMAPEL refactor |
|---|---|
| `app/Services/*Service` (logika + query + transaksi bercampur) | Use case di `app/Application/<Modul>`; aturan murni di `app/Domain/<Modul>` |
| String peran tersebar (`'Super Admin Regional'`, `'admin_pelatihan'`) | Enum `Role` (global), `TeamRole` (tim kerja), kapabilitas `can_be_admin` (ADR 0008) |
| Cek hak akses ditulis ulang di Blade | Policy/middleware di backend; view hanya menerima flag |
| Filter "pelatihan terlihat" disalin di 4 controller | Satu query scope/repository per modul (direncanakan) |
| Query langsung di controller untuk mutasi | Mutasi selalu lewat use case; controller boleh membaca untuk tampilan |
| CSS inline di layout, `style=""` di view | `public/css/simapel.css` (token sama) dan kelas utilitas |

## Aturan praktis

1. **Perubahan data selalu lewat Application**, baik use case maupun port seperti `AuditTrail`. Controller boleh membaca model Eloquent untuk tampilan (CQRS ringan).
2. **Aturan bisnis yang bisa dilanggar pengguna** dilempar sebagai turunan `App\Domain\Shared\BusinessRuleViolation` dengan nama field. Handler global di `bootstrap/app.php` mengubahnya menjadi error formulir, tanpa `try/catch` di controller.
3. **Perubahan data dan jejak auditnya ditulis dalam satu `Transaction`** (port) agar keduanya selalu konsisten.
4. **Waktu diambil dari port `Clock`**, bukan `now()` di Domain/Application, agar tenggat SOP dapat diuji.
5. **Validasi berlapis.** Form Request memvalidasi bentuk input, Domain/Application menegakkan aturan bisnis. Contohnya `PasswordPolicy`, yang dipakai web, CLI, dan use case sekaligus.

## Menambah fitur (contoh: modul Pelatihan)

1. **Domain**: `app/Domain/Training/` berisi value object, enum status, dan aturan tanpa framework (mis. penghitung tenggat), beserta unit test di `tests/Unit/Domain/Training`.
2. **Application**: use case `CreateTraining` dengan input DTO, port `TrainingRepository` di `Contracts`, serta unit test memakai fake di `tests/Support/Fakes`.
3. **Infrastructure**: model Eloquent dan migrasi, `EloquentTrainingRepository`, binding di `AppServiceProvider::$bindings`, dan feature test persistensi.
4. **Presentation**: Form Request, controller tipis, policy, route, dan view Blade yang memakai komponen CSS yang ada. Sertakan feature test HTTP.
5. **UI test**: spec Playwright dengan tag area baru (mis. `@training`), lalu daftarkan path baru di `e2e/impact-map.json`.
6. **Dokumentasi**: `CHANGELOG.md`, `docs/perubahan/NNNN-*.md`, dan pembaruan dokumen terkait.

## Keputusan terkait

Lihat [adr/](adr/), terutama ADR 0003 (clean architecture) dan ADR 0004 (aset statis dan CSP).
