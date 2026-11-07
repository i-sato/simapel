<?php

namespace App\Http\Controllers;

use App\Application\Administration\DocumentAccess;
use App\Application\Administration\TrainingAlertsQuery;
use App\Application\Assignment\TrainingWorkload;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\CreateTrainingDraft;
use App\Application\Training\FinalizeTraining;
use App\Application\Training\TrainingAccess;
use App\Application\Training\TrainingPreparation;
use App\Application\Training\UpdateTraining;
use App\Domain\Administration\DocumentDeadline;
use App\Domain\Administration\DocumentType;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Training\Certification;
use App\Domain\Training\ExecutionMode;
use App\Domain\Training\LearningPathSequence;
use App\Domain\Training\ParticipantCategory;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingLevel;
use App\Domain\Training\TrainingMethod;
use App\Domain\Training\TrainingPhase;
use App\Domain\Training\TrainingStatus;
use App\Domain\Training\Zonation;
use App\Http\Requests\Training\TrainingRequest;
use App\Models\AdministrativeDocumentRecord;
use App\Models\LearningPathRecord;
use App\Models\TeamMembership;
use App\Models\TrainingRecord;
use App\Models\WorkTeam;
use App\View\TeamRoleBadges;
use App\View\TeamSopStatus;
use App\View\TrainingPhases;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class TrainingController extends Controller
{
    public function index(Request $request, TrainingPhases $phases, TeamSopStatus $sopStatus, TrainingRepository $repository, TrainingAlertsQuery $alertsQuery): View
    {
        $filters = $request->validate([
            'team' => ['nullable', 'string', 'in:'.implode(',', array_column(WorkTeamCode::cases(), 'value'))],
            'status' => ['nullable', 'string', 'in:'.implode(',', [...array_column(TrainingStatus::cases(), 'value'), ...array_column(TrainingPhase::cases(), 'value')])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $query = TrainingRecord::query()->with(['team', 'learningPath']);

        if (! empty($filters['team'])) {
            $query->whereHas('team', fn ($team) => $team->where('code', $filters['team']));
        }

        $status = $filters['status'] ?? null;

        if ($status !== null && ($statusCase = TrainingStatus::tryFrom($status)) !== null) {
            $query->where('status', $statusCase);
        } elseif ($status !== null && ($phase = TrainingPhase::tryFrom($status)) !== null) {
            $phases->scope($query, $phase);
        }

        // Periode: pelatihan yang tanggal pelaksanaannya beririsan dengan rentang filter.
        if (! empty($filters['from'])) {
            $query->whereNotNull('training_end')->where('training_end', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereNotNull('training_start')->where('training_start', '<=', $filters['to']);
        }

        $trainings = $query
            ->orderByRaw('training_start IS NULL')
            ->orderByDesc('training_start')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $alerts = [];

        foreach ($trainings as $record) {
            $domain = $repository->find($record->id);
            $alerts[$record->id] = $domain === null ? [] : $alertsQuery->for($domain);
        }

        return view('trainings.index', [
            'trainings' => $trainings,
            'alerts' => $alerts,
            'phases' => $phases,
            'filters' => $filters,
            'teamCodes' => WorkTeamCode::cases(),
            'statuses' => TrainingStatus::cases(),
            'phaseCases' => TrainingPhase::cases(),
            'sopStatus' => $sopStatus->forAll(),
        ]);
    }

    public function create(Request $request, TrainingAccess $access, TeamSopStatus $sopStatus): View
    {
        $teams = $this->teamsFor($access->creatableTeamIds((int) $request->user()?->getAuthIdentifier()));

        return view('trainings.form', $this->formData(new TrainingRecord, $teams, $sopStatus) + [
            'selectedTeamId' => $request->integer('team') ?: null,
        ]);
    }

    public function store(TrainingRequest $request, CreateTrainingDraft $createDraft): RedirectResponse
    {
        $id = $createDraft->handle((int) $request->user()?->getAuthIdentifier(), $request->teamId(), $request->toInput());

        return redirect()->route('trainings.show', $id)->with('success', 'Draft pelatihan dibuat.');
    }

    public function show(
        Request $request,
        TrainingRecord $training,
        TrainingRepository $trainings,
        TrainingPreparation $preparation,
        TrainingPhases $phases,
        TeamSopStatus $sopStatus,
        TrainingWorkload $workload,
        TeamRoleBadges $teamRoles,
        TrainingAlertsQuery $alertsQuery,
        DocumentAccess $documentAccess,
    ): View {
        $training->load(['team', 'learningPath', 'assignments' => fn ($query) => $query->with('user')->orderBy('role')->orderBy('id')]);
        $domain = $trainings->find($training->id);
        $sequence = null;

        if ($training->learningPath !== null) {
            $byLevel = [];

            foreach ($training->learningPath->trainings()->whereNotNull('level')->get() as $member) {
                if ($member->level !== null) {
                    $byLevel[$member->level->value] = $member;
                }
            }

            $sequence = new LearningPathSequence($byLevel);
        }

        return view('trainings.show', [
            'training' => $training,
            'phase' => $phases->for($training),
            'warnings' => $domain === null ? [] : $preparation->warningsFor($domain),
            'missing' => $domain === null || $domain->isFinal() ? [] : $domain->details->missingForFinal(),
            'sopStatus' => $sopStatus->for($training->team->code),
            'sequence' => $sequence,
            'assignments' => $training->assignments,
            'alerts' => $domain === null ? [] : $alertsQuery->for($domain),
            'deadlines' => self::deadlines($domain),
            'documents' => AdministrativeDocumentRecord::query()->where('training_id', $training->id)->with('assignee')->get()
                ->sortBy(fn (AdministrativeDocumentRecord $document): int => (int) array_search($document->type, DocumentType::cases(), true))->values(),
            'canDelegate' => $documentAccess->canDelegate((int) $request->user()?->getAuthIdentifier(), $training->id),
            'updatable' => $documentAccess->updatableIn((int) $request->user()?->getAuthIdentifier(), $training->id),
            'secretaries' => TeamMembership::query()->where('work_team_id', $training->work_team_id)->where('role', TeamRole::Sekretaris)->with('user')->get(),
            'teamRoles' => $teamRoles->forYear($training->team->year),
            'conflicts' => $domain === null ? [] : $workload->conflictsFor($domain, array_values(array_unique(array_map(intval(...), $training->assignments->pluck('user_id')->all())))),
        ]);
    }

    public function edit(Request $request, TrainingRecord $training, TrainingAccess $access, TeamSopStatus $sopStatus): View
    {
        $training->load('team');
        $teamIds = $training->status === TrainingStatus::Final
            ? [$training->work_team_id]
            : $this->withCurrent($access->creatableTeamIds((int) $request->user()?->getAuthIdentifier()), $training->work_team_id);

        return view('trainings.form', $this->formData($training, $this->teamsFor($teamIds), $sopStatus) + [
            'selectedTeamId' => $training->work_team_id,
        ]);
    }

    public function update(TrainingRequest $request, TrainingRecord $training, UpdateTraining $updateTraining): RedirectResponse
    {
        $changed = $updateTraining->handle((int) $request->user()?->getAuthIdentifier(), $training->id, $request->teamId(), $request->toInput());

        return redirect()->route('trainings.show', $training)->with('success', $changed ? 'Pelatihan diperbarui.' : 'Tidak ada perubahan.');
    }

    public function finalize(Request $request, TrainingRecord $training, FinalizeTraining $finalize): RedirectResponse
    {
        $warnings = $finalize->handle((int) $request->user()?->getAuthIdentifier(), $training->id);

        $response = redirect()->route('trainings.show', $training)->with('success', 'Pelatihan difinalisasi. Salinan ketentuan SOP tim kerja disimpan.');

        return $warnings === [] ? $response : $response->with('warning', 'Peringatan: '.implode(' ', $warnings));
    }

    /**
     * Tenggat tiap dokumen dan administrasi siap dari salinan ketentuan pada pelatihan.
     *
     * @return array<string, \DateTimeImmutable>
     */
    private static function deadlines(?Training $training): array
    {
        $registrationEnd = $training?->details->schedule->registrationEnd;
        $deadlines = $training?->sopSnapshot?->deadlines;

        if ($registrationEnd === null || $deadlines === null) {
            return [];
        }

        $result = ['ready' => DocumentDeadline::ready($registrationEnd, $deadlines)];

        foreach (DocumentType::cases() as $type) {
            $result[$type->value] = DocumentDeadline::for($type, $registrationEnd, $deadlines);
        }

        return $result;
    }

    /**
     * @param  list<int>|null  $teamIds  null berarti semua tim
     * @return Collection<int, WorkTeam>
     */
    private function teamsFor(?array $teamIds): Collection
    {
        return WorkTeam::query()
            ->when($teamIds !== null, fn ($query) => $query->whereIn('id', $teamIds ?? []))
            ->orderByDesc('year')
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  list<int>|null  $teamIds
     * @return list<int>|null
     */
    private function withCurrent(?array $teamIds, int $currentTeamId): ?array
    {
        return $teamIds === null ? null : array_values(array_unique([...$teamIds, $currentTeamId]));
    }

    /**
     * @param  Collection<int, WorkTeam>  $teams
     * @return array<string, mixed>
     */
    private function formData(TrainingRecord $training, Collection $teams, TeamSopStatus $sopStatus): array
    {
        return [
            'training' => $training,
            'teams' => $teams,
            'paths' => LearningPathRecord::query()
                ->whereIn('work_team_id', $teams->pluck('id'))
                ->with('team')
                ->orderBy('name')
                ->get()
                ->groupBy(fn (LearningPathRecord $path): string => $path->team->label().' '.$path->team->year),
            'sopStatus' => $sopStatus->forAll(),
            'methods' => TrainingMethod::cases(),
            'levels' => TrainingLevel::cases(),
            'certifications' => Certification::cases(),
            'executions' => ExecutionMode::cases(),
            'participantCategories' => ParticipantCategory::cases(),
            'zonations' => Zonation::cases(),
            'required' => TrainingDetails::REQUIRED_FOR_FINAL,
        ];
    }
}
