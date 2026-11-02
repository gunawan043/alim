@extends('layouts.master')
@section('title') Laporan Penugasan Sarpras @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Laporan Penugasan @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1"><i class="ri-file-list-3-line me-2 text-primary"></i>Laporan Penugasan Sarpras</h5>
                <p class="text-muted small mb-0">
                    Setiap pekerjaan yang selesai dicatat singkat: apa yang dikerjakan dan kendalanya.
                    Daftar ini sekaligus menjadi penanda agenda mana yang sudah tuntas.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('sarpras.agenda.index') }}" class="btn btn-sm btn-light border"><i class="ri-calendar-task-line me-1"></i>Agenda Kerja</a>
                <a href="{{ route('sarpras.workflow.index') }}" class="btn btn-sm btn-outline-primary"><i class="ri-flow-chart me-1"></i>Alur Kerja</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Belum dilaporkan', $stats['pending'], 'ri-error-warning-line', 'warning', 'Pekerjaan selesai tanpa laporan'],
        ['Laporan bulan ini', $stats['reports_month'], 'ri-file-text-line', 'info', 'Sudah diisi petugas'],
        ['Terverifikasi', $stats['verified_month'], 'ri-shield-check-line', 'success', 'Diperiksa koordinator'],
        ['Biaya bulan ini', 'Rp '.number_format($stats['cost_month'], 0, ',', '.'), 'ri-money-cny-circle-line', 'primary', 'Akumulasi laporan'],
    ] as [$label, $value, $icon, $tone, $hint])
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 text-uppercase fw-medium fs-12">{{ $label }}</p>
                            <h4 class="mb-0">{{ $value }}</h4>
                            <small class="text-muted">{{ $hint }}</small>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title rounded bg-{{ $tone }}-subtle text-{{ $tone }} fs-20">
                                <i class="{{ $icon }}"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <ul class="nav nav-tabs card-header-tabs border-0">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'belum' ? 'active' : '' }}" href="{{ route('sarpras.assignment-report.index', ['tab' => 'belum', 'q' => $search]) }}">
                    Belum dilaporkan
                    @if($stats['pending'] > 0)<span class="badge bg-warning ms-1">{{ $stats['pending'] }}</span>@endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'laporan' ? 'active' : '' }}" href="{{ route('sarpras.assignment-report.index', ['tab' => 'laporan', 'q' => $search]) }}">
                    Laporan
                </a>
            </li>
        </ul>
        <form method="GET" action="{{ route('sarpras.assignment-report.index') }}" class="d-flex gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nomor / aset / isi laporan">
            <button class="btn btn-sm btn-light border"><i class="ri-search-line"></i></button>
        </form>
    </div>

    <div class="card-body">
        @if($tab === 'belum')
            <p class="text-muted small">
                Daftar pekerjaan berstatus selesai yang belum punya laporan. Klik <strong>Isi Laporan</strong> — formulir sudah terisi otomatis, tinggal menyesuaikan.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nomor</th>
                            <th>Pekerjaan</th>
                            <th>Lokasi</th>
                            <th>Petugas</th>
                            <th>Selesai</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pending as $order)
                            @php
                                $duration = $order->actual_start && $order->actual_end
                                    ? (int) abs($order->actual_start->diffInMinutes($order->actual_end))
                                    : null;
                            @endphp
                            <tr>
                                <td><span class="fw-medium">{{ $order->order_number }}</span></td>
                                <td>
                                    <div class="fw-medium">{{ $order->scope_of_work ?: $order->target_name }}</div>
                                    <small class="text-muted">
                                        {{ $order->repair_request_id ? 'Perbaikan' : ($order->maintenance_schedule_id ? 'Pemeliharaan' : 'Work Order') }}
                                    </small>
                                </td>
                                <td class="small text-muted">{{ $order->target_location }}</td>
                                <td class="small">{{ $order->assignee?->name ?? '-' }}</td>
                                <td class="small text-muted">{{ $order->actual_end?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary js-report"
                                        data-order-id="{{ $order->id }}"
                                        data-number="{{ $order->order_number }}"
                                        data-scope="{{ $order->scope_of_work }}"
                                        data-notes="{{ $order->completion_notes }}"
                                        data-parts=""
                                        data-cost="{{ (float) $order->total_cost }}"
                                        data-condition="{{ $order->asset?->condition }}"
                                        data-duration="{{ $duration }}">
                                        <i class="ri-edit-line me-1"></i>Isi Laporan
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">
                                <i class="ri-checkbox-circle-line me-1 text-success"></i>Tidak ada pekerjaan yang menunggu laporan.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nomor</th>
                            <th>Pekerjaan</th>
                            <th>Kendala</th>
                            <th>Petugas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td class="small">{{ $report->report_date?->format('d/m/Y') }}</td>
                                <td class="small fw-medium">{{ $report->workOrder?->order_number ?? '-' }}</td>
                                <td>
                                    <div class="text-truncate" style="max-width:320px">{{ $report->work_done }}</div>
                                    <small class="text-muted">{{ $report->asset?->asset_name ?? $report->workOrder?->target_name }}</small>
                                </td>
                                <td class="small text-muted text-truncate" style="max-width:200px">{{ $report->obstacles ?: '—' }}</td>
                                <td class="small">{{ $report->reporter?->name ?? '-' }}</td>
                                <td><span class="badge bg-{{ $report->status_tone }}-subtle text-{{ $report->status_tone }}">{{ $report->status_label }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('sarpras.assignment-report.show', ['reportId' => $report->id]) }}" class="btn btn-sm btn-light border" title="Detail"><i class="ri-eye-line"></i></a>
                                    @if($canVerify && $report->status !== 'verified')
                                        <form method="POST" action="{{ route('sarpras.assignment-report.verify', ['reportId' => $report->id]) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" title="Verifikasi"><i class="ri-shield-check-line"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada laporan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Modal laporan singkat --}}
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('sarpras.assignment-report.store') }}" class="modal-content" id="reportForm">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Laporan Pekerjaan</h5>
                    <small class="text-muted" id="reportModalSubtitle">—</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($pending->isEmpty())
                    <input type="hidden" name="work_order_id" value="{{ old('work_order_id', $selectedOrderId) }}">
                @endif

                @if($pending->isEmpty())
                    <div class="alert alert-warning small">
                        Tidak ada pekerjaan selesai yang menunggu laporan.
                    </div>
                @else
                    <div class="mb-3">
                        <label class="form-label">Pekerjaan <span class="text-danger">*</span></label>
                        <select name="work_order_id" id="reportOrderSelect" class="form-select" required>
                            <option value="">— pilih pekerjaan —</option>
                            @foreach($pending as $order)
                                <option value="{{ $order->id }}"
                                    data-scope="{{ $order->scope_of_work }}"
                                    data-notes="{{ $order->completion_notes }}"
                                    data-condition="{{ $order->asset?->condition }}"
                                    data-cost="{{ (float) $order->total_cost }}"
                                    data-number="{{ $order->order_number }}"
                                    {{ old('work_order_id', $selectedOrderId) === $order->id ? 'selected' : '' }}>
                                    {{ $order->order_number }} — {{ $order->scope_of_work ?: $order->target_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="report_date" class="form-control" value="{{ old('report_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durasi (menit)</label>
                            <input type="number" name="duration_minutes" id="reportDuration" class="form-control" min="0" value="{{ old('duration_minutes') }}" placeholder="otomatis">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kondisi setelah</label>
                            <select name="condition_after" id="reportCondition" class="form-select">
                                <option value="">— tidak berubah —</option>
                                @foreach($conditionOptions as $condition)
                                    <option value="{{ $condition }}" {{ old('condition_after') === $condition ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $condition)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Apa yang dikerjakan <span class="text-danger">*</span></label>
                            <textarea name="work_done" id="reportWorkDone" class="form-control" rows="3" required
                                placeholder="Contoh: mengganti kapasitor AC, membersihkan filter, uji nyala 15 menit.">{{ old('work_done') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between">
                                <span>Kendala</span>
                                <small class="text-muted">klik untuk menambahkan</small>
                            </label>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @foreach($obstaclePresets as $preset)
                                    <button type="button" class="btn btn-sm btn-light border js-obstacle" data-text="{{ $preset }}">{{ $preset }}</button>
                                @endforeach
                            </div>
                            <textarea name="obstacles" id="reportObstacles" class="form-control" rows="2"
                                placeholder="Kosongkan bila tidak ada kendala.">{{ old('obstacles') }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tindak lanjut</label>
                            <input type="text" name="follow_up" class="form-control" value="{{ old('follow_up') }}"
                                placeholder="Contoh: pantau 1 minggu, ajukan penggantian unit.">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Suku cadang</label>
                            <input type="text" name="parts_replaced" id="reportParts" class="form-control" value="{{ old('parts_replaced') }}" placeholder="kapasitor, filter">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Biaya (Rp)</label>
                            <input type="number" name="cost" id="reportCost" class="form-control" min="0" step="1000" value="{{ old('cost') }}">
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                @if(! $pending->isEmpty())
                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Simpan Draf</button>
                    <button type="submit" name="action" value="submit" class="btn btn-primary">Simpan Laporan</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const modalEl = document.getElementById('reportModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const select = document.getElementById('reportOrderSelect');
    const subtitle = document.getElementById('reportModalSubtitle');
    const workDone = document.getElementById('reportWorkDone');
    const obstacles = document.getElementById('reportObstacles');
    const parts = document.getElementById('reportParts');
    const cost = document.getElementById('reportCost');
    const condition = document.getElementById('reportCondition');
    const duration = document.getElementById('reportDuration');

    function apply(data) {
        if (!data) return;
        if (workDone && !workDone.value) {
            workDone.value = [data.scope, data.notes].filter(Boolean).join('\n');
        }
        if (parts && !parts.value && data.parts) parts.value = data.parts;
        if (cost && !cost.value && data.cost) cost.value = data.cost;
        if (condition && !condition.value && data.condition) condition.value = data.condition;
        if (duration && !duration.value && data.duration) duration.value = data.duration;
        if (subtitle) subtitle.textContent = data.number || '';
    }

    document.querySelectorAll('.js-report').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = btn.dataset;
            if (select) {
                select.value = d.orderId;
            }
            workDone.value = '';
            apply({
                scope: d.scope,
                notes: d.notes,
                parts: d.parts,
                cost: d.cost,
                condition: d.condition,
                duration: d.duration,
                number: d.number,
            });
            if (modal) modal.show();
        });
    });

    if (select) {
        select.addEventListener('change', function () {
            const opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) return;
            workDone.value = '';
            parts.value = '';
            cost.value = '';
            apply({
                scope: opt.dataset.scope,
                notes: opt.dataset.notes,
                condition: opt.dataset.condition,
                cost: opt.dataset.cost,
                number: opt.dataset.number,
            });
        });
    }

    document.querySelectorAll('.js-obstacle').forEach(function (chip) {
        chip.addEventListener('click', function () {
            const text = chip.dataset.text || '';
            if (!obstacles) return;
            if (obstacles.value.includes(text)) return;
            obstacles.value = obstacles.value ? obstacles.value.replace(/\s*$/, '') + '; ' + text : text;
        });
    });

    @if($selectedOrderId)
        if (select && select.value) {
            const opt = select.options[select.selectedIndex];
            apply({
                scope: opt.dataset.scope,
                notes: opt.dataset.notes,
                condition: opt.dataset.condition,
                cost: opt.dataset.cost,
                number: opt.dataset.number,
            });
        }
        if (modal) modal.show();
    @endif

    @if($errors->any() && old('work_order_id'))
        if (modal) modal.show();
    @endif
})();
</script>
@endsection
