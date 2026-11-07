<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-organization') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['password' => ['required', 'string', 'confirmed', Password::defaults()]];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['password' => 'kata sandi sementara'];
    }
}
