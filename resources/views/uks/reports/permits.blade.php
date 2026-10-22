@extends('layouts.master')
@section('title', 'Laporan Perizinan UKS')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Laporan Perizinan @endslot
    @endcomponent

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Riwayat Perizinan</h5>
            <div class="d-flex gap-2">
                @include('uks.reports._export-buttons', ['report' => 'permits'])
                <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Kembali</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Santri</th><th>Jenis</th><th>Tujuan</th><th>Berangkat</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($permits as $permit)
                        <tr>
                            <td><strong>{{ $permit->student?->name ?? '—' }}</strong></td>
                            <td>{{ $permit->permit_type ?? '—' }}</td>
                            <td>{{ $permit->destination ?? '—' }}</td>
                            <td class="text-muted small">{{ $permit->departure_datetime?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $permit->status ?? '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data perizinan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($permits->hasPages())<div class="card-body">{{ $permits->links() }}</div>@endif
    </div>
@endsection
