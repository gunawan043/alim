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
        <div class="col-xl-4 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Staf Tercatat</p>
                    <h3 class="stat-value mb-0">{{ $stats['total'] }}</h3>
                    <p class="stat-note mb-0">Termasuk Kepala Asrama</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Kepala / Wakil</p>
                    <h3 class="stat-value mb-0">{{ $stats['leaders'] }}</h3>
                    <p class="stat-note mb-0">Pimpinan operasional asrama</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body py-3">
                    <p class="stat-label mb-0">Bidang Tugas</p>
                    <h3 class="stat-value mb-0">{{ $stats['duties'] }}</h3>
                    <p class="stat-note mb-0">Total penugasan aktif</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-user-settings-line me-1 text-primary"></i>Pembagian Tugas Staf Asrama</h5>
                <p class="text-muted mb-0 small">Atur jabatan asrama, shift, dan bidang tugas staf di {{ $dormitory->name }}.</p>
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
                    Semua staf pada satuan pendidikan ini sudah tercatat, atau belum ada data kepegawaian yang dapat dipilih.
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;" class="text-center">No</th>
                            <th>Nama Staf</th>
                            <th>Jabatan Asrama</th>
                            <th>Shift</th>
                            <th>Bidang Tugas</th>
                            <th>Masa Tugas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $assignment = $assignments->get($row['user']->id);
                                $dutyKeys = $row['duties'] ?? [];
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row['user']->name }}</div>
                                    <div class="small text-muted">{{ $row['user']->email }}</div>
                                    @if($row['is_head'])
                                        <span class="badge bg-primary-subtle text-primary mt-1">Kepala Asrama</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $row['position'] === \App\Support\DormitoryPosition::KEPALA ? 'primary' : ($row['position'] === \App\Support\DormitoryPosition::WAKIL ? 'info' : 'secondary') }}-subtle text-{{ $row['position'] === \App\Support\DormitoryPosition::KEPALA ? 'primary' : ($row['position'] === \App\Support\DormitoryPosition::WAKIL ? 'info' : 'secondary') }}">
                                        {{ $row['position_label'] }}
                                    </span>
                                </td>
                                <td>{{ \App\Support\DormitoryPosition::shiftLabel($row['shift']) }}</td>
                                <td>
                                    @forelse($dutyKeys as $duty)
                                        <span class="badge bg-success-subtle text-success me-1 mb-1">{{ \App\Support\DormitoryPosition::dutyLabel($duty) }}</span>
                                    @empty
                                        <span class="text-muted small">Belum diatur</span>
                                    @endforelse
                                </td>
                                <td class="small text-muted">
                                    @if($row['start_date'])
                                        {{ $row['start_date']->format('d/m/Y') }} —
                                        {{ $row['end_date']?->format('d/m/Y') ?? 'sekarang' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($row['assignment_id'] && ! $row['is_head'])
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-assignment"
                                                    data-bs-toggle="modal" data-bs-target="#editAssignmentModal"
                                                    data-action="{{ route('user.asrama.task-assignments.update', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'assignmentUuid' => $row['assignment_id']]) }}"
                                                    data-name="{{ $row['user']->name }}"
                                                    data-position="{{ $row['position'] }}"
                                                    data-shift="{{ $row['shift'] }}"
                                                    data-duties="{{ json_encode($dutyKeys) }}"
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
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
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

    document.querySelectorAll('.btn-edit-assignment').forEach(function (button) {
        button.addEventListener('click', function () {
            const data = this.dataset;
            editForm.action = data.action;
            editName.value = data.name || '';

            document.getElementById('edit_position').value = data.position || '';
            document.getElementById('edit_shift').value = data.shift || '';
            document.getElementById('edit_start_date').value = data.start || '';
            document.getElementById('edit_end_date').value = data.end || '';
            document.getElementById('edit_notes').value = data.notes || '';

            let duties = [];
            try { duties = JSON.parse(data.duties || '[]'); } catch (e) { duties = []; }

            document.querySelectorAll('.edit-duty-check').forEach(function (checkbox) {
                checkbox.checked = duties.includes(checkbox.value);
            });
        });
    });
})();
</script>
@endsection
