<?php

namespace App\Http\Requests\Training;

use App\Application\Administration\DocumentInput;
use App\Domain\Administration\AdministrativeDocument;
use App\Domain\Administration\DocumentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi format isian dokumen administrasi. Kelengkapan status Selesai dan hak akses
 * ditegakkan Domain/Application.
 */
final class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(DocumentStatus::class)],
            'number' => ['nullable', 'string', 'max:'.AdministrativeDocument::MAX_NUMBER_LENGTH],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'file_url' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['status' => 'status', 'number' => 'nomor dokumen', 'date' => 'tanggal dokumen', 'file_url' => 'link berkas'];
    }

    public function toInput(): DocumentInput
    {
        return new DocumentInput(
            status: $this->string('status')->toString(),
            number: $this->filled('number') ? $this->string('number')->trim()->toString() : null,
            date: $this->filled('date') ? $this->string('date')->toString() : null,
            fileUrl: $this->filled('file_url') ? $this->string('file_url')->trim()->toString() : null,
        );
    }
}
