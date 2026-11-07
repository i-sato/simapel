<?php

namespace App\Http\Controllers\Admin;

use App\Application\Organization\UpdateTeamGuideUrl;
use App\Application\Settings\GlobalSettings;
use App\Application\Settings\UpdateGlobalSettings;
use App\Application\Shared\Contracts\Clock;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Settings\GlobalSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GlobalSettingsRequest;
use App\Http\Requests\Admin\TeamGuideRequest;
use App\Models\SopProvisionRecord;
use App\Models\WorkTeam;
use App\View\TeamSopStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function index(Request $request, GlobalSettings $settings, TeamSopStatus $sopStatus, Clock $clock): View
    {
        $currentYear = (int) $clock->now()->format('Y');
        $years = SopProvisionRecord::query()->distinct()->pluck('fiscal_year')->map(fn ($year): int => (int) $year)
            ->merge([$currentYear - 1, $currentYear, $currentYear + 1])
            ->unique()->sortDesc()->values()->all();
        $year = in_array($request->integer('year'), $years, true) ? $request->integer('year') : $currentYear;

        $provisions = SopProvisionRecord::query()
            ->where('fiscal_year', $year)
            ->orderBy('version')
            ->get()
            ->groupBy(fn (SopProvisionRecord $record): string => $record->team_code->value);

        return view('settings.index', [
            'settings' => $settings->all(),
            'globalSettings' => GlobalSetting::cases(),
            'teams' => WorkTeam::query()->where('year', $currentYear)->orderBy('code')->get(),
            'teamCodes' => WorkTeamCode::cases(),
            'provisions' => $provisions,
            'sopStatus' => $sopStatus->forAll(),
            'years' => $years,
            'year' => $year,
            'currentYear' => $currentYear,
        ]);
    }

    public function updateGlobal(GlobalSettingsRequest $request, UpdateGlobalSettings $updateGlobalSettings): RedirectResponse
    {
        $input = [];

        foreach (GlobalSetting::cases() as $setting) {
            $value = $request->input($setting->value);
            $input[$setting->value] = is_scalar($value) ? (string) $value : null;
        }

        $changed = $updateGlobalSettings->handle($input, (int) $request->user()?->getAuthIdentifier());

        return back()->with('success', $changed === 0 ? 'Tidak ada perubahan pengaturan.' : 'Pengaturan umum disimpan.');
    }

    public function updateTeamGuide(TeamGuideRequest $request, WorkTeam $team, UpdateTeamGuideUrl $updateTeamGuideUrl): RedirectResponse
    {
        $updateTeamGuideUrl->handle($team->id, $request->input('registration_guide_url'), (int) $request->user()?->getAuthIdentifier());

        return back()->with('success', 'Link panduan '.$team->label().' disimpan.');
    }
}
