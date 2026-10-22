@extends('layouts.master')
@section('title', 'Laporan Presensi UKS')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $statusLabels = \App\Models\Uks\UksPatient::$statusLabels;
    @endphp
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Laporan Presensi @endslot
    @endcomponent

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Bulan</label>
                    <select name="month" class="form-select form-select-sm">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ (int) ($month ?? now()->month) === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Tahun</label>
                    <input type="number" name="year" class="form-control form-control-sm" value="{{ $year ?? now()->year }}" min="2020" max="2100">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua status</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" {{ ($status ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach($statusLabels as $value => $label)
            @if(($monthlyTotals[$value] ?? 0) > 0)
                <div class="col-md-3">
                    <div class="card py-2"><div class="card-body py-1"><div class="text-muted small">{{ $label }}</div><strong class="fs-5">{{ $monthlyTotals[$value] }}</strong></div></div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Kunjungan / Presensi UKS</h5>
            @include('uks.reports._export-buttons', ['report' => 'attendance'])</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Pasien</th><th>Status</th><th>Lokasi</th><th>Datang</th></tr></thead>
                    <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td class="text-muted small">{{ $patient->arrived_at?->format('d/m/Y') ?? '—' }}</td>
                            <td><strong>{{ $patient->student?->name ?? '—' }}</strong></td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $statusLabels[$patient->status] ?? $patient->status }}</span></td>
                            <td>{{ $patient->care_location_label ?? '—' }}</td>
                            <td class="text-muted small">{{ $patient->arrived_at?->format('H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data presensi pada periode ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($patients->hasPages())<div class="card-body">{{ $patients->links() }}</div>@endif
    </div>
@endsection
