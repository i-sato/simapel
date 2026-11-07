<?php

declare(strict_types=1);

namespace App\Application\Messaging;

use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Settings\GlobalSettings;
use App\Application\Training\Contracts\TrainingRepository;
use App\Domain\Messaging\MessageValues;
use App\Domain\Messaging\Placeholder;
use App\Domain\Settings\GlobalSetting;
use App\Domain\Shared\DateRange;
use App\Domain\Training\LearningPathSequence;
use App\Domain\Training\Training;
use DateTimeImmutable;

/**
 * Nilai placeholder dari data pelatihan terkini, pengaturan umum (langkah 2), dan
 * learning path. Dibentuk ulang setiap kali template ditampilkan.
 */
final readonly class TrainingMessageValues
{
    public function __construct(
        private WorkTeamRepository $teams,
        private GlobalSettings $settings,
        private TrainingRepository $trainings,
    ) {}

    public function for(Training $training): MessageValues
    {
        $details = $training->details;
        $schedule = $details->schedule;
        $team = $this->teams->find($training->teamId);

        return new MessageValues([
            Placeholder::NamaPelatihan->value => $details->name,
            Placeholder::TimKerja->value => $team?->name,
            Placeholder::Level->value => $details->level?->fullLabel(),
            Placeholder::Metode->value => $details->method?->label(),
            Placeholder::LinkPendaftaran->value => $details->registrationUrl?->value,
            Placeholder::TanggalPendaftaran->value => self::period($schedule->registrationStart, $schedule->registrationEnd),
            Placeholder::TanggalPelatihan->value => self::period($schedule->trainingStart, $schedule->trainingEnd),
            Placeholder::LinkDaftarPeserta->value => $details->participantListUrl?->value,
            Placeholder::LinkLoginDigitalent->value => $this->settings->get(GlobalSetting::DigitalentLoginUrl),
            Placeholder::LinkRegistrasiDigitalent->value => $this->settings->get(GlobalSetting::DigitalentRegisterUrl),
            Placeholder::LinkPanduanDts->value => $this->settings->get(GlobalSetting::DtsRegistrationGuideUrl),
            Placeholder::LinkPanduanPelatihan->value => $team?->registrationGuideUrl,
            Placeholder::LearningPath->value => $this->learningPath($training),
        ]);
    }

    public function hasLearningPath(Training $training): bool
    {
        return $training->learningPathId !== null;
    }

    private function learningPath(Training $training): string
    {
        if ($training->learningPathId === null) {
            return '(tidak termasuk learning path)';
        }

        $byLevel = [];

        foreach ($this->trainings->inLearningPath($training->learningPathId) as $member) {
            if ($member->details->level !== null) {
                $byLevel[$member->details->level->value] = $member;
            }
        }

        $lines = [];

        foreach ((new LearningPathSequence($byLevel))->ordered() as $entry) {
            $member = $entry['item'];
            $schedule = $member->details->schedule;
            $lines[] = sprintf(
                "- %s: %s%s\n  Link: %s\n  Pendaftaran: %s · Pelatihan: %s",
                $entry['level']->fullLabel(),
                $member->details->name,
                $member->id === $training->id ? ' (pelatihan ini)' : '',
                $member->details->registrationUrl->value ?? MessageValues::PENDING,
                self::period($schedule->registrationStart, $schedule->registrationEnd) ?? MessageValues::PENDING,
                self::period($schedule->trainingStart, $schedule->trainingEnd) ?? MessageValues::PENDING,
            );
        }

        return implode("\n", $lines);
    }

    private static function period(?DateTimeImmutable $start, ?DateTimeImmutable $end): ?string
    {
        if ($start === null || $end === null) {
            return null;
        }

        return (new DateRange($start, $end))->longLabel();
    }
}
