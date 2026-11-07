<?php

namespace Tests\Unit\Application\Partner;

use App\Application\Partner\PartnerResolver;
use App\Application\Partner\SavePartner;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\TrainingActorProvider;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;
use App\Domain\Partner\InvalidPartner;
use App\Domain\Partner\Partner;
use App\Domain\Shared\WorkRegion;
use App\Domain\Training\TrainingActor;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAssignmentRepository;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryPartnerRepository;
use Tests\Support\Fakes\InMemoryTrainingRepository;
use Tests\Support\Fakes\InMemoryUserAccountRepository;
use Tests\Support\Fakes\InMemoryWorkTeamRepository;

final class PartnerUseCasesTest extends TestCase
{
    private InMemoryPartnerRepository $partners;

    private InMemoryAuditTrail $audit;

    private PartnerResolver $resolver;

    private SavePartner $save;

    private int $superAdmin;

    private int $adminCapable;

    private int $employee;

    protected function setUp(): void
    {
        $this->partners = new InMemoryPartnerRepository;
        $this->audit = new InMemoryAuditTrail;
        $users = new InMemoryUserAccountRepository;
        $teams = new InMemoryWorkTeamRepository;

        $this->superAdmin = $users->seed('999900000000000941', Role::SuperAdmin);
        $this->adminCapable = $users->seed('999900000000000942');
        $users->accounts[$this->adminCapable]['canBeAdmin'] = true;
        $this->employee = $users->seed('999900000000000943');

        $actors = new TrainingActorProvider($users, $teams, new InMemoryAssignmentRepository(new InMemoryTrainingRepository, $teams));
        $this->resolver = new PartnerResolver($this->partners, $this->audit);
        $this->save = new SavePartner($this->partners, $actors, $this->audit, new ImmediateTransaction);
    }

    public function test_resolver_uses_registered_partner_region(): void
    {
        $id = $this->partners->insert(new Partner(null, 'PT Maju Jaya', WorkRegion::Bali), 1);
        $actor = new TrainingActor(5, true, false, ledTeamIds: [1]);

        $this->assertSame([null, WorkRegion::Diy], $this->resolver->resolve($actor, '  ', WorkRegion::Diy));
        $this->assertSame([$id, WorkRegion::Bali], $this->resolver->resolve($actor, 'pt. maju  jaya', WorkRegion::Diy));
        $this->assertSame([], $this->audit->events);
    }

    public function test_resolver_registers_new_partner_for_authorized_actor(): void
    {
        $ketua = new TrainingActor(5, true, false, ledTeamIds: [1]);

        [$id, $region] = $this->resolver->resolve($ketua, 'Universitas Uji', WorkRegion::JawaTengah);

        $this->assertSame(WorkRegion::JawaTengah, $region);
        $this->assertSame('Universitas Uji', $this->partners->partners[(int) $id]->name);
        $this->assertSame(AuditEventType::PartnerCreated, $this->audit->events[0]->type);

        try {
            $this->resolver->resolve($ketua, 'Mitra Tanpa Wilayah', null);
            $this->fail('Mitra baru tanpa wilayah seharusnya ditolak.');
        } catch (InvalidPartner $exception) {
            $this->assertSame('work_region', $exception->field());
        }

        $this->expectException(InvalidPartner::class);
        $this->resolver->resolve(new TrainingActor(6, true, false, assignedPanitiaTrainingIds: [1]), 'Mitra Lain', WorkRegion::Diy);
    }

    public function test_save_partner_rules(): void
    {
        $id = $this->save->handle($this->adminCapable, null, 'PT Maju Jaya', 'bali');
        $this->assertTrue($this->save->canManage($this->adminCapable));
        $this->assertFalse($this->save->canManage($this->employee));

        try {
            $this->save->handle($this->superAdmin, null, 'pt. MAJU jaya', 'diy');
            $this->fail('Duplikat seharusnya ditolak.');
        } catch (InvalidPartner $exception) {
            $this->assertStringContainsString('PT Maju Jaya', $exception->getMessage());
        }

        $this->assertSame($id, $this->save->handle($this->superAdmin, $id, 'PT Maju Jaya', 'bali'));
        $this->assertSame($id, $this->save->handle($this->superAdmin, $id, 'PT Maju Jaya Abadi', 'diy'));
        $event = $this->audit->events[1];
        $this->assertSame(AuditEventType::PartnerUpdated, $event->type);
        $this->assertSame('bali', $event->context['old_region']);
        $this->assertSame('diy', $event->context['new_region']);

        foreach ([[$this->superAdmin, null, 'Mitra', 'papua'], [$this->superAdmin, 99, 'Mitra', 'diy']] as [$actor, $partnerId, $name, $region]) {
            try {
                $this->save->handle($actor, $partnerId, $name, $region);
                $this->fail('Isian tidak valid seharusnya ditolak.');
            } catch (InvalidPartner) {
            }
        }

        $this->expectException(TrainingAccessDenied::class);
        $this->save->handle($this->employee, null, 'Mitra Pegawai', 'diy');
    }
}
