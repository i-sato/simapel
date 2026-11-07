<?php

namespace App\Http\Requests\Admin;

use App\Domain\Settings\GlobalSetting;
use Illuminate\Foundation\Http\FormRequest;

final class GlobalSettingsRequest extends FormRequest
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
        $rules = [];

        foreach (GlobalSetting::cases() as $setting) {
            $rules[$setting->value] = $setting === GlobalSetting::ClosingSoonDays
                ? ['required', 'integer', 'between:1,60']
                : ['nullable', 'string', 'max:2048'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (GlobalSetting::cases() as $setting) {
            $attributes[$setting->value] = mb_strtolower($setting->label());
        }

        return $attributes;
    }
}
