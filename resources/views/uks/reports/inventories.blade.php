@extends('layouts.master')
@section('title', 'Laporan Inventaris UKS')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Laporan Inventaris @endslot
    @endcomponent
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Inventaris Obat dan Alat Kesehatan</h5>
            @include('uks.reports._export-buttons', ['report' => 'inventories'])</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Nama</th><th>Kategori</th><th>Stok</th><th>Satuan</th><th>Kedaluwarsa</th></tr></thead>
                    <tbody>
                    @forelse($inventories as $item)
                        <tr>
                            <td><strong>{{ $item->medicine_name }}</strong>@if($item->medicine_code)<div class="text-muted small">{{ $item->medicine_code }}</div>@endif</td>
                            <td>{{ $item->category ?? '—' }}</td>
                            <td>{{ $item->current_stock ?? 0 }}</td>
                            <td>{{ $item->unit ?? '—' }}</td>
                            <td class="text-muted small">{{ $item->expiry_date?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data inventaris.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($inventories->hasPages())<div class="card-body">{{ $inventories->links() }}</div>@endif
    </div>
@endsection
