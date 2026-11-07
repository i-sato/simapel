<?php

namespace App\Http\Requests\Training;

use App\Application\Training\TrainingInput;
use App\Domain\Partner\Partner;
use App\Domain\Shared\WorkRegion;
use App\Domain\Training\Certification;
use App\Domain\Training\ExecutionMode;
use App\Domain\Training\ParticipantCategory;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingLevel;
use App\Domain\Training\TrainingMethod;
use App\Domain\Training\Zonation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi format isian pelatihan. Aturan bisnis (kelengkapan, konsistensi tanggal,
 * link daftar peserta, learning path, hak akses) ditegakkan di Domain/Application.
 */
final class TrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hak akses diperiksa gate pada route dan oleh use case.
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $url = ['nullable', 'string', 'max:2048'];
        $date = ['nullable', 'date_format:Y-m-d'];
        $number = ['nullable', 'integer', 'between:1,'.TrainingDetails::MAX_NUMBER];

        return [
            'work_team_id' => ['required', 'integer', Rule::exists('work_teams', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'registration_url' => $url,
            'registration_start' => $date,
            'registration_end' => $date,
            'training_start' => $date,
            'training_end' => $date,
            'partner_location' => ['nullable', 'string', 'max:255'],
            'participant_list_url' => $url,
            'method' => ['nullable', Rule::enum(TrainingMethod::class)],
            'batch' => $number,
            'syllabus_url' => $url,
            'level' => ['nullable', Rule::enum(TrainingLevel::class)],
            'certification' => ['nullable', Rule::enum(Certification::class)],
            'coc_shows_score' => ['nullable', 'in:0,1'],
            'execution' => ['nullable', Rule::enum(ExecutionMode::class)],
            'participant_category' => ['nullable', Rule::enum(ParticipantCategory::class)],
            'zonation' => ['nullable', Rule::enum(Zonation::class)],
            'lesson_hours' => $number,
            'participant_target' => $number,
            'registration_quota' => $number,
            'learning_path_id' => ['nullable', 'integer', Rule::exists('learning_paths', 'id')],
            'new_learning_path' => ['nullable', 'string', 'max:150'],
            'work_region' => ['nullable', Rule::enum(WorkRegion::class)],
            'registration_closes_at' => ['nullable', 'string', 'max:8'],
            'partner_name' => ['nullable', 'string', 'max:'.Partner::MAX_NAME],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'work_team_id' => 'tim kerja',
            'name' => 'nama/tema pelatihan',
            'registration_url' => 'link pendaftaran',
            'registration_start' => 'awal pendaftaran',
            'registration_end' => 'akhir pendaftaran',
            'training_start' => 'awal pelatihan',
            'training_end' => 'akhir pelatihan',
            'partner_location' => 'mitra/lokasi',
            'participant_list_url' => 'link daftar peserta',
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
            'learning_path_id' => 'learning path',
            'new_learning_path' => 'nama learning path baru',
            'work_region' => 'wilayah kerja',
            'registration_closes_at' => 'jam penutupan pendaftaran',
            'partner_name' => 'mitra',
        ];
    }

    public function teamId(): int
    {
        return $this->integer('work_team_id');
    }

    public function toInput(): TrainingInput
    {
        return new TrainingInput(
            name: $this->string('name')->toString(),
            registrationUrl: $this->text('registration_url'),
            registrationStart: $this->text('registration_start'),
            registrationEnd: $this->text('registration_end'),
            trainingStart: $this->text('training_start'),
            trainingEnd: $this->text('training_end'),
            partnerLocation: $this->text('partner_location'),
            participantListUrl: $this->text('participant_list_url'),
            method: $this->text('method'),
            batch: $this->number('batch'),
            syllabusUrl: $this->text('syllabus_url'),
            level: $this->text('level'),
            certification: $this->text('certification'),
            cocShowsScore: $this->filled('coc_shows_score') ? $this->boolean('coc_shows_score') : null,
            execution: $this->text('execution'),
            participantCategory: $this->text('participant_category'),
            zonation: $this->text('zonation'),
            lessonHours: $this->number('lesson_hours'),
            participantTarget: $this->number('participant_target'),
            registrationQuota: $this->number('registration_quota'),
            learningPathId: $this->number('learning_path_id'),
            newLearningPathName: $this->text('new_learning_path'),
            region: $this->text('work_region'),
            registrationClosesAt: $this->text('registration_closes_at'),
            partnerName: $this->text('partner_name'),
        );
    }

    private function text(string $key): ?string
    {
        return $this->filled($key) ? $this->string($key)->trim()->toString() : null;
    }

    private function number(string $key): ?int
    {
        return $this->filled($key) ? $this->integer($key) : null;
    }
}
