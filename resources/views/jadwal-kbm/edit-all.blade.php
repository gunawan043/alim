@extends('layouts.master')

@section('title', 'Edit Jadwal Semua Rombel')

@push('css')
    @include('kurikulum._styles')
    <style>
        .matrix-layout { display: flex; gap: 1rem; align-items: flex-start; }
        .matrix-schedule { flex: 1 1 74%; min-width: 0; }
        .matrix-teachers { flex: 0 0 25%; min-width: 275px; }
        .schedule-matrix-table { font-size: .78rem; }
        .schedule-matrix-table th, .schedule-matrix-table td { padding: .32rem .35rem; vertical-align: middle; }
        .subject-code { display: block; font-weight: 600; line-height: 1.15; }
        .teacher-code { display: block; color: #64748b; font-size: .68rem; line-height: 1.2; }
        .matrix-day-start td { border-top: 2px solid #334155 !important; }
        .matrix-day-name { background: #f8fafc; }
        .matrix-cell { padding: .15rem !important; }
        .matrix-cell-button { width: 100%; min-height: 32px; border: 0; background: transparent; padding: 2px; text-align: center; }
        .matrix-cell-button:hover { background: rgba(17, 104, 145, .08); border-radius: .25rem; }
        .matrix-cell-empty { color: #94a3b8; font-weight: 600; }
        .matrix-conflict { background: #fef3c7 !important; }
        .matrix-unavailable { background: #fee2e2 !important; }
        .teacher-subject { display: flex; justify-content: space-between; gap: .5rem; border-top: 1px solid #e5e7eb; margin-top: .35rem; padding-top: .35rem; }
        .teacher-hours { white-space: nowrap; font-weight: 600; }
        .teacher-total { text-align: right; color: #64748b; margin-top: .25rem; }
        @media (max-width: 1199.98px) {
            .matrix-layout { flex-direction: column; }
            .matrix-schedule, .matrix-teachers { width: 100%; min-width: 0; }
        }
    </style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">Jadwal KBM</a> @endslot
    @slot('li_2') Edit Semua Rombel @endslot
    @slot('title') Edit Jadwal Semua Rombel @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="alert alert-info small d-flex flex-wrap gap-3 align-items-center">
    <span><i class="ri-cursor-line me-1"></i>Klik sel untuk menambah atau mengubah jadwal.</span>
    <span><span class="d-inline-block rounded px-2 py-1" style="background:#fef3c7">Kuning</span> = jadwal bentrok / guru mengajar di dua kelas.</span>
    <span><span class="d-inline-block rounded px-2 py-1" style="background:#fee2e2">Merah</span> = guru tidak tersedia pada hari/jam tersebut.</span>
</div>

@if(! $activeAy)
    <div class="alert alert-warning">Tahun ajaran aktif belum ditetapkan. Editor manual membutuhkan tahun ajaran aktif.</div>
@elseif($studyGroups->isEmpty())
    <div class="alert alert-warning">Belum ada rombel aktif.</div>
@else
    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-edit-2-line text-primary me-1"></i>Matriks Jadwal — Semua Rombel</h5>
                <p class="text-muted mb-0 small">Klik sel untuk mengedit. Semua perubahan tetap melewati validasi bentrok dan ketersediaan guru.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.jadwal-kbm.availability', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-warning">
                    <i class="ri-user-settings-line me-1"></i>Ketersediaan Guru
                </a>
                <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(empty($matrixData['masterDays']))
                <div class="alert alert-warning mb-0">
                    Master Jam Pelajaran belum diatur. Atur di
                    <a href="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}" class="alert-link">Jam Pelajaran</a>
                    sebelum mengedit jadwal.
                </div>
            @else
                @include('jadwal-kbm._matrix', [
                    'studyGroups' => $studyGroups,
                    'matrixData' => $matrixData,
                    'editable' => true,
                ])
            @endif
        </div>
    </div>

    <div class="modal fade" id="matrixEntryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="matrixEntryForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="_method" id="matrixEntryMethod" value="PUT" disabled>
                    <input type="hidden" name="matrix" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-edit-line me-1"></i>Edit Jadwal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small">Rombel</label>
                            <input type="text" id="matrixEntryGroupName" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Hari</label>
                                <select name="day_of_week" id="matrixEntryDay" class="form-select form-select-sm" required>
                                    @foreach([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Jam ke-</label>
                                <select name="slot_index" id="matrixEntrySlot" class="form-select form-select-sm" required>
                                    @for($slot = 1; $slot <= \App\Services\JadwalGeneratorService::MAX_PERIODS_PER_DAY; $slot++)
                                        <option value="{{ $slot }}">Jam {{ $slot }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Mata Pelajaran</label>
                                <select name="subject_id" id="matrixEntrySubject" class="form-select form-select-sm" data-searchable data-placeholder="Cari mapel..." required>
                                    <option value="">— Pilih mapel —</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->code ? $subject->code.' — ' : '' }}{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Guru</label>
                                <select name="teacher_id" id="matrixEntryTeacher" class="form-select form-select-sm" data-searchable data-placeholder="Cari guru...">
                                    <option value="">— Kosong —</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Ruang</label>
                                <input type="text" name="room" id="matrixEntryRoom" class="form-control form-control-sm" maxlength="50">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="submit" form="matrixDeleteForm" id="matrixEntryDelete" class="btn btn-sm btn-soft-danger d-none">
                            <i class="ri-delete-bin-line me-1"></i>Hapus
                        </button>
                        <div class="ms-auto d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                        </div>
                    </div>
                </form>
                <form id="matrixDeleteForm" method="POST" action="" class="d-none">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="matrix" value="1">
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('matrixEntryForm');
    const modalEl = document.getElementById('matrixEntryModal');
    if (!form || !modalEl || !window.bootstrap) return;

    const updateUrl = '{{ route('user.jadwal-kbm.entries.update', ['userId' => $userId, 'entryId' => '__ENTRY__']) }}';
    const storeUrl = '{{ route('user.jadwal-kbm.entries.store', ['userId' => $userId, 'studyGroupId' => '__GROUP__']) }}';
    const deleteUrl = '{{ route('user.jadwal-kbm.entries.destroy', ['userId' => $userId, 'studyGroupId' => '__GROUP__', 'entryId' => '__ENTRY__']) }}';
    const teacherBySubject = {{ Illuminate\Support\Js::from($teacherBySubject) }};
    let currentGroupId = '';
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const methodInput = document.getElementById('matrixEntryMethod');
    const deleteButton = document.getElementById('matrixEntryDelete');
    const deleteForm = document.getElementById('matrixDeleteForm');
    const subjectSelect = document.getElementById('matrixEntrySubject');
    const teacherSelect = document.getElementById('matrixEntryTeacher');

    function cleanupModalArtifacts() {
        if (document.querySelector('.modal.show')) return;

        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    modalEl.addEventListener('hidden.bs.modal', cleanupModalArtifacts);
    modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) {
        button.addEventListener('click', function () {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        });
    });

    subjectSelect.addEventListener('change', function () {
        const assigned = (teacherBySubject[currentGroupId] || {})[this.value];

        if (!assigned) return;

        teacherSelect.value = assigned;

        if (window.jQuery && window.jQuery(teacherSelect).data('select2')) {
            window.jQuery(teacherSelect).val(assigned).trigger('change');
        }
    });

    document.querySelectorAll('.matrix-cell-button').forEach(function (button) {
        button.addEventListener('click', function () {
            const entryId = button.dataset.entryId || '';
            const groupId = button.dataset.groupId || '';
            currentGroupId = groupId;

            form.action = entryId
                ? updateUrl.replace('__ENTRY__', entryId)
                : storeUrl.replace('__GROUP__', groupId);

            if (entryId) {
                methodInput.disabled = false;
                methodInput.value = 'PUT';
                deleteButton.classList.remove('d-none');
                deleteForm.action = deleteUrl.replace('__GROUP__', groupId).replace('__ENTRY__', entryId);
            } else {
                methodInput.disabled = true;
                methodInput.value = '';
                deleteButton.classList.add('d-none');
                deleteForm.action = '';
            }

            document.getElementById('matrixEntryGroupName').value = button.dataset.groupName || '';
            document.getElementById('matrixEntryDay').value = button.dataset.day || '1';
            document.getElementById('matrixEntrySlot').value = button.dataset.slot || '1';
            document.getElementById('matrixEntrySubject').value = button.dataset.subjectId || '';
            document.getElementById('matrixEntryTeacher').value = button.dataset.teacherId || '';
            document.getElementById('matrixEntryRoom').value = button.dataset.room || '';

            window.initSearchableSelects?.(modalEl);
            modal.show();
        });
    });
})();
</script>
@endpush
