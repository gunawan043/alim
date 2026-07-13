@extends('layouts.master')
@section('title') Asset Passport — {{ $asset->asset_name }} @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.aset.index') }}">Aset</a> @endslot
    @slot('li_3') <a href="{{ route('sarpras.aset.show', $asset->id) }}">Detail</a> @endslot
    @slot('title') Passport @endslot
@endcomponent

<div class="row mb-3">
    <div class="col-12">
        <ol class="breadcrumb mb-3">
            <li><a href="{{ route('sarpras.aset.index') }}" class="text-muted"><i class="mdi mdi-arrow-left"></i> Kembali ke Daftar Aset</a></li>
        </ol>
    </div>
</div>

{{-- Tabs Navigation --}}
<ul class="nav nav-tabs nav-tabs-custom mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#passport-overview">Overview</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#passport-timeline">Timeline</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#passport-repairs">Repair History</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#passport-maintenance">Maintenance</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#passport-movements">Movements</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#passport-loans">Loans</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#passport-audits">Stock Opname</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#passport-financial">Financial Summary</a>
    </li>
</ul>

<div class="tab-content">
    {{-- ======================== OVERVIEW TAB ======================== --}}
    <div class="tab-pane fade show active" id="passport-overview">
        <div class="row">
            {{-- Identity Card --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Identity</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Asset Name</p>
                                <strong>{{ $asset->asset_name }}</strong>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Asset Code</p>
                                <strong>{{ $asset->asset_code }}</strong>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Brand / Model</p>
                                {{ $asset->brand ?? '-' }} / {{ $asset->model ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Serial Number</p>
                                {{ $asset->serial_number ?? '-' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Category</p>
                                {{ $asset->category?->name ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Condition</p>
                                <span class="badge bg-{{ match($asset->condition) {
                                    'baik' => 'success',
                                    'rusak_ringan' => 'warning',
                                    'rusak_sedang' => 'danger',
                                    'rusak_berat' => 'dark',
                                    'hilang' => 'secondary',
                                    default => 'info'
                                }}">{{ $asset->condition }}</span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Room</p>
                                {{ $asset->room?->room_name ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Building</p>
                                {{ $asset->room?->building?->building_name ?? '-' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">School</p>
                                {{ $asset->room?->school?->name ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Work Unit</p>
                                {{ $asset->workUnit?->name ?? '-' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">PIC / Creator</p>
                                {{ $asset->creator?->name ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Status</p>
                                <span class="badge bg-{{ $asset->status === 'tersedia' ? 'success' : 'primary' }}">{{ $asset->status }}</span>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Purchase Date</p>
                                {{ $asset->acquisition_date?->format('d M Y') ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Acquisition Source</p>
                                {{ $asset->acquisition_source ?? '-' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Supplier</p>
                                {{ $asset->supplier_name ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Vendor</p>
                                {{ $asset->supplier_name ?? '-' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Warranty Provider</p>
                                {{ $asset->warranty_provider ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Warranty Period</p>
                                @if($asset->warranty_start_date)
                                    {{ $asset->warranty_start_date->format('d M Y') }} - {{ $asset->warranty_end_date?->format('d M Y') ?? 'Active' }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Remaining Warranty</p>
                                @if($asset->warranty_end_date)
                                    @php $days = now()->diffInDays($asset->warranty_end_date, false); @endphp
                                    {{ $days >= 0 ? $days . ' days' : 'Expired' }}
                                @else
                                    -
                                @endif
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Last Valuation Date</p>
                                {{ $asset->last_valuation_date?->format('d M Y') ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- QR Code & Photo --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">QR Code</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ route('sarpras.qr.generate', $asset->id) }}" alt="QR" class="img-fluid mb-3" style="max-height: 250px;">
                        <p class="text-muted">Scan untuk melihat passport aset</p>
                    </div>
                </div>
                {{-- Photo Gallery --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Photos</h5>
                    </div>
                    <div class="card-body">
                        @if($asset->photos && $asset->photos->count())
                            <div class="row g-2">
                                @foreach($asset->photos as $photo)
                                    <div class="col-6">
                                        <img src="{{ asset('storage/' . $photo->photo_path) }}" class="img-fluid rounded" style="aspect-ratio:1/1;object-fit:cover;width:100%;height:100px;">
                                    </div>
                                @endforeach
                            </div>
                        @elseif($asset->photo_path)
                            <img src="{{ asset('storage/' . $asset->photo_path) }}" class="img-fluid rounded">
                        @else
                            <p class="text-muted">No photos available</p>
                        @endif
                    </div>
                </div>

                {{-- Health & Criticality --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Health & Risk</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <p class="mb-1 text-muted">Health Score</p>
                            @if($asset->healthMetric)
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-success" style="width:{{ $asset->healthMetric->overall_health ?? 100 }}%"></div>
                                </div>
                                <small>{{ $asset->healthMetric->overall_health ?? 100 }}/100</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                        <div class="mb-2">
                            <p class="mb-1 text-muted">Criticality</p>
                            @php
                                $crit = $asset->criticality ?? 'normal';
                                $critBadge = match($crit){
                                    'high' => 'danger', 'medium' => 'warning', default => 'info'
                                };
                            @endphp
                            <span class="badge bg-{{ $critBadge }}">{{ ucfirst($crit) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== TIMELINE TAB ======================== --}}
    <div class="tab-pane fade" id="passport-timeline">
        <div class="card">
            <div class="card-header"><h5>Asset Lifecycle Timeline</h5></div>
            <div class="card-body">
                @php
                    $events = $passport['history'] ?? [];
                    if (!$events) {
                        $events = $asset->eventLogs()->orderByDesc('created_at')->limit(100)->get()->toArray();
                    }
                @endphp
                @forelse($events as $evt)
                    <div class="timeline-item">
                        <div class="timeline-badge bg-{{ match($evt['type'] ?? '') {
                            'asset_created' => 'success',
                            'asset_moved' => 'info',
                            'asset_borrowed' => 'warning',
                            'asset_returned' => 'success',
                            'repair_started' => 'danger',
                            'repair_completed' => 'primary',
                            'audit_conducted' => 'secondary',
                            'stock_opname' => 'dark',
                            'photo_uploaded' => 'info',
                            'asset_disposed' => 'danger',
                            'status_change' => 'warning',
                            default => 'light'
                        }}"></div>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h6 class="timeline-title">{{ Str::title(str_replace('_', ' ', $evt['type'] ?? '')) }}</h6>
                                <span class="text-muted">{{ \Carbon\Carbon::parse($evt['date'] ?? now())->diffForHumans() }}</span>
                            </div>
                            @if($evt['detail'])
                                <div class="timeline-body">
                                    <pre class="mb-0 small">{{ is_array($evt['detail']) ? json_encode($evt['detail'], JSON_PRETTY_PRINT) : $evt['detail'] }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No events recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ======================== REPAIR HISTORY TAB ======================== --}}
    <div class="tab-pane fade" id="passport-repairs">
        <div class="card">
            <div class="card-header"><h5>Repair History</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Problem</th>
                                <th>Technician</th>
                                <th>Cost</th>
                                <th>Duration</th>
                                <th>Result</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($passport['repairs'] ?? []) as $r)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($r['date'])->format('d M Y') }}</td>
                                    <td>{{ $r['problem'] ?? '-' }}</td>
                                    <td>{{ $r['technician'] ?? '-' }}</td>
                                    <td>Rp {{ number_format($r['cost'] ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $r['duration'] ?? '-' }}</td>
                                    <td>{{ $r['result'] ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $r['status'] === 'resolved' ? 'success' : 'primary' }}">{{ $r['status'] ?? 'pending' }}</span></td>
                                </tr>
                            @else
                                @php
                                    $repairs = $asset->repairRequests()->with(['assignedTo', 'workOrders'])->orderByDesc('created_at')->limit(20)->get();
                                @endphp
                                @forelse($repairs as $rr)
                                    <tr>
                                        <td>{{ $rr->created_at?->format('d M Y') }}</td>
                                        <td>{{ $rr->title ?? $rr->description }}</td>
                                        <td>{{ $rr->assignedTo?->name ?? '-' }}</td>
                                        <td>Rp {{ number_format($rr->labor_cost ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ $rr->workOrders->first()?->actual_start?->diff($rr->workOrders->first()?->actual_end)?->format('%d days %h hours') ?? '-' }}</td>
                                        <td>{{ $rr->result_description ?? '-' }}</td>
                                        <td><span class="badge bg-{{ match($rr->status){
                                            'approved'=>'success','in_progress'=>'warning','completed'=>'info', default:'secondary'
                                        }}">{{ $rr->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">No repair records found.</td></tr>
                                @endforelse
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== MAINTENANCE TAB ======================== --}}
    <div class="tab-pane fade" id="passport-maintenance">
        <div class="card">
            <div class="card-header"><h5>Maintenance History</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Schedule</th>
                                <th>Actual Date</th>
                                <th>Checklist</th>
                                <th>Technician</th>
                                <th>Cost</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($passport['maint_histories'] ?? $asset->maintenanceHistories()->orderByDesc('performed_date')->limit(20)->get()) as $m)
                                <tr>
                                    <td>{{ isset($m->schedule) ? $m->schedule : ($m->created_at?->format('d M Y')) }}</td>
                                    <td>{{ $m->performed_date?->format('d M Y') ?? '-' }}</td>
                                    <td>{{ $m->work_description ?? '-' }}</td>
                                    <td>{{ $m->performed_by_name ?? '-' }}</td>
                                    <td>Rp {{ number_format($m->cost ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $m->condition_after ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No maintenance records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== MOVEMENTS TAB ======================== --}}
    <div class="tab-pane fade" id="passport-movements">
        <div class="card">
            <div class="card-header"><h5>Movement History</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>PIC</th>
                                <th>Approved By</th>
                                <th>Date</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $movements = $asset->movements()->with(['fromRoom', 'toRoom', 'requester', 'approver'])->orderByDesc('created_at')->limit(20)->get();
                            @endphp
                            @forelse($movements as $mv)
                                <tr>
                                    <td>{{ $mv->fromRoom?->room_name ?? '-' }}</td>
                                    <td>{{ $mv->toRoom?->room_name ?? '-' }}</td>
                                    <td>{{ $mv->holder?->name ?? '-' }}</td>
                                    <td>{{ $mv->approver?->name ?? '-' }}</td>
                                    <td>{{ $mv->created_at?->format('d M Y') ?? ($mv->completed_at?->format('d M Y') ?? '-') }}</td>
                                    <td>{{ $mv->reason ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No movement records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== LOANS TAB ======================== --}}
    <div class="tab-pane fade" id="passport-loans">
        <div class="card">
            <div class="card-header"><h5>Loan History</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Borrower</th>
                                <th>Purpose</th>
                                <th>Borrow Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($passport['loans'] ?? $asset->loans()->with('borrower')->orderByDesc('created_at')->limit(20)->get()) as $loan)
                                <tr>
                                    <td>{{ $loan->borrower?->name ?? $loan->borrower_name ?? '-' }}</td>
                                    <td>{{ $loan->purpose ?? '-' }}</td>
                                    <td>{{ $loan->start_date?->format('d M Y') ?? '-' }}</td>
                                    <td>{{ $loan->end_date?->format('d M Y') ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $loan->status === 'returned' ? 'success' : 'warning' }}">{{ $loan->status ?? 'active' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No loan records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== STOCK OPNAME TAB ======================== --}}
    <div class="tab-pane fade" id="passport-audits">
        <div class="card">
            <div class="card-header"><h5>Stock Opname History</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Session</th>
                                <th>Auditor</th>
                                <th>Result</th>
                                <th>Variance</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $opnameItems = \App\Models\StockOpnameItem::with(['session.officers' => function($q){ $q->withPivot('scanned_at','observed_at'); }])
                                    ->where('asset_id', $asset->id)
                                    ->orderByDesc('observed_at')
                                    ->limit(20)
                                    ->get();
                            @endphp
                            @forelse($opnameItems as $item)
                                <tr>
                                    <td>{{ $item->session?->session_number ?? $item->id }}</td>
                                    <td>{{ $item->session?->auditor_name ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $item->is_match ? 'success' : 'danger' }}">{{ $item->is_match ? 'Match' : 'Mismatch' }}</span></td>
                                    <td>{{ $item->variance_reason ?? '-' }}</td>
                                    <td>{{ $item->observed_at?->format('d M Y') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No stock opname records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== FINANCIAL SUMMARY TAB ======================== --}}
    <div class="tab-pane fade" id="passport-financial">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5>Financial Summary</h5></div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td width="40%" class="text-muted">Purchase Cost</td>
                                <td><strong>Rp {{ number_format($asset->acquisition_price ?? 0, 0, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Repair Cost</td>
                                <td>Rp {{ number_format($passport['costs']['total_repair'] ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Maintenance Cost</td>
                                <td>Rp {{ number_format($passport['costs']['total_maintenance'] ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Residual Value</td>
                                <td>Rp {{ number_format($asset->current_value ?? ($asset->acquisition_price ?? 0) - ($asset->depreciation_per_year ?? 0), 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Annual Depreciation</td>
                                <td>Rp {{ number_format($asset->depreciation_per_year ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Book Value</td>
                                <td><strong>Rp {{ number_format($asset->current_value ?? ($asset->acquisition_price ?? 0), 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5>Cost Breakdown</h5></div>
                    <div class="card-body">
                        @foreach(($passport['costs_detail'] ?? []) as $costCat)
                            <div class="mb-2">
                                <small class="text-muted">{{ $costCat['category'] ?? 'Other' }}</small>
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar bg-primary" style="width:{{ min(100, ($costCat['total'] / ($asset->acquisition_price ?? 1)) * 100) }}%"></div>
                                </div>
                                <small>Rp {{ number_format($costCat['total'], 0, ',', '.') }} ({{ $costCat['count'] }} records)</small>
                            </div>
                        @endforeach
                        @if(empty($passport['costs_detail'] ?? []))
                            <p class="text-muted small">No cost breakdown available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection