@extends('layouts.master')
@section('title', 'Laporan Pelanggaran')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Laporan Pelanggaran @endslot
    @endcomponent
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Data Pelanggaran</h5>
            @include('uks.reports._export-buttons', ['report' => 'violations'])</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Kategori</th><th>Poin</th><th>Tindakan</th></tr></thead>
                    <tbody>
                    @forelse($violations as $violation)
                        <tr>
                            <td class="text-muted small">{{ $violation->violation_date?->format('d/m/Y') ?? '—' }}</td>
                            <td><strong>{{ $violation->student?->name ?? '—' }}</strong></td>
                            <td>{{ $violation->violation_category ?? '—' }}</td>
                            <td>{{ $violation->points ?? 0 }}</td>
                            <td>{{ $violation->action_taken ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data pelanggaran.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($violations->hasPages())<div class="card-body">{{ $violations->links() }}</div>@endif
    </div>
@endsection
