@extends('layouts.master')

@section('title', 'Farmasi UKS')

@section('css')
    @include('uks._ui-styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $hasFilter = request()->filled('status') || request()->filled('search');
        $rxData = $prescriptions->getCollection()->mapWithKeys(function ($prescription) use ($userId) {
            return [$prescription->id => [
                'no' => $prescription->prescription_no,
                'patient' => $prescription->patient?->student?->name ?? '-',
                'dispenseAction' => route('user.uks.pharmacy.dispense', ['userId' => $userId, 'uuid' => $prescription->id]),
                'cancelAction' => route('user.uks.pharmacy.cancel', ['userId' => $userId, 'uuid' => $prescription->id]),
                'items' => $prescription->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->medicine_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'instructions' => $item->instructions,
                    'remaining' => max(0, (float) $item->quantity - (float) $item->dispensed_quantity),
                    'unit' => $item->inventory?->unit ?? 'unit',
                    'stock' => $item->inventory?->current_stock,
                ])->values(),
            ]];
        });
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') UKS @endslot
        @slot('title') Farmasi & Resep @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach([
            ['label' => 'Resep Baru', 'value' => $stats['pending'], 'icon' => 'ri-file-list-3-line', 'color' => 'warning'],
            ['label' => 'Sedang Disiapkan', 'value' => $stats['prepared'], 'icon' => 'ri-flask-line', 'color' => 'info'],
            ['label' => 'Diserahkan Hari Ini', 'value' => $stats['dispensed_today'], 'icon' => 'ri-check-double-line', 'color' => 'success'],
            ['label' => 'Stok Menipis', 'value' => $stats['low_stock'], 'icon' => 'ri-alert-line', 'color' => 'danger'],
        ] as $card)
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-sm rounded bg-{{ $card['color'] }}-subtle d-flex align-items-center justify-content-center">
                                <i class="{{ $card['icon'] }} text-{{ $card['color'] }} fs-5"></i>
                            </span>
                            <div>
                                <p class="text-muted text-uppercase fs-11 mb-1">{{ $card['label'] }}</p>
                                <h4 class="mb-0 fw-bold">{{ $card['value'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Antrean Resep</h5>
                    <p class="text-muted mb-0 fs-12">Serahkan obat sesuai resep; stok otomatis berkurang.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <form method="GET" action="{{ route('user.uks.pharmacy.index', ['userId' => $userId]) }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari resep / pasien / obat" style="min-width: 220px;">
                            <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                        </form>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#pharmacyFilterModal">
                            <i class="bx bx-filter-alt align-bottom me-1"></i>Filter
                        </button>
                        <a href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                            <i class="ri-archive-line me-1"></i>Stok Obat
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($hasFilter)
            <div class="card-header py-2 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold"><i class="ri-filter-3-line me-1"></i>Filter Aktif:</span>
                    @if(request('status'))
                        <span class="filter-badge active">Status: {{ \App\Models\Uks\UksPrescription::STATUSES[request('status')] ?? request('status') }}</span>
                    @endif
                    @if(request('search'))
                        <span class="filter-badge active">Cari: {{ request('search') }}</span>
                    @endif
                    <a href="{{ route('user.uks.pharmacy.index', ['userId' => $userId]) }}" class="btn btn-sm btn-link text-danger ms-auto">
                        <i class="ri-close-circle-line me-1"></i>Hapus Semua
                    </a>
                </div>
            </div>
        @endif

        <div class="card-header py-2 uks-quick-filter">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted me-1"><i class="ri-flashlight-line me-1"></i>Quick Filter:</span>
                <a class="filter-badge {{ ! request('status') ? 'active' : '' }}" href="{{ route('user.uks.pharmacy.index', ['userId' => $userId]) }}">Semua</a>
                @foreach(\App\Models\Uks\UksPrescription::STATUSES as $key => $label)
                    <a class="filter-badge {{ request('status') === $key ? 'active' : '' }}"
                       href="{{ route('user.uks.pharmacy.index', ['userId' => $userId, 'status' => $key]) }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze" id="pharmacyTable">
                    <thead class="table-light">
                        <tr>
                            <th data-column="pasien">Pasien</th>
                            <th data-column="resep">No. Resep</th>
                            <th data-column="items">Item Obat</th>
                            <th data-column="status">Status</th>
                            <th data-column="waktu">Waktu</th>
                            <th data-column="action" class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prescriptions as $prescription)
                            @php
                                $pending = in_array($prescription->status, [\App\Models\Uks\UksPrescription::STATUS_NEW, \App\Models\Uks\UksPrescription::STATUS_PREPARED], true);
                                $statusColor = match ($prescription->status) {
                                    \App\Models\Uks\UksPrescription::STATUS_DISPENSED => 'success',
                                    \App\Models\Uks\UksPrescription::STATUS_CANCELLED => 'secondary',
                                    default => 'warning',
                                };
                            @endphp
                            <tr>
                                <td data-column="pasien">
                                    <div class="fw-semibold">{{ $prescription->patient?->student?->name ?? '-' }}</div>
                                    <div class="text-muted fs-11">
                                        {{ $prescription->patient?->medical_record_no ?? '-' }}
                                        @if($prescription->patient?->queue_number) · Antrean {{ $prescription->patient->queue_number }} @endif
                                    </div>
                                </td>
                                <td data-column="resep">
                                    <span class="fw-semibold">{{ $prescription->prescription_no }}</span>
                                    <div class="text-muted fs-11">{{ $prescription->prescribedBy?->name ?? '-' }}</div>
                                </td>
                                <td data-column="items">
                                    <ul class="mb-0 ps-3 fs-12 text-muted">
                                        @foreach($prescription->items as $item)
                                            <li>
                                                <span class="text-dark fw-medium">{{ $item->medicine_name }}</span>
                                                — {{ $item->dosage ?? '-' }} · {{ $item->quantity }} {{ $item->inventory?->unit ?? 'unit' }}
                                                @if($item->dispensed_quantity > 0) <span class="badge bg-success-subtle text-success ms-1">diserahkan {{ $item->dispensed_quantity }}</span> @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td data-column="status">
                                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $prescription->status_label }}</span>
                                </td>
                                <td data-column="waktu" class="fs-12 text-muted">
                                    {{ $prescription->prescribed_at?->format('d M Y H:i') }}
                                    @if($prescription->dispensed_at)
                                        <div class="fs-11">Diserahkan: {{ $prescription->dispensed_at->format('d M H:i') }}</div>
                                    @endif
                                </td>
                                <td data-column="action" class="text-end">
                                    <div class="dropdown uks-table-actions">
                                        <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($pending)
                                                <li>
                                                    <button type="button" class="dropdown-item btn-dispense-rx" data-bs-toggle="modal" data-bs-target="#dispenseModal" data-rx-id="{{ $prescription->id }}">
                                                        <i class="ri-check-line text-success me-2"></i>Serahkan Obat
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item btn-cancel-rx" data-bs-toggle="modal" data-bs-target="#cancelRxModal" data-rx-id="{{ $prescription->id }}">
                                                        <i class="ri-close-line text-danger me-2"></i>Batalkan Resep
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                            @endif
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.prescriptions.print', ['userId' => $userId, 'uuid' => $prescription->id]) }}" target="_blank">
                                                    <i class="ri-printer-line text-secondary me-2"></i>Cetak Resep
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $prescription->patient_id]) }}">
                                                    <i class="ri-file-user-line text-primary me-2"></i>Lembar Pemeriksaan
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="border-0 p-0">
                                    <div class="uks-empty">
                                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                        <h5 class="mt-2">Tidak ada resep</h5>
                                        <p class="text-muted mb-0">Resep dari lembar pemeriksaan akan muncul di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($prescriptions->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $prescriptions->withQueryString()])
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-alert-line me-2 text-danger"></i>Stok Menipis</h5>
                    <a href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-primary">Kelola</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light fs-12 text-muted"><tr><th>Obat</th><th class="text-center">Stok</th><th class="text-center">Min</th></tr></thead>
                            <tbody>
                                @forelse($lowStock as $medicine)
                                    <tr>
                                        <td class="fs-13">{{ $medicine->medicine_name }}</td>
                                        <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $medicine->current_stock }} {{ $medicine->unit }}</span></td>
                                        <td class="text-center fs-12 text-muted">{{ $medicine->min_stock_alert }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Semua stok aman.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-calendar-warning-line me-2 text-warning"></i>Kedaluwarsa ≤ 90 Hari</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light fs-12 text-muted"><tr><th>Obat</th><th class="text-center">Expired</th></tr></thead>
                            <tbody>
                                @forelse($expiring as $medicine)
                                    <tr>
                                        <td class="fs-13">{{ $medicine->medicine_name }}</td>
                                        <td class="text-center"><span class="badge bg-warning-subtle text-warning">{{ $medicine->expiry_date?->format('d M Y') }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3">Tidak ada obat mendekati kedaluwarsa.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-uks.filter-modal id="pharmacyFilterModal" title="Filter Resep & Farmasi" :action="route('user.uks.pharmacy.index', ['userId' => $userId])">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-file-list-3-line"></i>Status Resep</div>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\Uks\UksPrescription::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="No. resep / pasien / obat">
                </div>
            </div>
        </div>
    </x-uks.filter-modal>

    {{-- DISPENSE MODAL --}}
    <div class="modal fade" id="dispenseModal" tabindex="-1" aria-labelledby="dispenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" id="dispenseForm" action="#">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dispenseModalLabel"><i class="ri-capsule-line me-2 text-success"></i>Serahkan Obat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Pasien: <strong id="dispensePatient">-</strong> · Resep <strong id="dispenseNo">-</strong></p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light fs-12 text-muted">
                                    <tr><th>Obat</th><th>Aturan</th><th class="text-center">Sisa</th><th class="text-center" style="width:120px;">Serahkan</th><th class="text-center">Stok</th></tr>
                                </thead>
                                <tbody id="dispenseItems"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-check-line me-1"></i>Serahkan Obat</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- CANCEL MODAL --}}
    <div class="modal fade" id="cancelRxModal" tabindex="-1" aria-labelledby="cancelRxModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="cancelRxForm" action="#">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelRxModalLabel"><i class="ri-close-circle-line me-2 text-danger"></i>Batalkan Resep</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Batalkan resep <strong id="cancelRxNo">-</strong> untuk <strong id="cancelRxPatient">-</strong>?</p>
                        <label class="form-label">Alasan Pembatalan</label>
                        <textarea name="reason" rows="2" class="form-control" placeholder="Opsional..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="ri-close-line me-1"></i>Batalkan Resep</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rxData = @json($rxData);

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    document.querySelectorAll('.btn-dispense-rx').forEach(function (button) {
        button.addEventListener('click', function () {
            const data = rxData[this.dataset.rxId];
            if (!data) return;

            document.getElementById('dispensePatient').textContent = data.patient;
            document.getElementById('dispenseNo').textContent = data.no;
            document.getElementById('dispenseForm').action = data.dispenseAction;

            const tbody = document.getElementById('dispenseItems');
            tbody.innerHTML = '';
            data.items.forEach(function (item) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><span class="fw-medium">${escapeHtml(item.name)}</span></td>
                    <td class="fs-12 text-muted">${escapeHtml(item.dosage || '-')} ${item.frequency ? '· ' + escapeHtml(item.frequency) : ''}</td>
                    <td class="text-center">${escapeHtml(item.remaining)} ${escapeHtml(item.unit)}</td>
                    <td><input type="number" step="0.01" min="0" max="${escapeHtml(item.remaining)}" name="quantities[${escapeHtml(item.id)}]" class="form-control form-control-sm text-center" value="${escapeHtml(item.remaining)}"></td>
                    <td class="text-center fs-12">${item.stock !== null ? escapeHtml(item.stock) + ' ' + escapeHtml(item.unit) : 'Manual'}</td>
                `;
                tbody.appendChild(row);
            });
        });
    });

    document.querySelectorAll('.btn-cancel-rx').forEach(function (button) {
        button.addEventListener('click', function () {
            const data = rxData[this.dataset.rxId];
            if (!data) return;

            document.getElementById('cancelRxNo').textContent = data.no;
            document.getElementById('cancelRxPatient').textContent = data.patient;
            document.getElementById('cancelRxForm').action = data.cancelAction;
        });
    });
});
</script>
@endpush
