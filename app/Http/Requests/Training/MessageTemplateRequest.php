<?php

namespace App\Http\Requests\Training;

use App\Domain\Messaging\TemplateBody;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi format isi template. Placeholder, elemen wajib, dan batas jumlah
 * ditegakkan oleh Domain (App\Domain\Messaging).
 */
final class MessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hak akses diperiksa gate pada route dan oleh use case.
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        // Batas byte longgar; batas karakter yang sebenarnya ada di TemplateBody.
        return ['body' => ['required', 'string', 'max:'.(TemplateBody::MAX_LENGTH * 4)]];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'isi template'];
    }
}
