<?php

namespace Tests\Unit\Domain\Partner;

use App\Domain\Partner\InvalidPartner;
use App\Domain\Partner\Partner;
use App\Domain\Partner\PartnerPermissions;
use App\Domain\Shared\WorkRegion;
use App\Domain\Training\ExecutionMode;
use App\Domain\Training\TrainingActor;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingSchedule;
use PHPUnit\Framework\TestCase;
use Tests\Support\TrainingBuilder;

final class PartnerTest extends TestCase
{
    public function test_names_are_tidied_and_normalized_for_duplicate_check(): void
    {
        $partner = new Partner(null, "  PT.  Maju\tJaya ", WorkRegion::Bali);

        $this->assertSame('PT. Maju Jaya', $partner->name);
        $this->assertSame('pt maju jaya', $partner->normalizedName());
        $this->assertSame(Partner::normalize('pt maju-jaya'), $partner->normalizedName());
        $this->assertSame('universitas gadjah mada', Partner::normalize('Universitas  Gadjah Mada!'));
        $this->assertSame(WorkRegion::Diy, $partner->rename('PT Maju Jaya', WorkRegion::Diy)->region);
    }

    public function test_name_is_required_and_limited(): void
    {
        foreach (['  ', str_repeat('a', Partner::MAX_NAME + 1)] as $name) {
            try {
                new Partner(null, $name, WorkRegion::Diy);
                $this->fail('Nama mitra tidak valid seharusnya ditolak.');
            } catch (InvalidPartner $exception) {
                $this->assertSame('partner_name', $exception->field());
            }
        }
    }

    public function test_super_admin_ketua_and_admin_capable_employees_manage_partners(): void
    {
        $this->assertTrue(PartnerPermissions::canManage(new TrainingActor(1, true, true)));
        $this->assertTrue(PartnerPermissions::canManage(new TrainingActor(2, true, false, ledTeamIds: [3])));
        $this->assertTrue(PartnerPermissions::canManage(new TrainingActor(3, true, false, canBeAdmin: true)));
        $this->assertFalse(PartnerPermissions::canManage(new TrainingActor(4, true, false, assignedPanitiaTrainingIds: [1])));
        $this->assertFalse(PartnerPermissions::canManage(new TrainingActor(3, false, false, canBeAdmin: true)));
    }

    public function test_finalization_requires_region_and_partner_for_partner_execution(): void
    {
        $this->assertArrayHasKey('work_region', (new TrainingDetails('Draft', TrainingSchedule::empty()))->missingForFinal());

        $complete = TrainingBuilder::completeDetails();
        $this->assertSame([], $complete->missingForFinal());
        $this->assertSame('23:59', $complete->registrationClosesAt->value());

        $byPartner = new TrainingDetails('Mitra', TrainingBuilder::schedule(), execution: ExecutionMode::Mitra, region: WorkRegion::Bali);
        $this->assertArrayHasKey('partner_name', $byPartner->missingForFinal());
        $this->assertArrayNotHasKey('partner_name', $byPartner->withPartner(5, WorkRegion::Bali)->missingForFinal());
        $this->assertSame(5, $byPartner->withPartner(5, WorkRegion::Bali)->partnerId);
    }
}
