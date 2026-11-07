<?php

namespace App\Http\Requests\Admin;

use App\Domain\Organization\TeamRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TeamMemberRequest extends FormRequest
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
        $rules = ['role' => ['required', Rule::enum(TeamRole::class)]];

        if ($this->isMethod('post')) {
            $rules['employee_id'] = ['required', 'integer'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['employee_id' => 'pegawai', 'role' => 'peran di tim'];
    }
}
