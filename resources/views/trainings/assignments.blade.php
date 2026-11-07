@extends('layouts.app')

@section('title', 'Penugasan Pelatihan')

@php($selectedAdmins = array_map('intval', (array) old('admins', $adminIds)))
@php($selectedPanitia = array_map('intval', (array) old('panitia', $panitiaIds)))

@section('content')
<div class="page-head">
    <div>
        <h1>Atur penugasan</h1>
        <p>{{ $training->name }} · {{ $training->team->label() }} {{ $training->team->year }}</p>
    </div>
    <a class="btn btn-outline" href="{{ route('trainings.show', $training) }}">← Kembali</a>
</div>

@if ($range === null)
    <div class="notice warning" role="note">
        <strong>Jadwal belum lengkap.</strong>
        <span class="muted">Bentrok beban kerja dihitung dari awal pendaftaran sampai akhir pelatihan, sehingga belum dapat diperiksa.</span>
    </div>
@else
    <p class="muted">Rentang beban kerja: <strong>{{ $range->label() }}</strong> (awal pendaftaran sampai akhir pelatihan). Bentrok dihitung lintas tim kerja.</p>
@endif

<form method="POST" action="{{ route('trainings.assignments.update', $training) }}" class="stack">
    @csrf
    @method('PUT')

    @if ($conflicts !== [] || $errors->has('force_reason'))
        <section class="notice warning" aria-labelledby="conflict-heading">
            <h2 id="conflict-heading" class="mb-0">Ada pegawai dengan penugasan yang beririsan</h2>
            <p class="muted">{{ count($conflicts) }} pegawai pada daftar ini sudah bertugas di pelatihan lain pada rentang yang sama (badge kuning). Penugasan baru untuk pegawai tersebut wajib disertai alasan; alasan tercatat di audit log.</p>
            <div class="field">
                <label for="force_reason">Alasan tetap menugaskan meskipun bentrok</label>
                <textarea id="force_reason" name="force_reason" rows="3" maxlength="{{ \App\Domain\Assignment\TrainingAssignment::MAX_REASON }}">{{ old('force_reason') }}</textarea>
                @error('force_reason')<p class="help text-accent">{{ $message }}</p>@enderror
            </div>
        </section>
    @endif

    <section class="card" aria-labelledby="employees-heading">
        <h2 id="employees-heading">Pegawai</h2>
        <p class="muted">Admin hanya dari pegawai bertanda "Admin" (dapat menjadi Admin). Panitia boleh pegawai aktif mana pun, termasuk Ketua dan Sekretaris tim lain. Pegawai yang sudah ditugaskan tercentang.</p>
        @error('admins')<p class="help text-accent">{{ $message }}</p>@enderror
        @error('panitia')<p class="help text-accent">{{ $message }}</p>@enderror
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Pegawai</th><th>Keterangan</th><th class="text-right">Admin</th><th class="text-right">Panitia</th></tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                        @php($isAdmin = in_array($employee->id, $selectedAdmins, true))
                        <tr>
                            <td>
                                {{ $employee->name }}
                                @if ($employee->jabatan)
                                    <div class="muted">{{ $employee->jabatan }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="pill-row">
                                    @foreach ($teamRoles[$employee->id] ?? [] as $label)
                                        <span class="badge blue">{{ $label }}</span>
                                    @endforeach
                                    @if ($employee->can_be_admin)
                                        <span class="badge success">Admin</span>
                                    @endif
                                    @unless ($employee->is_active)
                                        <span class="badge danger">Nonaktif</span>
                                    @endunless
                                    @foreach ($conflicts[$employee->id] ?? [] as $conflict)
                                        <span class="badge warning" title="Bentrok dengan &quot;{{ $conflict->trainingName }}&quot;">⚠ {{ $conflict->label() }}<span class="sr-only"> (bentrok dengan {{ $conflict->trainingName }})</span></span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-right">
                                <label class="check-item" for="admin-{{ $employee->id }}">
                                    <input id="admin-{{ $employee->id }}" type="checkbox" name="admins[]" value="{{ $employee->id }}" @checked($isAdmin) @disabled(! $employee->can_be_admin && ! $isAdmin)>
                                    <span class="sr-only">Admin: {{ $employee->name }}</span>
                                </label>
                            </td>
                            <td class="text-right">
                                <label class="check-item" for="panitia-{{ $employee->id }}">
                                    <input id="panitia-{{ $employee->id }}" type="checkbox" name="panitia[]" value="{{ $employee->id }}" @checked(in_array($employee->id, $selectedPanitia, true))>
                                    <span class="sr-only">Panitia: {{ $employee->name }}</span>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="row">
        <button type="submit" class="btn">Simpan penugasan</button>
        <a class="btn btn-outline" href="{{ route('trainings.show', $training) }}">Batal</a>
    </div>
</form>
@endsection
