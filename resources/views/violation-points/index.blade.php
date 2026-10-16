@extends('layouts.master')
@section('title') Poin Pelanggaran @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Peserta Didik</a> @endslot
        @slot('li_2') <a href="{{ route('user.violation-points.dashboard', ['userId' => $userId]) }}">Dashboard Kedisiplinan</a> @endslot
        @slot('title') Poin Pelanggaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card" id="violationsList">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0">Poin Pelanggaran</h5>
                    <p class="text-muted mb-0 small">Daftar pencatatan poin pelanggaran peserta didik.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('user.violation-points.dashboard', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-dashboard-2-line me-1"></i>Dashboard
                    </a>
                    <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="ri-bar-chart-2-line me-1"></i>Rekap per Santri
                    </a>
                    <a href="{{ route('user.violation-points.export-pdf', array_merge(['userId' => $userId], request()->query())) }}"
                       class="btn btn-outline-danger btn-sm">
                        <i class="ri-file-pdf-2-line me-1"></i>Ekspor PDF
                    </a>
                    <a href="{{ route('user.violation-points.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
                        <i class="ri-add-line me-1"></i>Tambah Pelanggaran
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Pencarian</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama / jenis pelanggaran..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Rombel</label>
                    <select name="study_group_id" class="form-select form-select-sm">
                        <option value="">Semua Rombel</option>
                        @foreach($studyGroups as $sg)
                            <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="ri-filter-3-line me-1"></i>Filter</button>
                        <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm" title="Reset"><i class="ri-refresh-line"></i></a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px" class="text-center">No</th>
                            <th style="width:110px">Tanggal</th>
                            <th>Nama Peserta Didik</th>
                            <th>Rombel</th>
                            <th>Jenis Pelanggaran</th>
                            <th class="text-center" style="width:80px">Poin</th>
                            <th>Tindakan</th>
                            <th class="text-center" style="width:140px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($violations as $i => $v)
                            <tr>
                                <td class="text-center text-muted">{{ $violations->firstItem() + $i }}</td>
                                <td class="small">{{ $v->violation_date->format('d/m/Y') }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $v->student?->name ?? '-' }}</span>
                                    @if($v->student?->nisn)
                                        <small class="text-muted d-block">NISN: {{ $v->student->nisn }}</small>
                                    @endif
                                </td>
                                <td class="small">{{ $v->studyGroup?->full_name ?? '-' }}</td>
                                <td>{{ $v->violation_type }}</td>
                                <td class="text-center">
                                    <span class="badge bg-danger-subtle text-danger">{{ $v->points }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ Str::limit($v->action_taken, 40) ?: '—' }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('user.violation-points.show', ['userId' => $userId, 'violationUuid' => $v->id]) }}"
                                       class="btn btn-sm btn-soft-primary me-1" title="Detail">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('user.violation-points.edit', ['userId' => $userId, 'violationUuid' => $v->id]) }}"
                                       class="btn btn-sm btn-soft-warning me-1" title="Edit">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-soft-danger"
                                            title="Hapus"
                                            data-bs-toggle="modal" data-bs-target="#deleteViolationModal"
                                            data-action="{{ route('user.violation-points.destroy', ['userId' => $userId, 'violationUuid' => $v->id]) }}"
                                            data-name="{{ $v->student?->name }}"
                                            data-type="{{ $v->violation_type }}">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="8"
                                title="Belum ada data pelanggaran"
                                message="Catatan pelanggaran yang ditambahkan akan tampil di sini.">
                                <a href="{{ route('user.violation-points.create', ['userId' => $userId]) }}" class="btn btn-success mt-3">
                                    <i class="ri-add-line me-1"></i>Tambah Pelanggaran
                                </a>
                            </x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('shared._pagination', ['paginator' => $violations])
        </div>
    </div>

    {{-- Modal konfirmasi hapus — template lord-icon/zoomIn --}}
    <form id="deleteViolationForm" method="POST" action="" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-confirm id="deleteViolationModal"
        icon="https://cdn.lordicon.com/tdrtiskw.json"
        primary-color="#f7b84b" secondary-color="#005981"
        title="Hapus Catatan Pelanggaran?"
        message="Catatan <strong id=&quot;deleteViolationLabel&quot;>ini</strong> akan dihapus permanen dan poin santri ikut berkurang. Tindakan ini tidak dapat dibatalkan."
        form-id="deleteViolationForm"
        submit-label="Ya, Hapus"
        submit-icon="ri-delete-bin-line"
        submit-class="btn-danger" />

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('deleteViolationModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var form = document.getElementById('deleteViolationForm');
                if (form) form.setAttribute('action', trigger.getAttribute('data-action') || '');

                var label = document.getElementById('deleteViolationLabel');
                if (label) {
                    var name = trigger.getAttribute('data-name') || 'ini';
                    var type = trigger.getAttribute('data-type') || '';
                    label.textContent = (name + (type ? ' — ' + type : '')).trim();
                }
            });
        });
    </script>
    @endpush
@endsection
