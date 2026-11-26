@extends('layouts.master')
@section('title', $group ? 'Edit Halaqah' : 'Buat Halaqah')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $isEdit = (bool) $group;
        $action = $isEdit ? route('user.tahfidz.halaqah.update', ['userId' => $userId, 'id' => $group->id]) : route('user.tahfidz.halaqah.store', ['userId' => $userId]);
        $val = fn ($field, $default = null) => old($field, $group?->{$field} ?? $default);
        $genderValue = old('gender', $schoolGender ?? $group?->gender ?? 'putra');
        $genderLabel = $genderValue === 'putri' ? 'Putri' : 'Putra';
    @endphp
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah</a> @endslot @slot('li_2') {{ $isEdit ? 'Edit' : 'Buat' }} @endslot @slot('title') Halaqah Tahfizh @endslot @endcomponent

    <form method="POST" action="{{ $action }}">
        @csrf @if($isEdit) @method('PUT') @endif
        <div class="card"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Identitas Halaqah</h5></div><div class="card-body"><div class="row g-3">

            {{-- Sekolah: terkunci pada satuan pendidikan pengguna. Hanya Departemen Tahfidz yang boleh memilih. --}}
            <div class="col-md-4">
                <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                @if($canChooseSchool ?? false)
                    <select name="school_id" class="form-select" required>
                        @foreach($schools as $school)<option value="{{ $school->id }}" @selected((string)$val('school_id', $schoolId)===(string)$school->id)>{{ $school->name }}</option>@endforeach
                    </select>
                    <div class="form-text">Departemen Tahfidz dapat memilih satuan pendidikan.</div>
                @else
                    @php $lockedSchool = $schools->firstWhere('id', $schoolId); @endphp
                    <input type="text" class="form-control" value="{{ $lockedSchool->name ?? '—' }}" readonly disabled>
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <div class="form-text">Otomatis mengikuti satuan pendidikan Anda.</div>
                @endif
            </div>

            <div class="col-md-4"><label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label><select name="academic_year_id" class="form-select" required>@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected((string)$val('academic_year_id')===(string)$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>

            {{-- Kelas = tingkat (1, 2, 3), bukan rombel: halaqah boleh lintas rombel. --}}
            <div class="col-md-4">
                <label class="form-label">Kelas</label>
                <select name="grade_level_id" class="form-select">
                    <option value="">—</option>
                    @foreach($gradeLevels as $grade)<option value="{{ $grade->id }}" @selected((string)$val('grade_level_id')===(string)$grade->id)>{{ $grade->name }}</option>@endforeach
                </select>
                <div class="form-text">Halaqah boleh berisi santri dari beberapa rombel, tetapi satu kelas.</div>
            </div>

            {{-- Gender mengikuti satuan pendidikan, tidak dipilih. --}}
            <div class="col-md-4">
                <label class="form-label">Gender</label>
                <input type="text" class="form-control" value="{{ $genderLabel }}" readonly disabled>
                <input type="hidden" name="gender" value="{{ $genderValue }}">
                <div class="form-text">Mengikuti gender satuan pendidikan.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Guru Halaqah <span class="text-danger">*</span></label>
                <select name="teacher_id" class="form-select" required>
                    <option value="">—</option>
                    @foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string)$val('teacher_id')===(string)$teacher->id)>{{ $teacher->name }}</option>@endforeach
                </select>
                <div class="form-text">
                    @if($teachers->isEmpty())
                        <span class="text-warning">Belum ada Guru Tahfidz terdaftar di satuan pendidikan ini.</span>
                    @else
                        Hanya Guru Tahfidz pada satuan pendidikan ini.
                    @endif
                </div>
            </div>

            {{-- Koordinator diisi otomatis dari tugas tambahan. --}}
            <div class="col-md-4">
                <label class="form-label">Koordinator</label>
                <input type="text" class="form-control" value="{{ $autoCoordinator->name ?? 'Belum ada koordinator' }}" readonly disabled>
                @if($autoCoordinator)
                    <input type="hidden" name="coordinator_id" value="{{ $autoCoordinator->id }}">
                @endif
                <div class="form-text">
                    @if($autoCoordinator)
                        Otomatis dari tugas tambahan Koordinator Guru Tahfidz.
                    @else
                        <span class="text-warning">Belum ada guru dengan tugas tambahan Koordinator Guru Tahfidz di satuan pendidikan ini.</span>
                    @endif
                </div>
            </div>

            <div class="col-md-4"><label class="form-label">Nama Halaqah <span class="text-danger">*</span></label><input name="name" class="form-control" value="{{ $val('name') }}" required maxlength="100" placeholder="Mis. Halaqah Umar bin Khattab"></div>
            <div class="col-md-4"><label class="form-label">Kode</label><input name="code" class="form-control" value="{{ $val('code') }}" maxlength="20"></div>
            <div class="col-md-4"><label class="form-label">Maks. Anggota</label><input type="number" name="max_members" class="form-control" value="{{ $val('max_members') }}" min="1" max="100"></div>

            <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="groupActive" @checked($val('is_active', true))><label class="form-check-label" for="groupActive">Aktif</label></div></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2">{{ $val('notes') }}</textarea></div>
        </div></div>
        <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button></div></div>
    </form>
@endsection
