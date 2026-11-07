<?php

namespace Tests\Feature\Admin;

use App\Domain\Audit\AuditEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    private function upload(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('pegawai.csv', $content);
    }

    /**
     * @return TestResponse<Response>
     */
    private function importAs(User $user, string $content): TestResponse
    {
        return $this->actingAs($user)->from('/employees/import')->post('/employees/import', ['file' => $this->upload($content)]);
    }

    public function test_valid_comma_csv_with_bom_is_imported(): void
    {
        $csv = "\xEF\xBB\xBFnama,nip,pangkat_golongan,jabatan,email\n"
            ."Satu Uji,999900000000000401,Penata Muda - III/a,Pranata Komputer,satu@example.test\n"
            ."\n"
            ."\"Dua, Uji\",999900000000000402,,,\n";

        $this->importAs(User::factory()->superAdmin()->create(), $csv)
            ->assertRedirect('/employees')
            ->assertSessionHas('success');

        $this->assertSame('Dua, Uji', User::query()->where('nip', '999900000000000402')->sole()->name);
        $this->assertTrue(User::query()->where('nip', '999900000000000401')->sole()->password_change_required);
        $this->assertDatabaseHas('audit_logs', ['event_type' => AuditEventType::EmployeesImported->value]);
    }

    public function test_semicolon_csv_from_excel_is_accepted(): void
    {
        $csv = "nama;nip;pangkat_golongan;jabatan;email\r\nTiga Uji;999900000000000403;Penata - III/c;Analis;\r\n";

        $this->importAs(User::factory()->superAdmin()->create(), $csv)->assertRedirect('/employees');

        $this->assertSame('Analis', User::query()->where('nip', '999900000000000403')->sole()->jabatan);
    }

    public function test_wrong_header_is_rejected(): void
    {
        $this->importAs(User::factory()->superAdmin()->create(), "Nama,NIP,Email\nA,999900000000000404,\n")
            ->assertSessionHasErrors(['file' => 'Header wajib persis: nama,nip,pangkat_golongan,jabatan,email.']);
    }

    public function test_wrong_column_count_is_rejected(): void
    {
        $this->importAs(User::factory()->superAdmin()->create(), "nama,nip,pangkat_golongan,jabatan,email\nA,999900000000000405\n")
            ->assertSessionHasErrors('file');
    }

    public function test_invalid_rows_cancel_the_import_and_are_listed(): void
    {
        User::factory()->create(['nip' => '999900000000000406']);
        $csv = "nama,nip,pangkat_golongan,jabatan,email\n"
            ."Valid,999900000000000407,,,\n"
            ."Sudah Ada,999900000000000406,,,\n"
            ."Salah,123,,,\n";

        $this->importAs(User::factory()->superAdmin()->create(), $csv)
            ->assertRedirect('/employees/import')
            ->assertSessionHas('import_errors', [
                ['line' => 3, 'message' => 'NIP sudah terdaftar.'],
                ['line' => 4, 'message' => 'NIP harus terdiri dari tepat 18 digit angka.'],
            ]);

        $this->assertDatabaseMissing('users', ['nip' => '999900000000000407']);
        $this->get('/employees/import')->assertSee('Impor dibatalkan')->assertSee('Baris 3');
    }

    public function test_non_csv_files_and_pegawai_are_rejected(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->post('/employees/import', ['file' => UploadedFile::fake()->create('foto.png', 10, 'image/png')])
            ->assertSessionHasErrors('file');

        $this->actingAs(User::factory()->create())->post('/employees/import', ['file' => $this->upload("nama,nip,pangkat_golongan,jabatan,email\n")])
            ->assertForbidden();
    }
}
