@extends('layouts.master')

@section('title', 'Form Kegiatan Bahasa')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.activities.index', ['userId' => $userId]) }}">AM Nasyath Syahri</a> @endslot
        @slot('title') {{ $activity->exists ? 'Edit Kegiatan' : 'Buat Kegiatan' }} @endslot
    @endcomponent

    <form method="POST" action="{{ $activity->exists ? route('user.bahasa.activities.update', ['userId' => $userId, 'activity' => $activity->id]) : route('user.bahasa.activities.store', ['userId' => $userId]) }}">
        @csrf
        <input type="hidden" name="school_id" value="{{ old('school_id', $schoolId ?? null) }}">
        @if($activity->exists) @method('PUT') @endif

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Rencana Kegiatan</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Bulan <span class="text-danger">*</span></label><input type="month" name="period_month" class="form-control" value="{{ old('period_month', $activity->period_month?->format('Y-m') ?? now()->format('Y-m')) }}" required></div>
                    <div class="col-md-6"><label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label><input name="title" class="form-control" value="{{ old('title', $activity->title) }}" placeholder="Lomba Pidato Bahasa Arab" required></div>
                    <div class="col-md-3"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="activity_type" class="form-select" required>@foreach($types as $value => $label)<option value="{{ $value }}" @selected(old('activity_type', $activity->activity_type) === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Tingkat</label><select name="grade_level_id" class="form-select"><option value="">Semua tingkat</option>@foreach($gradeLevels ?? [] as $grade)<option value="{{ $grade->id }}" @selected((string) old('grade_level_id', $activity->grade_level_id) === (string) $grade->id)>{{ $grade->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Kelas</label><select name="study_group_id" class="form-select"><option value="">Semua kelas</option>@foreach($studyGroups ?? [] as $group)<option value="{{ $group->id }}" @selected((string) old('study_group_id', $activity->study_group_id) === (string) $group->id)>{{ $group->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Tema</label><input name="theme" class="form-control" value="{{ old('theme', $activity->theme) }}"></div>
                    <div class="col-md-3"><label class="form-label">Tanggal</label><input type="date" name="activity_date" class="form-control" value="{{ old('activity_date', $activity->activity_date?->format('Y-m-d')) }}"></div>
                    <div class="col-md-3"><label class="form-label">Tempat</label><input name="place" class="form-control" value="{{ old('place', $activity->place) }}"></div>
                    <div class="col-md-3"><label class="form-label">Mulai</label><input type="time" name="time_start" class="form-control" value="{{ old('time_start', $activity->time_start ? substr($activity->time_start, 0, 5) : '') }}"></div>
                    <div class="col-md-3"><label class="form-label">Selesai</label><input type="time" name="time_end" class="form-control" value="{{ old('time_end', $activity->time_end ? substr($activity->time_end, 0, 5) : '') }}"></div>
                    <div class="col-md-3"><label class="form-label">Pelaksana <span class="text-danger">*</span></label><select name="executor_type" class="form-select">@foreach(['santri' => 'Santri', 'guru' => 'Guru Bahasa', 'departemen' => 'Departemen Bahasa', 'asrama' => 'Pengurus Asrama'] as $value => $label)<option value="{{ $value }}" @selected(old('executor_type', $activity->executor_type) === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Nama Pelaksana</label><input name="executor_name" class="form-control" value="{{ old('executor_name', $activity->executor_name) }}"></div>
                    <div class="col-md-4"><label class="form-label">MC</label><input name="mc_name" class="form-control" value="{{ old('mc_name', $activity->mc_name) }}"></div>
                    <div class="col-md-4"><label class="form-label">Jumlah Peserta</label><input type="number" min="0" name="participants_count" class="form-control" value="{{ old('participants_count', $activity->participants_count ?? 0) }}"></div>
                    <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['planned' => 'Direncanakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $activity->status) === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Panitia</label><textarea name="committee" rows="2" class="form-control">{{ old('committee', $activity->committee) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Catatan Partisipasi</label><textarea name="participation_notes" rows="3" class="form-control">{{ old('participation_notes', $activity->participation_notes) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label">Laporan Kegiatan</label><textarea name="report" rows="3" class="form-control">{{ old('report', $activity->report) }}</textarea></div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('user.bahasa.activities.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                <button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button>
            </div>
        </div>
    </form>
@endsection
