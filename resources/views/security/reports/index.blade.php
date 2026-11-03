@extends('layouts.master')

@section('title', 'Laporan Keamanan')

@include('security.partials.styles')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('title') Laporan @endslot
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end" action="{{ route('user.keamanan.reports.index', ['userId' => $userId]) }}">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan</button>
                    <a href="{{ route('user.keamanan.reports.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Bulan Ini</a>
                </div>
                <div class="col-md-3 text-md-end">
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-file-excel-2-line me-1"></i> Ekspor CSV
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('user.keamanan.reports.guests', ['userId' => $userId, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Laporan Tamu</a></li>
                            <li><a class="dropdown-item" href="{{ route('user.keamanan.reports.incidents', ['userId' => $userId, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Laporan Insiden</a></li>
                            <li><a class="dropdown-item" href="{{ route('user.keamanan.reports.patrols', ['userId' => $userId, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Laporan Patroli</a></li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Tamu', 'value' => number_format($guestTotal), 'icon' => 'ri-user-received-line', 'color' => 'primary'],
            ['label' => 'Kendaraan', 'value' => number_format($vehicleTotal), 'icon' => 'ri-roadster-line', 'color' => 'violet'],
            ['label' => 'Insiden', 'value' => number_format($incidentTotal), 'icon' => 'ri-alert-line', 'color' => 'danger'],
            ['label' => 'Kepatuhan Patroli', 'value' => $patrolRate.'%', 'icon' => 'ri-shield-check-line', 'color' => 'success'],
        ],
    ])
    @endcomponent

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Kunjungan Tamu per Hari</h5>
                    <p class="text-muted mb-0 small">{{ $from->translatedFormat('d M Y') }} — {{ $to->translatedFormat('d M Y') }}</p>
                </div>
                <div class="card-body">
                    @if($dailyGuests->isEmpty())
                        <x-empty-state title="Belum ada kunjungan" message="Tidak ada tamu pada rentang tanggal ini." />
                    @else
                        <div class="d-flex align-items-end justify-content-between gap-1" style="height: 220px; overflow-x: auto;">
                            @foreach($dailyGuests as $day)
                                @php $height = max(6, (int) round($day->total / $maxDaily * 100)); @endphp
                                <div class="d-flex flex-column align-items-center" style="min-width: 34px;">
                                    <span class="small text-muted mb-1">{{ $day->total }}</span>
                                    <div class="w-100 rounded-top bg-primary" style="height: {{ $height }}%; min-height: 6px;"></div>
                                    <span class="small text-muted mt-2" style="font-size:10px;">
                                        {{ \Illuminate\Support\Carbon::parse($day->day)->format('d/m') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Insiden per Jenis</h5>
                </div>
                <div class="card-body">
                    @forelse($incidentByType as $row)
                        @php $pct = $incidentTotal > 0 ? (int) round($row->total / $incidentTotal * 100) : 0; @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $row->label }}</span>
                                <span class="fw-semibold">{{ $row->total }}</span>
                            </div>
                            <div class="metric-bar"><span class="bg-danger" style="width: {{ $pct }}%"></span></div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada insiden pada periode ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Status Insiden</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Status</th><th class="text-end">Jumlah</th></tr>
                            </thead>
                            <tbody>
                                @foreach(\App\Models\Security\SecurityIncident::STATUS_OPTIONS as $value => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end fw-semibold">{{ (int) ($incidentByStatus[$value]->total ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Keperluan Tamu Terbanyak</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Keperluan</th><th class="text-end">Jumlah</th></tr>
                            </thead>
                            <tbody>
                                @forelse($guestByPurpose as $row)
                                    <tr>
                                        <td>{{ $row->purpose }}</td>
                                        <td class="text-end fw-semibold">{{ $row->total }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3">Belum ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">Kepatuhan Patroli</div>
                        <div class="small text-muted">{{ $patrolDone }} dari {{ $patrolTotal }} jadwal selesai pada periode ini.</div>
                    </div>
                    <div style="min-width: 220px;">
                        <div class="metric-bar"><span class="bg-success" style="width: {{ $patrolRate }}%"></span></div>
                    </div>
                    <span class="fw-bold fs-5">{{ $patrolRate }}%</span>
                </div>
            </div>
        </div>
    </div>
@endsection
