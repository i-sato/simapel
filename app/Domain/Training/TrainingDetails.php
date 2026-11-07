<?php

declare(strict_types=1);

namespace App\Domain\Training;

use App\Domain\Shared\HttpUrl;

/**
 * Informasi pelatihan (19 dari 21 field business story; panitia dan admin di langkah 4).
 * Semua field opsional selama Draft, kecuali nama.
 */
final readonly class TrainingDetails
{
    public const MAX_NUMBER = 100000;

    /** Field wajib saat finalisasi beserta labelnya. */
    public const REQUIRED_FOR_FINAL = [
        'registration_url' => 'link pendaftaran',
        'registration_start' => 'awal pendaftaran',
        'registration_end' => 'akhir pendaftaran',
        'training_start' => 'awal pelatihan',
        'training_end' => 'akhir pelatihan',
        'partner_location' => 'mitra/lokasi',
        'method' => 'metode',
        'batch' => 'batch',
        'syllabus_url' => 'link silabus',
        'level' => 'level',
        'certification' => 'sertifikasi',
        'coc_shows_score' => 'CoC menampilkan nilai',
        'execution' => 'pelaksanaan',
        'participant_category' => 'kategori peserta',
        'zonation' => 'zonasi',
        'lesson_hours' => 'jumlah JP',
        'participant_target' => 'target peserta',
        'registration_quota' => 'kuota pendaftar',
    ];

    public string $name;

    public ?string $partnerLocation;

    /**
     * @throws InvalidTraining
     */
    public function __construct(
        string $name,
        public TrainingSchedule $schedule,
        public ?HttpUrl $registrationUrl = null,
        ?string $partnerLocation = null,
        public ?HttpUrl $participantListUrl = null,
        public ?TrainingMethod $method = null,
        public ?int $batch = null,
        public ?HttpUrl $syllabusUrl = null,
        public ?TrainingLevel $level = null,
        public ?Certification $certification = null,
        public ?bool $cocShowsScore = null,
        public ?ExecutionMode $execution = null,
        public ?ParticipantCategory $participantCategory = null,
        public ?Zonation $zonation = null,
        public ?int $lessonHours = null,
        public ?int $participantTarget = null,
        public ?int $registrationQuota = null,
    ) {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 255) {
            throw new InvalidTraining('Nama/tema pelatihan wajib diisi, maksimal 255 karakter.', 'name');
        }

        $partnerLocation = $partnerLocation === null ? null : trim($partnerLocation);

        if ($partnerLocation !== null && mb_strlen($partnerLocation) > 255) {
            throw new InvalidTraining('Mitra/lokasi maksimal 255 karakter.', 'partner_location');
        }

        foreach (['batch' => $batch, 'lesson_hours' => $lessonHours, 'participant_target' => $participantTarget, 'registration_quota' => $registrationQuota] as $field => $value) {
            if ($value !== null && ($value < 1 || $value > self::MAX_NUMBER)) {
                throw new InvalidTraining(sprintf('Nilai harus bilangan bulat positif (1–%d).', self::MAX_NUMBER), $field);
            }
        }

        $this->name = $name;
        $this->partnerLocation = $partnerLocation === '' ? null : $partnerLocation;
    }

    /**
     * @return array<string, string> field wajib yang belum diisi => label
     */
    public function missingForFinal(): array
    {
        $values = [
            'registration_url' => $this->registrationUrl,
            'registration_start' => $this->schedule->registrationStart,
            'registration_end' => $this->schedule->registrationEnd,
            'training_start' => $this->schedule->trainingStart,
            'training_end' => $this->schedule->trainingEnd,
            'partner_location' => $this->partnerLocation,
            'method' => $this->method,
            'batch' => $this->batch,
            'syllabus_url' => $this->syllabusUrl,
            'level' => $this->level,
            'certification' => $this->certification,
            'coc_shows_score' => $this->cocShowsScore,
            'execution' => $this->execution,
            'participant_category' => $this->participantCategory,
            'zonation' => $this->zonation,
            'lesson_hours' => $this->lessonHours,
            'participant_target' => $this->participantTarget,
            'registration_quota' => $this->registrationQuota,
        ];

        return array_intersect_key(self::REQUIRED_FOR_FINAL, array_filter($values, fn (mixed $value): bool => $value === null));
    }
}
