<?php

namespace Tests\Support;

use App\Application\Training\TrainingInput;
use App\Domain\Shared\HttpUrl;
use App\Domain\Shared\WorkRegion;
use App\Domain\Training\Certification;
use App\Domain\Training\ExecutionMode;
use App\Domain\Training\ParticipantCategory;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingLevel;
use App\Domain\Training\TrainingMethod;
use App\Domain\Training\TrainingSchedule;
use App\Domain\Training\Zonation;
use DateTimeImmutable;

/**
 * Data pelatihan sintetis untuk unit test. Jadwal bawaan: pendaftaran 1–10 Des 2026,
 * pelatihan 15–17 Des 2026 (jeda H+5).
 */
final class TrainingBuilder
{
    public static function schedule(
        ?string $registrationStart = '2026-12-01',
        ?string $registrationEnd = '2026-12-10',
        ?string $trainingStart = '2026-12-15',
        ?string $trainingEnd = '2026-12-17',
    ): TrainingSchedule {
        $date = fn (?string $value): ?DateTimeImmutable => $value === null ? null : new DateTimeImmutable($value);

        return TrainingSchedule::of($date($registrationStart), $date($registrationEnd), $date($trainingStart), $date($trainingEnd));
    }

    public static function completeDetails(
        ?TrainingSchedule $schedule = null,
        TrainingLevel $level = TrainingLevel::Dasar,
        ?string $participantListUrl = null,
        string $name = 'Pelatihan Uji',
    ): TrainingDetails {
        return new TrainingDetails(
            name: $name,
            schedule: $schedule ?? self::schedule(),
            registrationUrl: HttpUrl::fromString('https://digitalent.example.test/daftar'),
            partnerLocation: 'Mitra Uji, Yogyakarta',
            participantListUrl: HttpUrl::fromNullable($participantListUrl),
            method: TrainingMethod::Daring,
            batch: 1,
            syllabusUrl: HttpUrl::fromString('https://drive.example.test/silabus'),
            level: $level,
            certification: Certification::Industri,
            cocShowsScore: true,
            execution: ExecutionMode::Swakelola,
            participantCategory: ParticipantCategory::Umum,
            zonation: Zonation::WilayahKerja,
            lessonHours: 32,
            participantTarget: 100,
            registrationQuota: 300,
            region: WorkRegion::Diy,
        );
    }

    public static function draft(int $teamId = 1, ?TrainingDetails $details = null, ?int $pathId = null, int $id = 0): Training
    {
        $training = Training::draft($teamId, $details ?? self::completeDetails(), $pathId, new DateTimeImmutable('2026-11-04'));

        return $id > 0 ? $training->withId($id) : $training;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function input(array $overrides = []): TrainingInput
    {
        $values = array_merge([
            'name' => 'Pelatihan Uji',
            'registrationUrl' => 'https://digitalent.example.test/daftar',
            'registrationStart' => '2026-12-01',
            'registrationEnd' => '2026-12-10',
            'trainingStart' => '2026-12-15',
            'trainingEnd' => '2026-12-17',
            'partnerLocation' => 'Mitra Uji, Yogyakarta',
            'participantListUrl' => null,
            'method' => 'daring',
            'batch' => 1,
            'syllabusUrl' => 'https://drive.example.test/silabus',
            'level' => 'dasar',
            'certification' => 'industri',
            'cocShowsScore' => true,
            'execution' => 'swakelola',
            'participantCategory' => 'umum',
            'zonation' => 'wilayah_kerja',
            'lessonHours' => 32,
            'participantTarget' => 100,
            'registrationQuota' => 300,
            'learningPathId' => null,
            'newLearningPathName' => null,
            'region' => 'diy',
            'registrationClosesAt' => null,
            'partnerName' => null,
        ], $overrides);

        return new TrainingInput(...$values);
    }
}
