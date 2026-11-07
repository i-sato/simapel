<?php

namespace App\Http\Controllers;

use App\Domain\Organization\TeamRole;
use App\Models\TeamMembership;
use App\Models\User;
use App\Models\WorkTeam;
use App\View\TeamSopStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar tim kerja dan anggotanya: terbuka (read-only) untuk semua pegawai.
 */
final class WorkTeamController extends Controller
{
    public function index(Request $request, TeamSopStatus $sopStatus): View
    {
        $years = WorkTeam::query()->distinct()->orderByDesc('year')->pluck('year')->map(fn ($year): int => (int) $year)->all();
        $year = in_array($request->integer('year'), $years, true) ? $request->integer('year') : ($years[0] ?? (int) now()->year);

        $teams = WorkTeam::query()
            ->where('year', $year)
            ->with(['memberships' => fn ($query) => $query->with('user')])
            ->orderBy('code')
            ->get();

        return view('work-teams.index', ['teams' => $teams, 'years' => $years, 'year' => $year, 'sopStatus' => $sopStatus->forAll()]);
    }

    public function show(Request $request, WorkTeam $team, TeamSopStatus $sopStatus): View
    {
        $memberships = $team->memberships()->with('user')->get()
            ->sortBy(fn (TeamMembership $membership): string => array_search($membership->role, TeamRole::cases(), true).($membership->user->name ?? ''))
            ->values();

        $candidates = $request->user()?->can('manage-organization')
            ? User::query()->where('is_active', true)->whereNotIn('id', $memberships->pluck('user_id'))->orderBy('name')->get(['id', 'name', 'jabatan'])
            : collect();

        return view('work-teams.show', [
            'team' => $team,
            'memberships' => $memberships,
            'candidates' => $candidates,
            'roles' => TeamRole::options(),
            'sop' => $sopStatus->for($team->code),
        ]);
    }
}
