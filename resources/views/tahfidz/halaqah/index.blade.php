@extends('layouts.master')
@section('title', 'Halaqah Tahfizh')

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $formSchools = $form['schools'] ?? collect();
        $formYears = $form['academicYears'] ?? collect();
        $formStudyGroups = $form['studyGroups'] ?? collect();
        $formPackages = $form['packages'] ?? collect();
        $formTeachers = $form['teachers'] ?? collect();
    @endphp
    @include('tahfidz._styles')

    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Halaqah Tahfizh @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Total Halaqah', $stats['total'], 'ri-team-line', 'primary', 'Semua halaqah pada filter ini'],
            ['Halaqah Aktif', $stats['active'], 'ri-checkbox-circle-line', 'success', 'Siap menerima setoran'],
            ['Anggota', $stats['members'], 'ri-user-heart-line', 'info', 'Santri dalam halaqah'],
            ['Guru Halaqah', $stats['teachers'], 'ri-user-star-line', 'warning', 'Guru unik yang mengampu'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                            <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h3></div>
                        </div>
                        <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>{{ $note }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-team-line text-primary me-1"></i>Halaqah Tahfizh</h5><p class="text-muted mb-0 small">Satu kelas dapat memiliki beberapa halaqah. Koordinator sekolah mengatur pembagiannya.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId]) }}" class="btn btn-soft-info"><i class="ri-file-add-line me-1"></i>Setoran</a>
                @if($canManage)
                    <button type="button" class="btn btn-success" onclick="openHalaqahModal()"><i class="ri-add-line me-1"></i>Tambah Halaqah</button>
                @endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" id="halaqah_academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                @if($schools->isNotEmpty())<div class="col-md-4"><label class="form-label small">Sekolah</label><select name="school_id" id="halaqah_school_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($schools as $school)<option value="{{ $school->id }}" @selected(request('school_id')===$school->id)>{{ $school->name }}</option>@endforeach</select></div>@endif
                <div class="col-md-4"><label class="form-label small">Kelas</label><select name="study_group_id" id="halaqah_study_group_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($formStudyGroups as $sg)<option value="{{ $sg->id }}" @selected(request('study_group_id')===$sg->id)>{{ $sg->name }}</option>@endforeach</select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Halaqah</th><th>Sekolah</th><th>Kelas</th><th>Guru / Koordinator</th><th class="text-center">Anggota</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td><div class="fw-medium">{{ $group->name }}</div><div class="text-muted small">{{ strtoupper($group->gender) }} · {{ $group->code ?? '—' }}</div></td>
                            <td>{{ $group->school?->name ?? '—' }}</td>
                            <td>{{ $group->studyGroup?->name ?? '—' }}</td>
                            <td>{{ $group->teacher?->name ?? '—' }}<div class="text-muted small">{{ $group->coordinator?->name ? 'Koor: '.$group->coordinator->name : '' }}</div></td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $group->members_count }}</span></td>
                            <td>{!! $group->is_active ? '<span class="badge bg-success-subtle text-success">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>' !!}</td>
                            <td class="text-end">
                                <a href="{{ route('user.tahfidz.halaqah.show', ['userId' => $userId, 'id' => $group->id]) }}" class="btn btn-sm btn-outline-primary" title="Detail"><i class="ri-eye-line"></i></a>
                                @if($canManage)
                                    @php
                                        $editPayload = [
                                            'id' => $group->id,
                                            'school_id' => $group->school_id,
                                            'academic_year_id' => $group->academic_year_id,
                                            'study_group_id' => $group->study_group_id,
                                            'tahfidz_package_id' => $group->tahfidz_package_id,
                                            'curriculum_id' => $group->curriculum_id,
                                            'name' => $group->name,
                                            'code' => $group->code,
                                            'gender' => $group->gender,
                                            'teacher_id' => $group->teacher_id,
                                            'coordinator_id' => $group->coordinator_id,
                                            'max_members' => $group->max_members,
                                            'room' => $group->room,
                                            'is_active' => (bool) $group->is_active,
                                            'notes' => $group->notes,
                                        ];
                                    @endphp
                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Edit" data-group="{{ json_encode($editPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openHalaqahEditModal(JSON.parse(this.dataset.group))"><i class="ri-edit-line"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-halaqah" title="Hapus"
                                            data-action="{{ route('user.tahfidz.halaqah.destroy', ['userId' => $userId, 'id' => $group->id]) }}"
                                            data-name="{{ $group->name }}"><i class="ri-delete-bin-line"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-team-line fs-3 d-block mb-2"></i>Belum ada halaqah. Tambahkan halaqah pertama.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <div class="modal fade" id="halaqahModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.halaqah.store', ['userId' => $userId]) }}" id="halaqahForm">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" id="halaqahMethod" disabled>
                        <div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="halaqahModalTitle"><i class="ri-team-line me-1"></i>Tambah Halaqah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Sekolah <span class="text-danger">*</span></label><select name="school_id" class="form-select" required>@foreach($formSchools as $school)<option value="{{ $school->id }}" @selected((string)old('school_id', $form['schoolId'])===(string)$school->id)>{{ $school->name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label><select name="academic_year_id" class="form-select" required>@foreach($formYears as $ay)<option value="{{ $ay->id }}" @selected((string)old('academic_year_id', $academicYearId)===(string)$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Kelas / Rombel</label><select name="study_group_id" class="form-select"><option value="">—</option>@foreach($formStudyGroups as $sg)<option value="{{ $sg->id }}" @selected((string)old('study_group_id')===(string)$sg->id)>{{ $sg->name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Nama Halaqah <span class="text-danger">*</span></label><input name="name" id="halaqah_name" class="form-control" value="{{ old('name') }}" required maxlength="100" placeholder="Mis. Halaqah 7A Putra"></div>
                            <div class="col-md-4"><label class="form-label">Paket</label><select name="tahfidz_package_id" id="halaqah_tahfidz_package_id" class="form-select"><option value="">—</option>@foreach($formPackages as $package)<option value="{{ $package->id }}" @selected((string)old('tahfidz_package_id')===(string)$package->id)>{{ $package->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Kurikulum</label><select name="curriculum_id" id="halaqah_curriculum_id" class="form-select"><option value="">—</option>@foreach(($form['curricula'] ?? collect()) as $curriculum)<option value="{{ $curriculum->id }}" @selected((string)old('curriculum_id')===(string)$curriculum->id)>#{{ substr($curriculum->id,0,8) }} ({{ $curriculum->semester }})</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Gender <span class="text-danger">*</span></label><select name="gender" id="halaqah_gender" class="form-select" required><option value="putra" @selected(old('gender')==='putra')>Putra</option><option value="putri" @selected(old('gender')==='putri')>Putri</option></select></div>
                            <div class="col-md-4"><label class="form-label">Kode</label><input name="code" id="halaqah_code" class="form-control" value="{{ old('code') }}" maxlength="20"></div>
                            <div class="col-md-4"><label class="form-label">Guru Halaqah <span class="text-danger">*</span></label><select name="teacher_id" id="halaqah_teacher_id" class="form-select" required><option value="">—</option>@foreach($formTeachers as $teacher)<option value="{{ $teacher->id }}" @selected((string)old('teacher_id')===(string)$teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Koordinator</label><select name="coordinator_id" id="halaqah_coordinator_id" class="form-select"><option value="">—</option>@foreach($formTeachers as $teacher)<option value="{{ $teacher->id }}" @selected((string)old('coordinator_id')===(string)$teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Maks. Anggota</label><input type="number" name="max_members" id="halaqah_max_members" min="1" max="100" class="form-control" value="{{ old('max_members') }}"></div>
                            <div class="col-md-4"><label class="form-label">Ruang</label><input name="room" id="halaqah_room" class="form-control" value="{{ old('room') }}" maxlength="100"></div>
                            <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="halaqah_is_active" @checked(old('is_active', true))><label class="form-check-label" for="halaqahActive">Aktif</label></div></div>
                            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="halaqah_notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="halaqahSubmit"><i class="ri-save-line me-1"></i>Simpan Halaqah</button></div>
                    </form>
                </div>
            </div>
        </div>

        <form id="deleteHalaqahForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="deleteHalaqahModal" icon="https://cdn.lordicon.com/gsqxdxog.json" primary-color="#f7b84b" secondary-color="#f06548"
            title="Hapus Halaqah?" message="Halaqah <strong id='deleteHalaqahName'>ini</strong> akan dihapus. Halaqah yang memiliki setoran tidak dapat dihapus."
            submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteHalaqahForm" />
    @endif
@endsection

@push('scripts')
<script>
    const halaqahUpdateUrl = @json(route('user.tahfidz.halaqah.update', ['userId' => $userId, 'id' => '__ID__']));
    const halaqahStoreUrl = @json(route('user.tahfidz.halaqah.store', ['userId' => $userId]));
    function setHalaqahField(name, value) {
        const field = document.getElementById('halaqah_' + name);
        if (! field) return;
        field.value = value === null || value === undefined ? '' : value;
    }
    function openHalaqahModal() {
        if (! window.bootstrap) return;
        const form = document.getElementById('halaqahForm');
        form.reset();
        form.action = halaqahStoreUrl;
        document.getElementById('halaqahMethod').disabled = true;
        document.getElementById('halaqahModalTitle').innerHTML = '<i class="ri-team-line me-1"></i>Tambah Halaqah';
        document.getElementById('halaqahSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Halaqah';
        document.getElementById('halaqah_is_active').checked = true;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('halaqahModal')).show();
    }
    function openHalaqahEditModal(data) {
        if (! window.bootstrap || ! data) return;
        const form = document.getElementById('halaqahForm');
        form.action = halaqahUpdateUrl.replace('__ID__', data.id);
        document.getElementById('halaqahMethod').disabled = false;
        document.getElementById('halaqahModalTitle').innerHTML = '<i class="ri-edit-line me-1"></i>Edit Halaqah';
        document.getElementById('halaqahSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Perubahan';
        ['school_id','academic_year_id','study_group_id','tahfidz_package_id','curriculum_id','name','code','gender','teacher_id','coordinator_id','max_members','room','notes'].forEach(function (key) { setHalaqahField(key, data[key]); });
        document.getElementById('halaqah_is_active').checked = !! data.is_active;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('halaqahModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any() && $canManage)
            openHalaqahModal();
        @endif
        document.querySelectorAll('.delete-halaqah').forEach(function (button) {
            button.addEventListener('click', function () {
                const form = document.getElementById('deleteHalaqahForm');
                form.action = this.dataset.action;
                document.getElementById('deleteHalaqahName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteHalaqahModal')).show();
            });
        });
    });
</script>
@endpush
