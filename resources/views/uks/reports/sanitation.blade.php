@extends('layouts.master')
@section('title', 'Laporan Kebersihan UKS')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Laporan Kebersihan @endslot
    @endcomponent

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Inspeksi Sanitasi</h5>
            <div class="d-flex gap-2">
                @include('uks.reports._export-buttons', ['report' => 'sanitation'])
                <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Kembali</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Lokasi</th><th>Skor</th><th>Status</th><th>Petugas</th></tr></thead>
                    <tbody>
                    @forelse($inspections as $inspection)
                        <tr>
                            <td class="text-muted small">{{ $inspection->inspection_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $inspection->location_type_text ?? $inspection->location_type ?? '—' }}</td>
                            <td><strong>{{ $inspection->score ?? 0 }}</strong> <span class="text-muted small">({{ $inspection->score_label ?? '—' }})</span></td>
                            <td>@if($inspection->is_passed)<span class="badge bg-success-subtle text-success">Lulus</span>@else<span class="badge bg-warning-subtle text-warning">Perlu Tindak Lanjut</span>@endif</td>
                            <td class="text-muted small">{{ $inspection->inspectedBy?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data inspeksi sanitasi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($inspections->hasPages())<div class="card-body">{{ $inspections->links() }}</div>@endif
    </div>
@endsection
