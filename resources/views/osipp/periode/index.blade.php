@extends('layouts.master')
@section('title', 'Periode Kepengurusan OSIPP')
@section('css')
    @include('osipp._styles')
@endsection

@section('content')
    <div class="osipp-page">
        @component('components.breadcrumb')
            @slot('li_1') OSIPP @endslot
            @slot('title') Periode Kepengurusan @endslot
        @endcomponent

        @include('osipp._header')

        @php
            $periodsJson = $periods->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'academic_year_id' => $row->academic_year_id,
                'decree_number' => $row->decree_number,
                'decree_date' => $row->decree_date?->format('Y-m-d'),
                'start_date' => $row->start_date?->format('Y-m-d'),
                'end_date' => $row->end_date?->format('Y-m-d'),
                'status' => $row->status,
                'notes' => $row->notes,
                'update_url' => route('osipp.periode.update', ['userId' => $userId, 'period' => $row->id]),
            ])->values();
        @endphp

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-calendar-event-line me-1 text-primary"></i>Daftar Periode</h5>
                    <p class="text-muted small mb-0">Satu periode mewakili satu kepengurusan OSIPP pada tahun ajaran.</p>
                </div>
                @if($canManage)
                    <button class="btn btn-primary btn-sm" type="button" onclick="openPeriodModal(null)">
                        <i class="ri-add-line me-1"></i>Tambah Periode
                    </button>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Tahun Ajaran</th>
                            <th>Masa Tugas</th>
                            <th class="text-center">Pengurus</th>
                            <th class="text-center">Program</th>
                            <th class="text-center">Kegiatan</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periods as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row->name }}</div>
                                    @if($row->decree_number)
                                        <div class="text-muted small">SK: {{ $row->decree_number }}</div>
                                    @endif
                                </td>
                                <td>{{ $row->academicYear?->name ?? '—' }}</td>
                                <td>
                                    {{ $row->start_date?->translatedFormat('d M Y') ?? '—' }}
                                    @if($row->end_date)
                                        <span class="text-muted">s.d.</span> {{ $row->end_date->translatedFormat('d M Y') }}
                                    @endif
                                </td>
                                <td class="text-center">{{ number_format($row->members_count) }}</td>
                                <td class="text-center">{{ number_format($row->programs_count) }}</td>
                                <td class="text-center">{{ number_format($row->activities_count) }}</td>
                                <td class="text-center"><span class="badge bg-{{ $row->status_badge }}">{{ $row->status_label }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('osipp.dashboard', ['userId' => $userId, 'period_id' => $row->id]) }}" class="btn btn-sm btn-light" title="Buka"><i class="ri-external-link-line"></i></a>
                                        @if($canManage && ! $row->isActive())
                                            <form method="POST" action="{{ route('osipp.periode.activate', ['userId' => $userId, 'period' => $row->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success" title="Aktifkan"><i class="ri-play-circle-line"></i></button>
                                            </form>
                                        @endif
                                        @if($canManage)
                                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="openPeriodModal('{{ $row->id }}')" title="Edit"><i class="ri-edit-line"></i></button>
                                            @if(! $row->isActive())
                                                <form method="POST" action="{{ route('osipp.periode.destroy', ['userId' => $userId, 'period' => $row->id]) }}" onsubmit="return confirm('Hapus periode ini? Data yang terkait juga akan disembunyikan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="ri-calendar-event-line fs-3 d-block mb-2"></i>
                                    Belum ada periode kepengurusan OSIPP.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($canManage)
            <div class="modal fade" id="periodModal" tabindex="-1" aria-labelledby="periodModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('osipp.periode.store', ['userId' => $userId]) }}" id="periodForm">
                            @csrf
                            <input type="hidden" name="_method" value="POST" id="periodMethod">
                            <input type="hidden" name="period_id" id="periodId">
                            <div class="modal-header">
                                <h5 class="modal-title" id="periodModalLabel">Tambah Periode OSIPP</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Nama Periode <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="periodName" class="form-control" required placeholder="Kepengurusan OSIPP 2026/2027">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select name="status" id="periodStatus" class="form-select">
                                            @foreach($statusOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tahun Ajaran</label>
                                        <select name="academic_year_id" id="periodAcademicYear" class="form-select">
                                            <option value="">— Ikuti tahun ajaran aktif —</option>
                                            @foreach($academicYears as $year)
                                                <option value="{{ $year->id }}">{{ $year->name }} · {{ $year->semester_text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" id="periodStart" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Selesai</label>
                                        <input type="date" name="end_date" id="periodEnd" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor SK</label>
                                        <input type="text" name="decree_number" id="periodDecreeNumber" class="form-control" placeholder="Opsional">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal SK</label>
                                        <input type="date" name="decree_date" id="periodDecreeDate" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan</label>
                                        <textarea name="notes" id="periodNotes" class="form-control" rows="3" placeholder="Catatan kepengurusan, target, atau hal penting lainnya."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('script')
<script>
    const osippPeriods = @json($periodsJson);
    const osippPeriodStoreUrl = @json(route('osipp.periode.store', ['userId' => $userId]));

    function openPeriodModal(id) {
        const form = document.getElementById('periodForm');
        if (!form) return;
        const data = id ? osippPeriods.find(function (item) { return item.id === id; }) : null;
        const modalEl = document.getElementById('periodModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        form.action = data ? data.update_url : osippPeriodStoreUrl;
        document.getElementById('periodMethod').value = data ? 'PUT' : 'POST';
        document.getElementById('periodId').value = data ? data.id : '';
        document.getElementById('periodModalLabel').textContent = data ? 'Edit Periode OSIPP' : 'Tambah Periode OSIPP';
        document.getElementById('periodName').value = data ? (data.name || '') : '';
        document.getElementById('periodStatus').value = data ? (data.status || 'draft') : 'draft';
        document.getElementById('periodAcademicYear').value = data ? (data.academic_year_id || '') : '';
        document.getElementById('periodStart').value = data ? (data.start_date || '') : '';
        document.getElementById('periodEnd').value = data ? (data.end_date || '') : '';
        document.getElementById('periodDecreeNumber').value = data ? (data.decree_number || '') : '';
        document.getElementById('periodDecreeDate').value = data ? (data.decree_date || '') : '';
        document.getElementById('periodNotes').value = data ? (data.notes || '') : '';
        modal.show();
    }
</script>
@endsection
