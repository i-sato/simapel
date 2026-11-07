<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ImportEmployeesRequest extends FormRequest
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
        return ['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['file' => 'berkas CSV'];
    }
}
