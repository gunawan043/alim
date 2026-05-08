@extends('layouts.master')
@section('title') Tambah Rombel @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombongan Belajar</a> @endslot
        @slot('title') Tambah Rombel @endslot
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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.study-groups.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Form Rombongan Belajar</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                                @if($schoolContext)
                                    <input type="text" class="form-control" value="{{ $schoolContext->name }}" readonly>
                                    <input type="hidden" name="school_id" value="{{ $schoolContext->id }}">
                                @else
                                    <select name="school_id" id="school_id" class="form-control" required>
                                        <option value="">— Pilih Sekolah —</option>
                                        @foreach($schools as $s)
                                            <option value="{{ $s->id }}" {{ old('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-control" required>
                                    <option value="">— Pilih Tahun Ajaran —</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id') == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester_text }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tingkat Kelas <span class="text-danger">*</span></label>
                                <select name="grade_level_id" id="grade_level_id" class="form-control" required>
                                    <option value="">— Pilih Tingkat —</option>
                                    @foreach($gradeLevels as $gl)
                                        <option value="{{ $gl->id }}" {{ old('grade_level_id') == $gl->id ? 'selected' : '' }}>
                                            {{ $gl->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Rombel <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Contoh: A, B, C">
                                <small class="text-muted">Huruf rombel (A, B, C, dsb)</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Rombel</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="VII-A" maxlength="20">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kapasitas</label>
                                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 32) }}" min="1" max="200">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ruang Kelas</label>
                                <input type="text" name="room" class="form-control" value="{{ old('room') }}" placeholder="Ruang 1" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Wali Kelas</label>
                                <select name="homeroom_teacher_id" id="homeroom_teacher_id" class="form-control">
                                    <option value="">— Pilih Wali Kelas —</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}" {{ old('homeroom_teacher_id') == $t->id ? 'selected' : '' }}>
                                            {{ $t->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kurikulum</label>
                                <select name="curriculum_type" class="form-control">
                                    <option value="merdeka" {{ old('curriculum_type', 'merdeka') === 'merdeka' ? 'selected' : '' }}>Merdeka</option>
                                    <option value="2013" {{ old('curriculum_type') === '2013' ? 'selected' : '' }}>2013</option>
                                    <option value="ktsp" {{ old('curriculum_type') === 'ktsp' ? 'selected' : '' }}>KTSP</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Shift</label>
                                <select name="shift" class="form-control">
                                    <option value="pagi" {{ old('shift', 'pagi') === 'pagi' ? 'selected' : '' }}>Pagi</option>
                                    <option value="siang" {{ old('shift') === 'siang' ? 'selected' : '' }}>Siang</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Rombel aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const schoolSelect = document.getElementById('school_id');
    const gradeSelect = document.getElementById('grade_level_id');
    const teacherSelect = document.getElementById('homeroom_teacher_id');

    async function loadGradeLevels(schoolId) {
        if (!schoolId) return;
        gradeSelect.disabled = true;
        try {
            const res = await fetch(`/api/grade-levels/by-school/${schoolId}`);
            const json = await res.json();
            let html = '<option value="">— Pilih Tingkat —</option>';
            json.data.forEach(gl => {
                html += `<option value="${gl.id}">${gl.name}</option>`;
            });
            gradeSelect.innerHTML = html;
        } catch(e) {
            gradeSelect.innerHTML = '<option value="">Gagal memuat tingkat</option>';
        } finally {
            gradeSelect.disabled = false;
        }
    }

    async function loadTeachers(schoolId) {
        if (!schoolId) return;
        teacherSelect.disabled = true;
        try {
            const res = await fetch(`/api/teachers/by-school/${schoolId}`);
            const json = await res.json();
            let html = '<option value="">— Pilih Wali Kelas —</option>';
            json.data.forEach(t => {
                html += `<option value="${t.id}">${t.name}</option>`;
            });
            teacherSelect.innerHTML = html;
        } catch(e) {
            teacherSelect.innerHTML = '<option value="">Gagal memuat wali kelas</option>';
        } finally {
            teacherSelect.disabled = false;
        }
    }

    schoolSelect?.addEventListener('change', async function() {
        const schoolId = this.value;
        await Promise.all([loadGradeLevels(schoolId), loadTeachers(schoolId)]);
    });
});
</script>
@endsection
