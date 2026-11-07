<?php

namespace App\Http\Controllers\Admin;

use App\Application\Sop\ActivateSopProvision;
use App\Application\Sop\CopySopProvisionFromPreviousYear;
use App\Application\Sop\CreateSopProvisionDraft;
use App\Application\Sop\DeleteSopProvisionDraft;
use App\Application\Sop\UpdateSopProvisionDraft;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\SopProvisionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SopProvisionRequest;
use App\Models\SopProvisionRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SopProvisionController extends Controller
{
    public function create(Request $request): View
    {
        $data = $request->validate([
            'team' => ['required', Rule::enum(WorkTeamCode::class)],
            'year' => ['required', 'integer', 'between:2020,2100'],
        ]);

        return view('settings.sop-provision-form', [
            'provision' => null,
            'team' => WorkTeamCode::from($data['team']),
            'year' => (int) $data['year'],
        ]);
    }

    public function store(SopProvisionRequest $request, CreateSopProvisionDraft $createDraft): RedirectResponse
    {
        $year = $request->integer('fiscal_year');
        $createDraft->handle(
            WorkTeamCode::from($request->string('team')->toString()),
            $year,
            $request->toInput(),
            (int) $request->user()?->getAuthIdentifier(),
        );

        return redirect()->route('settings.index', ['year' => $year])->with('success', 'Draf ketentuan SOP dibuat.');
    }

    public function edit(SopProvisionRecord $provision): View|RedirectResponse
    {
        if ($provision->status !== SopProvisionStatus::Draf) {
            return redirect()->route('settings.index', ['year' => $provision->fiscal_year])
                ->withErrors(['general' => 'Hanya ketentuan berstatus Draf yang dapat diubah. Buat versi baru untuk revisi.']);
        }

        return view('settings.sop-provision-form', [
            'provision' => $provision,
            'team' => $provision->team_code,
            'year' => $provision->fiscal_year,
        ]);
    }

    public function update(SopProvisionRequest $request, SopProvisionRecord $provision, UpdateSopProvisionDraft $updateDraft): RedirectResponse
    {
        $updateDraft->handle($provision->id, $request->toInput(), (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('settings.index', ['year' => $provision->fiscal_year])->with('success', 'Draf ketentuan SOP diperbarui.');
    }

    public function destroy(Request $request, SopProvisionRecord $provision, DeleteSopProvisionDraft $deleteDraft): RedirectResponse
    {
        $deleteDraft->handle($provision->id, (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('settings.index', ['year' => $provision->fiscal_year])->with('success', 'Draf ketentuan SOP dihapus.');
    }

    public function activate(Request $request, SopProvisionRecord $provision, ActivateSopProvision $activate): RedirectResponse
    {
        $activate->handle($provision->id, (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('settings.index', ['year' => $provision->fiscal_year])
            ->with('success', sprintf('Ketentuan %s %d versi %d diberlakukan.', $provision->team_code->value, $provision->fiscal_year, $provision->version));
    }

    public function copy(Request $request, CopySopProvisionFromPreviousYear $copy): RedirectResponse
    {
        $data = $request->validate([
            'team' => ['required', Rule::enum(WorkTeamCode::class)],
            'year' => ['required', 'integer', 'between:2021,2100'],
        ]);

        $copy->handle(WorkTeamCode::from($data['team']), (int) $data['year'], (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('settings.index', ['year' => $data['year']])
            ->with('success', sprintf('Draf %s %d disalin dari tahun %d. Periksa sebelum diberlakukan.', $data['team'], $data['year'], $data['year'] - 1));
    }
}
