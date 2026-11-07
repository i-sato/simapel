<?php

namespace App\Http\Requests\Admin;

use App\Domain\Identity\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi bentuk input data pegawai. Aturan bisnis (keunikan, Super Admin
 * terakhir) ditegakkan use case.
 */
final class EmployeeRequest extends FormRequest
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
        $rules = [
            'nip' => ['required', 'string', 'regex:/\A[0-9]{18}\z/'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'pangkat_golongan' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:150'],
            'role' => ['required', Rule::enum(Role::class)],
            'can_be_admin' => ['sometimes', 'boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['password'] = ['required', 'string', 'confirmed', Password::defaults()];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['nip.regex' => 'NIP harus terdiri dari tepat 18 digit angka.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pangkat_golongan' => 'pangkat/golongan ruang',
            'jabatan' => 'jabatan',
            'password' => 'kata sandi awal',
        ];
    }
}
