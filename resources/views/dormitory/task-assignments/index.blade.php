@extends('layouts.master')
@section('title', 'Pembagian Tugas Asrama')
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Pembagian Tugas @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa input:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Staf Tercatat</p>
                    <h3 class="stat-value mb-0">{{ $stats['total'] }}</h3>
                    <p class="stat-note mb-0">Termasuk Kepala Asrama</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Kepala / Wakil</p>
                    <h3 class="stat-value mb-0">{{ $stats['leaders'] }}</h3>
                    <p class="stat-note mb-0">Pimpinan operasional asrama</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Tugas Tambahan</p>
                    <h3 class="stat-value mb-0">{{ $stats['tasks'] }}</h3>
                    <p class="stat-note mb-0">Dari master tugas tambahan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Bidang Tugas</p>
                    <h3 class="stat-value mb-0">{{ $stats['duties'] }}</h3>
                    <p class="stat-note mb-0">Rincian pekerjaan harian</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-user-settings-line me-1 text-primary"></i>Pembagian Tugas Staf Asrama</h5>
                <p class="text-muted mb-0 small">
                    Jabatan dan tugas tambahan mengikuti master satuan kerja; shift, rincian bidang, dan masa tugas khas asrama.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.asrama.task-assignments.print', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                   class="btn btn-outline-secondary btn-sm" target="_blank">
                    <i class="ri-printer-line me-1"></i>Cetak
                </a>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAssignmentModal"
                        @disabled($availableUsers->isEmpty())>
                    <i class="ri-add-line me-1"></i>Tambah Staf
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            @if($availableUsers->isEmpty())
                <div class="alert alert-info m-3 mb-0 small">
                    <i class="ri-information-line me-1"></i>
                    Semua staf pada satuan kerja ini sudah tercatat, atau belum ada data kepegawaian yang dapat dipilih.
                </div>
            @endif
            @if($positions->isEmpty())
                <div class="alert alert-warning m-3 mb-0 small">
                    <i class="ri-error-warning-line me-1"></i>
                    Master jabatan untuk role <strong>Asrama</strong> belum tersedia. Hubungi Personalia/System Admin
                    agar master jabatan struktural asrama diisi terlebih dahulu.
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;" class="text-center">No</th>
                            <th>Nama Staf</th>
                            <th>Jabatan</th>
                            <th>Tugas Tambahan</th>
                            <th>Shift</th>
                            <th>Rincian Bidang</th>
                            <th>SK &amp; Masa Tugas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $dutyKeys = $row['duties'] ?? [];
                                $taskList = collect($row['task_types'] ?? []);
                                $editTasks = $taskList->map(fn ($task) => [
                                    'id' => $task->id,
                                    'hours' => $task->pivot->hours_per_week ?? null,
                                ])->values()->all();
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row['user']->name }}</div>
                                    <div class="small text-muted">{{ $row['user']->email }}</div>
                                    @if($row['is_head'])
                                        <span class="badge bg-primary-subtle text-primary mt-1">Kepala Asrama</span>
                                    @elseif($row['is_unassigned'] ?? false)
                                        <span class="badge bg-warning-subtle text-warning mt-1">Belum ditugaskan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['position_name'])
                                        <span class="badge bg-info-subtle text-info">{{ $row['position_name'] }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($taskList as $task)
                                        <span class="badge bg-primary-subtle text-primary me-1 mb-1">
                                            {{ $task->nama }}
                                            @if($task->pivot->hours_per_week)
                                                · {{ $task->pivot->hours_per_week }} JP
                                            @endif
                                        </span>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </td>
                                <td>{{ \App\Support\DormitoryPosition::shiftLabel($row['shift']) }}</td>
                                <td>
                                    @forelse($dutyKeys as $duty)
                                        <span class="badge bg-success-subtle text-success me-1 mb-1">{{ \App\Support\DormitoryPosition::dutyLabel($duty) }}</span>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </td>
                                <td class="small text-muted">
                                    @if($row['nomor_sk'])
                                        <div><i class="ri-file-text-line me-1"></i>{{ $row['nomor_sk'] }}</div>
                                    @endif
                                    @if($row['start_date'])
                                        <div>
                                            {{ $row['start_date']->format('d/m/Y') }} —
                                            {{ $row['end_date']?->format('d/m/Y') ?? 'sekarang' }}
                                        </div>
                                    @else
                                        <div>—</div>
                                    @endif
                                    @if($row['tmt'] || $row['tst'])
                                        <div class="mt-1">
                                            TMT {{ $row['tmt']?->format('d/m/Y') ?? '—' }}
                                            · TST {{ $row['tst']?->format('d/m/Y') ?? '—' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($row['assignment_id'] && ! $row['is_head'])
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-assignment"
                                                    data-bs-toggle="modal" data-bs-target="#editAssignmentModal"
                                                    data-action="{{ route('user.asrama.task-assignments.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'assignmentUuid' => $row['assignment_id']]) }}"
                                                    data-name="{{ $row['user']->name }}"
                                                    data-position-id="{{ $row['position_id'] }}"
                                                    data-shift="{{ $row['shift'] }}"
                                                    data-duties="{{ json_encode(array_values($dutyKeys)) }}"
                                                    data-tasks="{{ json_encode($editTasks) }}"
                                                    data-nomor-sk="{{ $row['nomor_sk'] }}"
                                                    data-tmt="{{ $row['tmt']?->toDateString() }}"
                                                    data-tst="{{ $row['tst']?->toDateString() }}"
                                                    data-start="{{ $row['start_date']?->toDateString() }}"
                                                    data-end="{{ $row['end_date']?->toDateString() }}"
                                                    data-notes="{{ $row['notes'] }}">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <form method="POST" action="{{ route('user.asrama.task-assignments.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'assignmentUuid' => $row['assignment_id']]) }}"
                                                  onsubmit="return confirm('Akhiri penugasan staf ini di asrama?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Akhiri penugasan"><i class="ri-user-unfollow-line"></i></button>
                                            </form>
                                        </div>
                                    @elseif($row['is_head'])
                                        <span class="text-muted small">Diatur di Profil Asrama</span>
                                    @elseif($row['is_unassigned'] ?? false)
                                        <button type="button" class="btn btn-sm btn-outline-success btn-assign-user"
                                                data-bs-toggle="modal" data-bs-target="#addAssignmentModal"
                                                data-user-id="{{ $row['user']->id }}">
                                            <i class="ri-user-add-line me-1"></i>Tugaskan
                                        </button>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="ri-team-line fs-1 d-block mb-2"></i>
                                    Belum ada staf asrama. Klik <strong>Tambah Staf</strong> untuk memulai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.asrama.task-assignments.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-user-add-line me-1"></i>Tambah Staf / Penugasan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('dormitory.task-assignments._form', [
                            'prefix' => 'add',
                            'assignment' => null,
                            'availableUsers' => $availableUsers,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="editAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="editAssignmentForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-edit-line me-1"></i>Edit Pembagian Tugas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Staf</label>
                            <input type="text" class="form-control" id="edit_assignment_name" disabled>
                        </div>
                        @include('dormitory.task-assignments._form', [
                            'prefix' => 'edit',
                            'assignment' => null,
                            'availableUsers' => collect(),
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
(function () {
    'use strict';

    const editForm = document.getElementById('editAssignmentForm');
    const editName = document.getElementById('edit_assignment_name');

    function setValue(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = value ?? '';
    }

    document.querySelectorAll('.btn-assign-user').forEach(function (button) {
        button.addEventListener('click', function () {
            const select = document.getElementById('add_user_id');
            if (select) {
                select.value = this.dataset.userId || '';
            }
        });
    });

    document.querySelectorAll('.btn-edit-assignment').forEach(function (button) {
        button.addEventListener('click', function () {
            const data = this.dataset;
            editForm.action = data.action;
            editName.value = data.name || '';

            setValue('edit_position_id', data.positionId);
            setValue('edit_shift', data.shift);
            setValue('edit_nomor_sk', data.nomorSk);
            setValue('edit_tmt', data.tmt);
            setValue('edit_tst', data.tst);
            setValue('edit_start_date', data.start);
            setValue('edit_end_date', data.end);
            setValue('edit_notes', data.notes);

            let duties = [];
            try { duties = JSON.parse(data.duties || '[]'); } catch (e) { duties = []; }

            document.querySelectorAll('.edit-duty-check').forEach(function (checkbox) {
                checkbox.checked = duties.includes(checkbox.value);
            });

            let tasks = [];
            try { tasks = JSON.parse(data.tasks || '[]'); } catch (e) { tasks = []; }

            const jpByTask = {};
            tasks.forEach(function (task) {
                jpByTask[task.id] = task.hours;
            });

            document.querySelectorAll('.edit-task-check').forEach(function (checkbox) {
                const has = Object.prototype.hasOwnProperty.call(jpByTask, checkbox.value);
                checkbox.checked = has;
                setValue('edit_jp_' + checkbox.value, has ? jpByTask[checkbox.value] : '');
            });
        });
    });
})();
</script>
@endsection
