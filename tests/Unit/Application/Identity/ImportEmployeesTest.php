<?php

namespace Tests\Unit\Application\Identity;

use App\Application\Identity\EmployeeImportRow;
use App\Application\Identity\ImportEmployees;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FakePasswordHasher;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryUserAccountRepository;

final class ImportEmployeesTest extends TestCase
{
    private InMemoryUserAccountRepository $users;

    private InMemoryAuditTrail $audit;

    private ImmediateTransaction $transaction;

    private ImportEmployees $import;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserAccountRepository;
        $this->audit = new InMemoryAuditTrail;
        $this->transaction = new ImmediateTransaction;
        $this->import = new ImportEmployees($this->users, new FakePasswordHasher, $this->audit, $this->transaction);
    }

    private function row(int $line, string $nip, string $name = 'Pegawai', ?string $email = null): EmployeeImportRow
    {
        return new EmployeeImportRow($line, $name, $nip, 'Penata Muda - III/a', 'Pranata Komputer', $email);
    }

    public function test_imports_all_rows_as_pegawai_with_random_password_that_must_be_changed(): void
    {
        $result = $this->import->handle([
            $this->row(2, '999900000000000201', 'Satu', 'satu@example.test'),
            $this->row(3, '999900000000000202', 'Dua'),
        ], actorId: 1);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(2, $result->created);
        $this->assertCount(2, $this->users->accounts);
        $this->assertSame(1, $this->transaction->runs);

        foreach ($this->users->accounts as $record) {
            $this->assertSame(Role::Pegawai, $record['role']);
            $this->assertFalse($record['canBeAdmin']);
            $this->assertTrue($record['passwordChangeRequired']);
            $this->assertMatchesRegularExpression('/\Ahashed:[0-9a-f]{64}\z/', $record['passwordHash']);
        }

        $types = array_map(fn ($event) => $event->type, $this->audit->events);
        $this->assertSame([AuditEventType::UserCreated, AuditEventType::UserCreated, AuditEventType::EmployeesImported], $types);
    }

    public function test_any_invalid_row_cancels_the_whole_import_and_reports_line_numbers(): void
    {
        $this->users->seed('999900000000000210', email: 'ada@example.test');

        $result = $this->import->handle([
            $this->row(2, '999900000000000211', 'Valid'),
            $this->row(3, '12345', 'NIP salah'),
            $this->row(4, '999900000000000210', 'Sudah ada'),
            $this->row(5, '999900000000000211', 'Duplikat dalam berkas'),
            $this->row(6, '999900000000000212', 'Email sudah ada', 'ada@example.test'),
            $this->row(7, '999900000000000213', 'Email', 'sama@example.test'),
            $this->row(8, '999900000000000214', 'Email kembar', 'sama@example.test'),
            $this->row(9, '999900000000000215', ' '),
        ], 1);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(0, $result->created);
        $this->assertCount(1, $this->users->accounts);
        $this->assertSame(0, $this->transaction->runs);
        $this->assertSame([], $this->audit->events);

        $messages = [];
        foreach ($result->errors as $error) {
            $messages[$error->line] = $error->message;
        }

        $this->assertSame([3, 4, 5, 6, 8, 9], array_keys($messages));
        $this->assertStringContainsString('18 digit', $messages[3]);
        $this->assertSame('NIP sudah terdaftar.', $messages[4]);
        $this->assertSame('NIP sama dengan baris 2.', $messages[5]);
        $this->assertSame('Email sudah terdaftar.', $messages[6]);
        $this->assertSame('Email sama dengan baris 7.', $messages[8]);
        $this->assertStringContainsString('Nama wajib diisi', $messages[9]);
    }

    public function test_empty_or_oversized_files_are_rejected(): void
    {
        $this->assertFalse($this->import->handle([], 1)->isSuccessful());

        $rows = [];
        for ($index = 0; $index <= ImportEmployees::MAX_ROWS; $index++) {
            $rows[] = $this->row($index + 2, sprintf('9999%014d', $index));
        }

        $result = $this->import->handle($rows, 1);
        $this->assertFalse($result->isSuccessful());
        $this->assertStringContainsString('Maksimal 1000', $result->errors[0]->message);
        $this->assertSame([], $this->users->accounts);
    }
}
