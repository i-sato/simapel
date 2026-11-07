<?php

namespace App\Http\Requests\Admin;

use App\Application\Sop\SopProvisionInput;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\AdministrativeDeadlines;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SopProvisionRequest extends FormRequest
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
        $days = ['required', 'integer', 'between:0,'.AdministrativeDeadlines::MAX_DAYS];
        $rules = [
            'sop_number' => ['nullable', 'string', 'max:100'],
            'sop_effective_date' => ['nullable', 'date_format:Y-m-d'],
            'sop_url' => ['nullable', 'string', 'max:2048'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'bap_days' => $days,
            'sk_days' => $days,
            'st_days' => $days,
            'ready_days' => $days,
            'note' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->isMethod('post')) {
            $rules['team'] = ['required', Rule::enum(WorkTeamCode::class)];
            $rules['fiscal_year'] = ['required', 'integer', 'between:2020,2100'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sop_number' => 'nomor SOP',
            'sop_effective_date' => 'tanggal efektif SOP',
            'sop_url' => 'link SOP',
            'effective_from' => 'tanggal mulai berlaku',
            'bap_days' => 'tenggat BAP',
            'sk_days' => 'tenggat SK',
            'st_days' => 'tenggat ST',
            'ready_days' => 'tenggat administrasi siap',
            'fiscal_year' => 'tahun anggaran',
            'team' => 'tim kerja',
        ];
    }

    public function toInput(): SopProvisionInput
    {
        return new SopProvisionInput(
            sopNumber: $this->input('sop_number'),
            sopEffectiveDate: $this->input('sop_effective_date'),
            sopUrl: $this->input('sop_url'),
            effectiveFrom: $this->string('effective_from')->toString(),
            bapDays: $this->integer('bap_days'),
            skDays: $this->integer('sk_days'),
            stDays: $this->integer('st_days'),
            readyDays: $this->integer('ready_days'),
            note: $this->input('note'),
        );
    }
}
