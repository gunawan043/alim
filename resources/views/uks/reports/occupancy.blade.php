@extends('layouts.master')
@section('title', 'Laporan Penghuni UKS')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Laporan Penghuni @endslot
    @endcomponent

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pasien Aktif / Dalam Perawatan</h5>
            <div class="d-flex gap-2">
                @include('uks.reports._export-buttons', ['report' => 'occupancy'])
                <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Kembali</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Pasien</th><th>Status</th><th>Lokasi</th><th>Bed</th><th>Masuk</th></tr></thead>
                    <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td><strong>{{ $patient->student?->name ?? '—' }}</strong><div class="text-muted small">{{ $patient->student?->nisn ?? '' }}</div></td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ \App\Models\Uks\UksPatient::$statusLabels[$patient->status] ?? $patient->status }}</span></td>
                            <td>{{ $patient->care_location_label ?? $patient->care_location ?? '—' }}</td>
                            <td>{{ $patient->currentBedAssignment?->bed?->identifier ?? $patient->bed_number ?? '—' }}</td>
                            <td class="text-muted small">{{ $patient->admitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pasien aktif.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($patients->hasPages())<div class="card-body">{{ $patients->links() }}</div>@endif
    </div>
@endsection
