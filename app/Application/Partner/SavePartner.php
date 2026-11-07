<?php

declare(strict_types=1);

namespace App\Application\Partner;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Partner\Contracts\PartnerRepository;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\TrainingActorProvider;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Partner\InvalidPartner;
use App\Domain\Partner\Partner;
use App\Domain\Partner\PartnerPermissions;
use App\Domain\Shared\WorkRegion;

/**
 * Menambah atau mengubah mitra dari halaman Mitra. Nama yang sama setelah dinormalisasi
 * ditolak agar tidak ada duplikat.
 */
final readonly class SavePartner
{
    public function __construct(
        private PartnerRepository $partners,
        private TrainingActorProvider $actors,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    /**
     * @throws TrainingAccessDenied|InvalidPartner
     */
    public function handle(int $actorId, ?int $partnerId, string $name, string $region): int
    {
        if (! PartnerPermissions::canManage($this->actors->forUser($actorId))) {
            throw new TrainingAccessDenied;
        }

        $regionCase = WorkRegion::tryFrom($region) ?? throw new InvalidPartner('Pilih wilayah kerja.', 'work_region');

        return $this->transaction->run(function () use ($actorId, $partnerId, $name, $regionCase): int {
            $current = $partnerId === null ? null : ($this->partners->find($partnerId) ?? throw new InvalidPartner('Mitra tidak ditemukan.', 'partner_name'));
            $partner = $current === null ? new Partner(null, $name, $regionCase) : $current->rename($name, $regionCase);

            $duplicate = $this->partners->findByNormalizedName($partner->normalizedName());

            if ($duplicate !== null && $duplicate->id !== $partnerId) {
                throw new InvalidPartner(sprintf('Mitra dengan nama serupa sudah terdaftar: "%s".', $duplicate->name), 'partner_name');
            }

            if ($current === null) {
                $id = $this->partners->insert($partner, $actorId);
                PartnerAudit::created($this->audit, $actorId, $id, $partner);

                return $id;
            }

            if ($current->name === $partner->name && $current->region === $partner->region) {
                return (int) $partnerId;
            }

            $this->partners->update($partner, $actorId);
            $this->audit->record(new AuditEvent(
                type: AuditEventType::PartnerUpdated,
                description: sprintf('Mitra "%s" diubah.', $partner->name),
                actorId: $actorId,
                subjectType: 'partner',
                subjectId: $partnerId,
                context: [
                    'old_name' => $current->name, 'new_name' => $partner->name,
                    'old_region' => $current->region->value, 'new_region' => $partner->region->value,
                ],
            ));

            return (int) $partnerId;
        });
    }

    public function canManage(int $actorId): bool
    {
        return PartnerPermissions::canManage($this->actors->forUser($actorId));
    }
}
