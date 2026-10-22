@extends('layouts.master')
@section('title', 'Laporan Penghargaan')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.reports.index', ['userId' => $userId]) }}">Laporan</a> @endslot
        @slot('title') Laporan Penghargaan @endslot
    @endcomponent
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Data Penghargaan Santri</h5>
            @include('uks.reports._export-buttons', ['report' => 'rewards'])</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Penghargaan</th><th>Kategori</th><th>Level</th></tr></thead>
                    <tbody>
                    @forelse($rewards as $reward)
                        <tr>
                            <td class="text-muted small">{{ $reward->awarded_date?->format('d/m/Y') ?? '—' }}</td>
                            <td><strong>{{ $reward->student?->name ?? '—' }}</strong></td>
                            <td>{{ $reward->title ?? '—' }}</td>
                            <td>{{ $reward->category ?? '—' }}</td>
                            <td>{{ $reward->level ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data penghargaan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rewards->hasPages())<div class="card-body">{{ $rewards->links() }}</div>@endif
    </div>
@endsection
