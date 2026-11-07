<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class TeamGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-organization') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['registration_guide_url' => ['nullable', 'string', 'max:2048']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['registration_guide_url' => 'link panduan pendaftaran'];
    }
}
