@extends('layouts.app')

@php($editing = $training->exists)
@php($isFinal = $editing && $training->status === \App\Domain\Training\TrainingStatus::Final)
@php($req = fn (string $field): bool => array_key_exists($field, $required))

@section('title', $editing ? 'Ubah Pelatihan' : 'Draft Pelatihan')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $editing ? 'Ubah pelatihan' : 'Draft pelatihan baru' }}</h1>
        <p>
            @if ($isFinal)
                Pelatihan sudah Final: data harus tetap lengkap, tim kerja dan salinan ketentuan SOP tidak berubah.
            @else
                Draft boleh diisi bertahap. Kolom bertanda <span class="muted">*</span> wajib lengkap sebelum finalisasi.
            @endif
        </p>
    </div>
    <a class="btn btn-outline" href="{{ $editing ? route('trainings.show', $training) : route('trainings.index') }}">← Kembali</a>
</div>

<section class="card" aria-labelledby="sop-heading">
    <h2 id="sop-heading">SOP tim kerja</h2>
    <p class="muted">Ketentuan SOP yang berlaku menjadi acuan tenggat BAP, SK, ST, dan administrasi siap.</p>
    <ul class="list-clean stack">
        @foreach ($teams->unique(fn ($team) => $team->code->value) as $team)
            <li><strong>{{ $team->code->value }}</strong> · <x-sop.team-link :sop="$sopStatus[$team->code->value]" :team="$team->code->value" :show-fallback="auth()->user()?->can('manage-organization')" /></li>
        @endforeach
    </ul>
</section>

<form method="POST" action="{{ $editing ? route('trainings.update', $training) : route('trainings.store') }}" class="stack">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <section class="card" aria-labelledby="identity-heading">
        <h2 id="identity-heading">Identitas</h2>
        <div class="form-grid">
            <x-training.input name="name" label="Nama/tema pelatihan" :value="$training->name" full />
            <div class="field">
                <label for="work_team_id">Tim kerja</label>
                @if ($isFinal)
                    <input type="hidden" name="work_team_id" value="{{ $training->work_team_id }}">
                    <select id="work_team_id" disabled>
                        <option>{{ $training->team->label() }} {{ $training->team->year }}</option>
                    </select>
                @else
                    <select id="work_team_id" name="work_team_id" required>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected((int) old('work_team_id', $selectedTeamId) === $team->id)>{{ $team->label() }} {{ $team->year }}</option>
                        @endforeach
                    </select>
                @endif
                @error('work_team_id')<p class="help text-accent">{{ $message }}</p>@enderror
            </div>
            <x-training.input name="batch" label="Batch" type="number" :value="$training->batch" :required="$req('batch')" />
            <x-training.input name="partner_location" label="Mitra/lokasi" :value="$training->partner_location" :required="$req('partner_location')" />
            <x-training.enum-select name="method" label="Metode" :cases="$methods" :value="$training->method" :required="$req('method')" />
        </div>
    </section>

    <section class="card" aria-labelledby="schedule-heading">
        <h2 id="schedule-heading">Jadwal</h2>
        <div class="form-grid">
            <x-training.input name="registration_start" label="Awal pendaftaran" type="date" :value="$training->registration_start" :required="$req('registration_start')" />
            <x-training.input name="registration_end" label="Akhir pendaftaran" type="date" :value="$training->registration_end" :required="$req('registration_end')" />
            <x-training.input name="training_start" label="Awal pelatihan" type="date" :value="$training->training_start" :required="$req('training_start')" />
            <x-training.input name="training_end" label="Akhir pelatihan" type="date" :value="$training->training_end" :required="$req('training_end')" />
        </div>
    </section>

    <section class="card" aria-labelledby="links-heading">
        <h2 id="links-heading">Tautan</h2>
        <div class="form-grid">
            <x-training.input name="registration_url" label="Link pendaftaran/pelatihan" type="url" :value="$training->registration_url" :required="$req('registration_url')" />
            <x-training.input name="syllabus_url" label="Link silabus" type="url" :value="$training->syllabus_url" :required="$req('syllabus_url')" />
            <x-training.input name="participant_list_url" label="Link daftar peserta" type="url" :value="$training->participant_list_url" full
                help="Baru dapat diisi setelah periode pendaftaran berakhir. Isi dengan link Google Sheet yang aksesnya dibatasi." />
        </div>
    </section>

    <section class="card" aria-labelledby="category-heading">
        <h2 id="category-heading">Kategori</h2>
        <div class="form-grid">
            <x-training.enum-select name="level" label="Level" :cases="$levels" :value="$training->level" :required="$req('level')" />
            <x-training.enum-select name="certification" label="Sertifikasi" :cases="$certifications" :value="$training->certification" :required="$req('certification')" />
            <div class="field">
                @php($coc = old('coc_shows_score', $training->coc_shows_score === null ? '' : ($training->coc_shows_score ? '1' : '0')))
                <label for="coc_shows_score">Certificate of Completion menampilkan nilai <span class="muted">*</span></label>
                <select id="coc_shows_score" name="coc_shows_score">
                    <option value="">— Belum dipilih —</option>
                    <option value="1" @selected((string) $coc === '1')>Ya</option>
                    <option value="0" @selected((string) $coc === '0')>Tidak</option>
                </select>
                @error('coc_shows_score')<p class="help text-accent">{{ $message }}</p>@enderror
            </div>
            <x-training.enum-select name="execution" label="Pelaksanaan" :cases="$executions" :value="$training->execution" :required="$req('execution')" />
            <x-training.enum-select name="participant_category" label="Peserta" :cases="$participantCategories" :value="$training->participant_category" :required="$req('participant_category')" />
            <x-training.enum-select name="zonation" label="Zonasi" :cases="$zonations" :value="$training->zonation" :required="$req('zonation')" />
        </div>
    </section>

    <section class="card" aria-labelledby="numbers-heading">
        <h2 id="numbers-heading">JP, target, dan kuota</h2>
        <div class="form-grid">
            <x-training.input name="lesson_hours" label="Jumlah JP" type="number" :value="$training->lesson_hours" :required="$req('lesson_hours')" />
            <x-training.input name="participant_target" label="Target peserta" type="number" :value="$training->participant_target" :required="$req('participant_target')" />
            <x-training.input name="registration_quota" label="Kuota pendaftar" type="number" :value="$training->registration_quota" :required="$req('registration_quota')" />
        </div>
    </section>

    <section class="card" aria-labelledby="path-heading">
        <h2 id="path-heading">Learning path (opsional)</h2>
        <p class="muted">Rangkaian pelatihan berjenjang dalam satu tim kerja: Level 1 Dasar, Level 2 Menengah, Level 3 Mahir. Tiap level ditempati satu pelatihan.</p>
        <div class="form-grid">
            <div class="field">
                <label for="learning_path_id">Learning path yang ada</label>
                <select id="learning_path_id" name="learning_path_id">
                    <option value="">— Tidak termasuk learning path —</option>
                    @foreach ($paths as $group => $options)
                        <optgroup label="{{ $group }}">
                            @foreach ($options as $path)
                                <option value="{{ $path->id }}" @selected((int) old('learning_path_id', $training->learning_path_id) === $path->id)>{{ $path->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('learning_path_id')<p class="help text-accent">{{ $message }}</p>@enderror
            </div>
            <x-training.input name="new_learning_path" label="Atau buat learning path baru" :value="null" help="Isi nama bila pelatihan ini memulai rangkaian baru." />
        </div>
    </section>

    <div class="row">
        <button type="submit" class="btn">{{ $editing ? 'Simpan perubahan' : 'Simpan draft' }}</button>
        <a class="btn btn-outline" href="{{ $editing ? route('trainings.show', $training) : route('trainings.index') }}">Batal</a>
    </div>
</form>
@endsection
