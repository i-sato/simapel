@props(['training', 'phase' => null])
<span @class(['badge', 'success' => $training->status === \App\Domain\Training\TrainingStatus::Final, 'warning' => $training->status === \App\Domain\Training\TrainingStatus::Draft])>{{ $training->status->label() }}</span>
@if ($phase)
    <span class="{{ \App\View\TrainingPhases::badgeClass($phase) }}">{{ $phase->label() }}</span>
@else
    <span class="badge">Jadwal belum lengkap</span>
@endif
