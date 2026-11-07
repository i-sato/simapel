<?php

namespace Tests\Unit\Domain\Administration;

use App\Domain\Administration\AdministrativeDocument;
use App\Domain\Administration\DocumentPermissions;
use App\Domain\Administration\DocumentStatus;
use App\Domain\Administration\DocumentType;
use App\Domain\Administration\InvalidDocument;
use App\Domain\Organization\TeamRole;
use App\Domain\Shared\HttpUrl;
use App\Domain\Training\TrainingActor;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\TrainingBuilder;

final class AdministrativeDocumentTest extends TestCase
{
    private function document(?int $assignee = null): AdministrativeDocument
    {
        return AdministrativeDocument::restore(1, 9, DocumentType::Sk, DocumentStatus::Belum, null, null, null, $assignee);
    }

    public function test_new_document_starts_as_belum(): void
    {
        $open = AdministrativeDocument::open(9, DocumentType::Bap);

        $this->assertSame(DocumentStatus::Belum, $open->status);
        $this->assertNull($open->assigneeId);
        $this->assertFalse($open->isDone());
        $this->assertSame('BAP penetapan peserta', DocumentType::Bap->fullLabel());
    }

    public function test_selesai_requires_number_date_and_link(): void
    {
        $date = new DateTimeImmutable('2026-12-11 15:00');
        $url = HttpUrl::fromString('https://drive.example.test/sk');

        foreach ([['number', null, $date, $url], ['date', 'SK/1', null, $url], ['file_url', 'SK/1', $date, null], ['number', '  ', $date, $url]] as [$field, $number, $d, $u]) {
            try {
                $this->document()->record(DocumentStatus::Selesai, $number, $d, $u);
                $this->fail("Status Selesai tanpa {$field} seharusnya ditolak.");
            } catch (InvalidDocument $exception) {
                $this->assertSame($field, $exception->field());
            }
        }

        $done = $this->document()->record(DocumentStatus::Selesai, ' SK/1 ', $date, $url);
        $this->assertTrue($done->isDone());
        $this->assertSame('SK/1', $done->number);
        $this->assertSame('2026-12-11 00:00', $done->date?->format('Y-m-d H:i'));

        $this->assertSame(DocumentStatus::DalamProses, $this->document()->record(DocumentStatus::DalamProses, null, null, null)->status);
    }

    public function test_number_length_is_limited(): void
    {
        $this->expectException(InvalidDocument::class);
        $this->document()->record(DocumentStatus::DalamProses, str_repeat('9', 101), null, null);
    }

    public function test_delegation_only_to_team_secretary(): void
    {
        $this->assertSame(5, $this->document()->delegateTo(5, TeamRole::Sekretaris)->assigneeId);
        $this->assertNull($this->document(5)->delegateTo(null, null)->assigneeId);

        foreach ([TeamRole::Anggota, TeamRole::Ketua, null] as $role) {
            try {
                $this->document()->delegateTo(6, $role);
                $this->fail('Delegasi ke selain Sekretaris seharusnya ditolak.');
            } catch (InvalidDocument $exception) {
                $this->assertSame('assignee_id', $exception->field());
            }
        }
    }

    public function test_permissions(): void
    {
        $training = TrainingBuilder::draft(teamId: 1, id: 9);
        $delegated = $this->document(assignee: 5);

        $superAdmin = new TrainingActor(1, true, true);
        $ketua = new TrainingActor(2, true, false, ledTeamIds: [1]);
        $sekretaris = new TrainingActor(5, true, false);
        $otherSekretaris = new TrainingActor(6, true, false);
        $inactive = new TrainingActor(5, false, false);

        $this->assertTrue(DocumentPermissions::canDelegate($superAdmin, $training));
        $this->assertTrue(DocumentPermissions::canDelegate($ketua, $training));
        $this->assertFalse(DocumentPermissions::canDelegate($sekretaris, $training));

        $this->assertTrue(DocumentPermissions::canUpdate($sekretaris, $training, $delegated));
        $this->assertTrue(DocumentPermissions::canUpdate($ketua, $training, $delegated));
        $this->assertTrue(DocumentPermissions::canUpdate($superAdmin, $training, $this->document()));
        $this->assertFalse(DocumentPermissions::canUpdate($otherSekretaris, $training, $delegated));
        $this->assertFalse(DocumentPermissions::canUpdate($sekretaris, $training, $this->document()));
        $this->assertFalse(DocumentPermissions::canUpdate($inactive, $training, $delegated));
    }
}
