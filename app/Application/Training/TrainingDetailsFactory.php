<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Domain\Shared\HttpUrl;
use App\Domain\Shared\InvalidUrl;
use App\Domain\Shared\TimeOfDay;
use App\Domain\Shared\WorkRegion;
use App\Domain\Training\Certification;
use App\Domain\Training\ExecutionMode;
use App\Domain\Training\InvalidTraining;
use App\Domain\Training\ParticipantCategory;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingLevel;
use App\Domain\Training\TrainingMethod;
use App\Domain\Training\TrainingSchedule;
use App\Domain\Training\Zonation;
use BackedEnum;
use DateTimeImmutable;

final class TrainingDetailsFactory
{
    /**
     * @throws InvalidTraining|InvalidUrl
     */
    public static function from(TrainingInput $input): TrainingDetails
    {
        return new TrainingDetails(
            name: $input->name,
            schedule: TrainingSchedule::of(
                self::date($input->registrationStart, 'registration_start'),
                self::date($input->registrationEnd, 'registration_end'),
                self::date($input->trainingStart, 'training_start'),
                self::date($input->trainingEnd, 'training_end'),
            ),
            registrationUrl: HttpUrl::fromNullable($input->registrationUrl, 'registration_url'),
            partnerLocation: $input->partnerLocation,
            participantListUrl: HttpUrl::fromNullable($input->participantListUrl, 'participant_list_url'),
            method: self::choice(TrainingMethod::class, $input->method, 'method'),
            batch: $input->batch,
            syllabusUrl: HttpUrl::fromNullable($input->syllabusUrl, 'syllabus_url'),
            level: self::choice(TrainingLevel::class, $input->level, 'level'),
            certification: self::choice(Certification::class, $input->certification, 'certification'),
            cocShowsScore: $input->cocShowsScore,
            execution: self::choice(ExecutionMode::class, $input->execution, 'execution'),
            participantCategory: self::choice(ParticipantCategory::class, $input->participantCategory, 'participant_category'),
            zonation: self::choice(Zonation::class, $input->zonation, 'zonation'),
            lessonHours: $input->lessonHours,
            participantTarget: $input->participantTarget,
            registrationQuota: $input->registrationQuota,
            region: self::choice(WorkRegion::class, $input->region, 'work_region'),
            registrationClosesAt: $input->registrationClosesAt === null || trim($input->registrationClosesAt) === ''
                ? null
                : TimeOfDay::fromString($input->registrationClosesAt, 'registration_closes_at'),
        );
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    private static function choice(string $enum, ?string $value, string $field): ?BackedEnum
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $enum::tryFrom($value) ?? throw new InvalidTraining('Pilihan tidak valid.', $field);
    }

    private static function date(?string $value, string $field): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        if ($date === false || $date->format('Y-m-d') !== trim($value)) {
            throw new InvalidTraining('Format tanggal harus YYYY-MM-DD.', $field);
        }

        return $date;
    }
}
