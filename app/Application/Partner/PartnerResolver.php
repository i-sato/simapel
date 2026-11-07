<?php

declare(strict_types=1);

namespace App\Application\Partner;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Partner\Contracts\PartnerRepository;
use App\Domain\Partner\InvalidPartner;
use App\Domain\Partner\Partner;
use App\Domain\Partner\PartnerPermissions;
use App\Domain\Shared\WorkRegion;
use App\Domain\Training\TrainingActor;

/**
 * Menentukan mitra dan wilayah kerja pelatihan dari isian form (dipanggil di dalam transaksi):
 * - tanpa mitra: wilayah dari isian;
 * - mitra terdaftar: wilayah otomatis mengikuti mitra;
 * - mitra baru: didaftarkan bila pelaku berhak (Super Admin, Ketua, pegawai bertanda Admin).
 */
final readonly class PartnerResolver
{
    public function __construct(
        private PartnerRepository $partners,
        private AuditTrail $audit,
    ) {}

    /**
     * @return array{0: int|null, 1: WorkRegion|null} id mitra dan wilayah kerja
     *
     * @throws InvalidPartner
     */
    public function resolve(TrainingActor $actor, ?string $partnerName, ?WorkRegion $region): array
    {
        if ($partnerName === null || trim($partnerName) === '') {
            return [null, $region];
        }

        $existing = $this->partners->findByNormalizedName(Partner::normalize($partnerName));

        if ($existing !== null) {
            return [$existing->id, $existing->region];
        }

        if (! PartnerPermissions::canManage($actor)) {
            throw new InvalidPartner('Mitra belum terdaftar. Minta Ketua tim, Admin, atau Super Admin menambahkannya di menu Mitra.', 'partner_name');
        }

        if ($region === null) {
            throw new InvalidPartner('Pilih wilayah kerja untuk mendaftarkan mitra baru.', 'work_region');
        }

        $partner = new Partner(null, $partnerName, $region);
        $id = $this->partners->insert($partner, $actor->userId);
        PartnerAudit::created($this->audit, $actor->userId, $id, $partner);

        return [$id, $region];
    }
}
