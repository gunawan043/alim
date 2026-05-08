@extends('layouts.master')
@section('title') Edit Penugasan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">Penugasan Mengajar</a> @endslot
        @slot('title') Edit Penugasan @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.teaching-assignments.update', ['userId' => $userId, 'id' => $assignment->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Edit Penugasan Mengajar</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if(!$schoolId)
                            <div class="col-md-6">
                                <label class="form-label">Sekolah</label>
                                <select name="school_id" class="form-control" required>
                                    @foreach($schools as $s)
                                        <option value="{{ $s->id }}" {{ old('school_id', $assignment->school_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-control" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id', $assignment->academic_year_id) == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Guru / GTK <span class="text-danger">*</span></label>
                                <select name="teacher_id" class="form-control" required>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}" {{ old('teacher_id', $assignment->teacher_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}
                                            ({{ $t->getRoleNames()->first() ?? 'GTK' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-control" required>
                                    @foreach($subjects as $sub)
                                        <option value="{{ $sub->id }}" {{ old('subject_id', $assignment->subject_id) == $sub->id ? 'selected' : '' }}>{{ $sub->name }} ({{ $sub->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kelas (Rombel) <span class="text-danger">*</span></label>
                                <select name="study_group_id" class="form-control" required>
                                    @foreach($studyGroups as $sg)
                                        <option value="{{ $sg->id }}" {{ old('study_group_id', $assignment->study_group_id) == $sg->id ? 'selected' : '' }}>{{ $sg->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Peran <span class="text-danger">*</span></label>
                                <select name="role" class="form-control" required>
                                    <option value="guru_mapel" {{ old('role', $assignment->role) == 'guru_mapel' ? 'selected' : '' }}>Guru Mata Pelajaran</option>
                                    <option value="guru_pendamping" {{ old('role', $assignment->role) == 'guru_pendamping' ? 'selected' : '' }}>Guru Pendamping</option>
                                    <option value="guru_praktik" {{ old('role', $assignment->role) == 'guru_praktik' ? 'selected' : '' }}>Guru Praktik</option>
                                    <option value="ustadz_pengasuh" {{ old('role', $assignment->role) == 'ustadz_pengasuh' ? 'selected' : '' }}>Ustadz Pengasuh</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jam Pelajaran / Minggu <span class="text-danger">*</span></label>
                                <input type="number" name="weekly_hours" class="form-control" value="{{ old('weekly_hours', $assignment->weekly_hours) }}" min="0" max="40" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" {{ old('status', $assignment->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ old('status', $assignment->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SK Referensi</label>
                                <select name="decree_id" class="form-control">
                                    <option value="">— Tidak ada SK —</option>
                                    @foreach($decrees as $d)
                                        <option value="{{ $d->id }}" {{ old('decree_id', $assignment->decree_id) == $d->id ? 'selected' : '' }}>{{ $d->decree_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_coordinator" value="1" {{ old('is_coordinator', $assignment->is_coordinator) ? 'checked' : '' }}>
                                    <label class="form-check-label">Koordinator mata pelajaran ini</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection