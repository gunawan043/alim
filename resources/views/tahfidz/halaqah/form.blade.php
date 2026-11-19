@extends('layouts.master')
@section('title', $group ? 'Edit Halaqah' : 'Buat Halaqah')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $isEdit = (bool) $group;
        $action = $isEdit ? route('user.tahfidz.halaqah.update', ['userId' => $userId, 'id' => $group->id]) : route('user.tahfidz.halaqah.store', ['userId' => $userId]);
        $val = fn ($field, $default = null) => old($field, $group?->{$field} ?? $default);
    @endphp
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah</a> @endslot @slot('li_2') {{ $isEdit ? 'Edit' : 'Buat' }} @endslot @slot('title') Halaqah Tahfizh @endslot @endcomponent

    <form method="POST" action="{{ $action }}">
        @csrf @if($isEdit) @method('PUT') @endif
        <div class="card"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Identitas Halaqah</h5></div><div class="card-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label">Sekolah <span class="text-danger">*</span></label><select name="school_id" class="form-select" required>@foreach($schools as $school)<option value="{{ $school->id }}" @selected((string)$val('school_id', $schoolId)===(string)$school->id)>{{ $school->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label><select name="academic_year_id" class="form-select" required>@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected((string)$val('academic_year_id')===(string)$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Tingkat</label><select name="grade_level_id" class="form-select"><option value="">—</option>@foreach($gradeLevels as $grade)<option value="{{ $grade->id }}" @selected((string)$val('grade_level_id')===(string)$grade->id)>{{ $grade->name }}</option>@endforeach</select><div class="form-text">Halaqah boleh lintas rombel, tetapi satu tingkat.</div></div>
            <div class="col-md-2"><label class="form-label">Kelas / Rombel</label><select name="study_group_id" class="form-select"><option value="">—</option>@foreach($studyGroups as $sg)<option value="{{ $sg->id }}" @selected((string)$val('study_group_id')===(string)$sg->id)>{{ $sg->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Paket</label><select name="tahfidz_package_id" class="form-select"><option value="">—</option>@foreach($packages as $package)<option value="{{ $package->id }}" @selected((string)$val('tahfidz_package_id')===(string)$package->id)>{{ $package->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Kurikulum</label><select name="curriculum_id" class="form-select"><option value="">—</option>@foreach($curricula as $curriculum)<option value="{{ $curriculum->id }}" @selected((string)$val('curriculum_id')===(string)$curriculum->id)>#{{ substr($curriculum->id,0,8) }} — {{ $curriculum->semester }} {{ $curriculum->academic_year_id ? '' : '' }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Nama Halaqah <span class="text-danger">*</span></label><input name="name" class="form-control" value="{{ $val('name') }}" required maxlength="100" placeholder="Mis. Halaqah 7A Putra"></div>
            <div class="col-md-3"><label class="form-label">Kode</label><input name="code" class="form-control" value="{{ $val('code') }}" maxlength="20"></div>
            <div class="col-md-3"><label class="form-label">Gender <span class="text-danger">*</span></label><select name="gender" class="form-select" required><option value="putra" @selected($val('gender')==='putra')>Putra</option><option value="putri" @selected($val('gender')==='putri')>Putri</option></select></div>
            <div class="col-md-3"><label class="form-label">Guru Halaqah <span class="text-danger">*</span></label><select name="teacher_id" class="form-select" required><option value="">—</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string)$val('teacher_id')===(string)$teacher->id)>{{ $teacher->name }}</option>@endforeach</select><div class="form-text">@if($teacherFallback ?? false)<span class="text-warning">Belum ada Guru Tahfidz terdaftar di sekolah ini; daftar menampilkan seluruh GTK sekolah.</span>@else Hanya Guru Tahfidz pada satuan pendidikan ini.@endif</div></div>
            <div class="col-md-3"><label class="form-label">Koordinator</label><select name="coordinator_id" class="form-select"><option value="">—</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string)$val('coordinator_id')===(string)$teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Maks. Anggota</label><input type="number" name="max_members" class="form-control" value="{{ $val('max_members') }}" min="1" max="100"></div>
            <div class="col-md-3"><label class="form-label">Ruang</label><input name="room" class="form-control" value="{{ $val('room') }}" maxlength="100"></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="groupActive" @checked($val('is_active', true))><label class="form-check-label" for="groupActive">Aktif</label></div></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2">{{ $val('notes') }}</textarea></div>
        </div></div>
        <div class="border-top">
            <div class="card-body">
                <h6 class="card-title mb-1"><i class="ri-team-line text-primary me-1"></i>Tarik Santri (Opsional)</h6>
                <p class="text-muted small mb-2">Pilih santri dari daftar santri sekolah. Santri yang sudah punya halaqah lain akan dipindahkan ke halaqah ini. Santri dari tingkat berbeda akan ditolak. Untuk mengeluarkan santri, gunakan halaman detail halaqah.</p>
                <select name="member_ids[]" class="form-select" multiple data-searchable data-placeholder="Cari nama atau NIS santri…">
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected(in_array($student->id, (array) old('member_ids', []), true))>{{ $student->name }} ({{ $student->nis }}) — {{ $student->class_name }}</option>
                    @endforeach
                </select>
                @if($students->isEmpty())
                    <div class="form-text text-warning">Belum ada santri aktif pada sekolah ini.</div>
                @else
                    <div class="form-text">Ketik untuk mencari; Ctrl/Cmd untuk memilih beberapa santri.</div>
                @endif
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button></div></div>
    </form>
@endsection
