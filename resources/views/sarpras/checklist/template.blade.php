@extends('layouts.master')
@section('title') Template Checklist — {{ $template->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.checklist.index') }}">Checklist</a> @endslot
        @slot('title') {{ $template->name }}
    @endcomponent

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0">{{ $template->name }}</h5><div class="text-muted small"><code>{{ $template->code }}</code> · {{ $template->items->count() }} item pemeriksaan</div></div>
            <a href="{{ route('sarpras.checklist.index') }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light"><tr><th style="width:45px">No</th><th>Item Pemeriksaan</th><th>Standar</th><th>Metode</th><th>Frekuensi</th><th>Hasil</th></tr></thead>
                    <tbody>
                    @php $lastGroup = null; @endphp
                    @forelse($template->items as $index => $item)
                        @if($item->group_label && $item->group_label !== $lastGroup)
                            <tr class="table-secondary"><td colspan="6" class="fw-semibold">{{ $item->group_label }}</td></tr>
                            @php $lastGroup = $item->group_label; @endphp
                        @endif
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item->label }}</td>
                            <td class="small">{{ $item->standard ?? '-' }}</td>
                            <td class="small">{{ $item->method ?? '-' }}</td>
                            <td class="small">{{ $item->frequency ?? '-' }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">V / X</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada item checklist.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
