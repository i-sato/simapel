<?php

declare(strict_types=1);

namespace App\Application\Training;

/**
 * Isian form pelatihan yang sudah lolos validasi format di Presentation.
 * Tanggal berformat Y-m-d; pilihan berupa nilai enum.
 */
final readonly class TrainingInput
{
    public function __construct(
        public string $name,
        public ?string $registrationUrl = null,
        public ?string $registrationStart = null,
        public ?string $registrationEnd = null,
        public ?string $trainingStart = null,
        public ?string $trainingEnd = null,
        public ?string $partnerLocation = null,
        public ?string $participantListUrl = null,
        public ?string $method = null,
        public ?int $batch = null,
        public ?string $syllabusUrl = null,
        public ?string $level = null,
        public ?string $certification = null,
        public ?bool $cocShowsScore = null,
        public ?string $execution = null,
        public ?string $participantCategory = null,
        public ?string $zonation = null,
        public ?int $lessonHours = null,
        public ?int $participantTarget = null,
        public ?int $registrationQuota = null,
        public ?int $learningPathId = null,
        public ?string $newLearningPathName = null,
        public ?string $region = null,
        public ?string $registrationClosesAt = null,
        public ?string $partnerName = null,
    ) {}
}
