<?php

namespace App\Http\Requests\Training;

use App\Domain\Assignment\TrainingAssignment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi format isian penugasan. Kelayakan (Admin, aktif) dan bentrok ditegakkan
 * oleh Domain/Application.
 */
final class TrainingAssignmentsRequest extends FormRequest
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
        return [
            'admins' => ['nullable', 'array', 'max:500'],
            'admins.*' => ['integer', 'distinct'],
            'panitia' => ['nullable', 'array', 'max:500'],
            'panitia.*' => ['integer', 'distinct'],
            'force_reason' => ['nullable', 'string', 'max:'.TrainingAssignment::MAX_REASON],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'admins' => 'Admin',
            'panitia' => 'Panitia',
            'force_reason' => 'alasan penugasan meskipun bentrok',
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(string $key): array
    {
        $values = $this->input($key, []);

        return is_array($values) ? array_values(array_map(intval(...), $values)) : [];
    }

    public function forceReason(): ?string
    {
        return $this->filled('force_reason') ? $this->string('force_reason')->trim()->toString() : null;
    }
}
