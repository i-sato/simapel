<?php

namespace App\Http\Controllers\Admin;

use App\Application\Organization\AssignTeamMember;
use App\Application\Organization\RemoveTeamMember;
use App\Domain\Organization\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamMemberRequest;
use App\Models\User;
use App\Models\WorkTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TeamMemberController extends Controller
{
    public function store(TeamMemberRequest $request, WorkTeam $team, AssignTeamMember $assignTeamMember): RedirectResponse
    {
        $assignTeamMember->handle(
            $team->id,
            $request->integer('employee_id'),
            TeamRole::from($request->string('role')->toString()),
            (int) $request->user()?->getAuthIdentifier(),
        );

        return back()->with('success', 'Anggota tim disimpan.');
    }

    public function update(TeamMemberRequest $request, WorkTeam $team, User $employee, AssignTeamMember $assignTeamMember): RedirectResponse
    {
        $assignTeamMember->handle(
            $team->id,
            $employee->id,
            TeamRole::from($request->string('role')->toString()),
            (int) $request->user()?->getAuthIdentifier(),
        );

        return back()->with('success', 'Peran anggota diperbarui.');
    }

    public function destroy(Request $request, WorkTeam $team, User $employee, RemoveTeamMember $removeTeamMember): RedirectResponse
    {
        $removeTeamMember->handle($team->id, $employee->id, (int) $request->user()?->getAuthIdentifier());

        return back()->with('success', 'Anggota dikeluarkan dari tim.');
    }
}
