<?php

namespace App\Http\Controllers;

use App\Application\Assignment\TrainingWorkload;
use App\Application\Assignment\UpdateTrainingAssignments;
use App\Application\Training\Contracts\TrainingRepository;
use App\Domain\Assignment\AssignmentRole;
use App\Http\Requests\Training\TrainingAssignmentsRequest;
use App\Models\TrainingRecord;
use App\Models\User;
use App\View\TeamRoleBadges;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TrainingAssignmentController extends Controller
{
    public function edit(TrainingRecord $training, TrainingRepository $trainings, TrainingWorkload $workload, TeamRoleBadges $teamRoles): View
    {
        $training->load(['team', 'assignments']);
        $assigned = $training->assignments;
        $domain = $trainings->find($training->id);

        // Pegawai aktif, ditambah pegawai nonaktif yang masih tercatat agar dapat dicabut.
        $employees = User::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $assigned->pluck('user_id')))
            ->orderBy('name')
            ->get(['id', 'name', 'jabatan', 'is_active', 'can_be_admin']);

        $conflicts = $domain === null ? [] : $workload->conflictsFor($domain);

        return view('trainings.assignments', [
            'training' => $training,
            'employees' => $employees,
            'adminIds' => $assigned->where('role', AssignmentRole::Admin)->pluck('user_id')->all(),
            'panitiaIds' => $assigned->where('role', AssignmentRole::Panitia)->pluck('user_id')->all(),
            'teamRoles' => $teamRoles->forYear($training->team->year),
            'conflicts' => $conflicts,
            'range' => $domain?->details->schedule->assignmentRange(),
        ]);
    }

    public function update(TrainingAssignmentsRequest $request, TrainingRecord $training, UpdateTrainingAssignments $update): RedirectResponse
    {
        $plan = $update->handle(
            (int) $request->user()?->getAuthIdentifier(),
            $training->id,
            $request->ids('admins'),
            $request->ids('panitia'),
            $request->forceReason(),
        );

        $message = $plan->isEmpty()
            ? 'Tidak ada perubahan penugasan.'
            : sprintf('Penugasan disimpan: %d ditambahkan, %d dicabut%s.', count($plan->added), count($plan->removed), $plan->conflicts === [] ? '' : ', '.count($plan->conflicts).' dengan alasan bentrok');

        return redirect()->route('trainings.show', $training)->with('success', $message);
    }
}
