@extends('layouts.master')
@section('title') Laporan Kebersihan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.kebersihan.index') }}">Kebersihan</a> @endslot
        @slot('title') Laporan Kebersihan
    @endcomponent

    <div class="card mb-3"><div class="card-body"><form method="GET" action="{{ route('sarpras.kebersihan.reports') }}" class="row g-2 align-items-end">@if(request('area_type'))<input type="hidden" name="area_type" value="{{ request('area_type') }}">@endif
        <div class="col-md-3"><label class="form-label small fw-semibold">Area</label><select name="area_id" class="form-select"><option value="">Semua</option>@foreach($areas as $area)<option value="{{ $area->id }}" {{ request('area_id') === $area->id ? 'selected' : '' }}>{{ $area->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="date" class="form-control" value="{{ request('date') }}"></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['submitted'=>'Menunggu Verifikasi','verified'=>'Diverifikasi','rejected'=>'Dikembalikan'] as $key=>$label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
        <div class="col-md-2"><a href="{{ route('sarpras.kebersihan.reports') }}" class="btn btn-light w-100">Reset</a></div>
        <div class="col-md-1"><a href="{{ route('sarpras.kebersihan.index') }}" class="btn btn-outline-secondary w-100"><i class="ri-arrow-left-line"></i></a></div>
    </form></div></div>

    <div class="card"><div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-success"></i>Laporan Kebersihan</h5><p class="text-muted small mb-0">Verifikasi laporan, periksa foto sebelum/sesudah, dan catatan petugas.</p></div>@if($canReport)<a href="{{ route('sarpras.kebersihan.report.create') }}" class="btn btn-success btn-sm"><i class="ri-add-line me-1"></i>Laporan Harian</a>@endif</div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Area</th><th>Petugas</th><th>Menggantikan</th><th>Kondisi</th><th>Skor</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($reports as $report)
            @php $tone = ['submitted'=>'warning','verified'=>'success','rejected'=>'danger'][$report->status] ?? 'secondary'; @endphp
            <tr><td class="small">{{ $report->report_date?->format('d/m/Y') }}</td><td><div class="fw-medium">{{ $report->area?->name }}</div><small class="text-muted">{{ $report->area?->building?->building_name }}</small></td><td class="small">{{ $report->officer?->name }}</td><td class="small">{{ $report->substituteFor?->name ?? '-' }}</td><td class="small">{{ ucfirst(str_replace('_',' ', $report->overall_condition)) }}</td><td class="small">{{ $report->score ?? '-' }}</td><td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst(str_replace('_',' ', $report->status)) }}</span></td><td class="text-end"><a href="{{ route('sarpras.kebersihan.report.show', ['reportId' => $report->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
        @empty
            <x-empty-state colspan="8" title="Belum ada laporan" message="Laporan harian petugas akan tampil di sini." />
        @endforelse
    </tbody></table></div></div>
    @if($reports->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $reports])</div>@endif</div>
@endsection
