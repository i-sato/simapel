<?php

declare(strict_types=1);

namespace App\View;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Training\TrainingPhase;
use App\Domain\Training\TrainingSchedule;
use App\Models\TrainingRecord;
use Illuminate\Database\Eloquent\Builder;

/**
 * Status waktu pelatihan untuk tampilan dan filter daftar. Aturannya ada di
 * App\Domain\Training\TrainingSchedule::phaseOn().
 */
final readonly class TrainingPhases
{
    public function __construct(private Clock $clock) {}

    public function for(TrainingRecord $training): ?TrainingPhase
    {
        return TrainingSchedule::of(
            $training->registration_start,
            $training->registration_end,
            $training->training_start,
            $training->training_end,
        )->phaseOn($this->clock->now());
    }

    public static function badgeClass(?TrainingPhase $phase): string
    {
        return match ($phase) {
            TrainingPhase::Pendaftaran => 'badge success',
            TrainingPhase::PenetapanPeserta => 'badge warning',
            TrainingPhase::Pelaksanaan, TrainingPhase::AkanDibuka => 'badge blue',
            default => 'badge',
        };
    }

    /**
     * Membatasi query ke pelatihan dengan status waktu tertentu (padanan SQL dari phaseOn()).
     *
     * @param  Builder<TrainingRecord>  $query
     */
    public function scope(Builder $query, TrainingPhase $phase): void
    {
        $today = $this->clock->now()->format('Y-m-d');

        $query->whereNotNull('registration_start')->whereNotNull('registration_end')
            ->whereNotNull('training_start')->whereNotNull('training_end');

        match ($phase) {
            TrainingPhase::AkanDibuka => $query->where('registration_start', '>', $today),
            TrainingPhase::Pendaftaran => $query->where('registration_start', '<=', $today)->where('registration_end', '>=', $today),
            TrainingPhase::PenetapanPeserta => $query->where('registration_end', '<', $today)->where('training_start', '>', $today),
            TrainingPhase::Pelaksanaan => $query->where('training_start', '<=', $today)->where('training_end', '>=', $today),
            TrainingPhase::Selesai => $query->where('training_end', '<', $today),
        };
    }
}
