@extends('layouts.master')
@section('title', 'Pengajuan SIPLah')

@section('css')
<style>
    .table-container{position:relative;width:100%;overflow-x:auto}
    .table-freeze{table-layout:auto;min-width:max-content;margin-bottom:0;width:100%}
    .table-freeze th,.table-freeze td{white-space:normal;vertical-align:middle;padding:12px 16px;word-break:break-word}
    .card-animate{transition:all .3s ease}
    .card-animate:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(15,23,42,.10)}
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarpras @endslot
        @slot('title') Pengajuan SIPLah @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error') || ($error ?? null))<div class="alert alert-danger"><i class="ri-error-warning-line me-1"></i>{{ session('error') ?? $error }}</div>@endif

    <div class="row mb-3">
        @foreach([
            ['Total Pengajuan', $summary['total'] ?? 0, 'ri-file-list-3-line', 'primary'],
            ['Menunggu Persetujuan', $summary['submitted'] ?? 0, 'ri-time-line', 'warning'],
            ['Disetujui', $summary['approved'] ?? 0, 'ri-check-double-line', 'success'],
            ['Diproses BUMP', $summary['processing'] ?? 0, 'ri-loader-4-line', 'info'],
            ['Selesai', $summary['completed'] ?? 0, 'ri-checkbox-circle-line', 'success'],
        ] as [$label, $value, $icon, $color])
            <div class="col-xl col-md-4 mb-2">
                <div class="card card-animate h-100"><div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded"><i class="{{ $icon }} text-{{ $color }}"></i></span></span>
                        <div><div class="text-muted small">{{ $label }}</div><h4 class="fw-semibold mb-0">{{ number_format($value, 0, ',', '.') }}</h4></div>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1 text-primary"></i>Daftar Pengajuan SIPLah</h5>
                <p class="text-muted small mb-0">Proposal kebutuhan barang untuk diproses melalui mekanisme SIPLah oleh BUMP.</p>
            </div>
            <a href="{{ route('user.siplah.create', ['userId' => $userId]) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Buat Pengajuan</a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-lg-4"><label class="form-label small">Pencarian</label><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nomor, program, atau referensi..."></div>
                <div class="col-md-3"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Semua status</option>@foreach(['draft'=>'Draft','submitted'=>'Diajukan','approved'=>'Disetujui','rejected'=>'Ditolak','sent'=>'Terkirim ke BUMP','processing'=>'Diproses','partially_fulfilled'=>'Dipenuhi Sebagian','completed'=>'Selesai','cancelled'=>'Dibatalkan'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small">Kategori</label><select name="category" class="form-select form-select-sm"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category')===$category)>{{ $category }}</option>@endforeach</select></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button><a href="{{ route('user.siplah.index',['userId'=>$userId]) }}" class="btn btn-light btn-sm ms-1">Reset</a></div>
            </form>

            <div class="table-container"><table class="table table-hover table-freeze align-middle">
                <thead class="table-light"><tr><th>Nomor</th><th>Program</th><th>Kategori</th><th>Unit</th><th>Status ALIM</th><th>Status BUMP</th><th class="text-end">Estimasi</th><th>Tanggal</th><th></th></tr></thead>
                <tbody>
                @forelse($proposals as $proposal)
                    @php $proposalCategories = collect($proposal['items'] ?? [])->pluck('category')->filter()->unique(); @endphp
                    <tr>
                        <td class="font-monospace small">{{ $proposal['number'] ?? '—' }}</td>
                        <td><div class="fw-medium">{{ $proposal['program_name'] ?? '—' }}</div><div class="small text-muted">{{ \Illuminate\Support\Str::limit($proposal['purpose'] ?? '', 60) }}</div></td>
                        <td class="small">{{ $proposalCategories->isEmpty() ? '—' : $proposalCategories->implode(', ') }}</td>
                        <td class="small">{{ data_get($proposal, 'business_unit.name', '—') }}</td>
                        <td><span class="badge bg-info-subtle text-info">{{ $proposal['status_label'] ?? $proposal['status'] ?? '—' }}</span></td>
                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $proposal['bump_status_label'] ?? '—' }}</span></td>
                        <td class="text-end">Rp {{ number_format((float) ($proposal['estimated_total'] ?? 0), 0, ',', '.') }}</td>
                        <td class="small text-muted">{{ isset($proposal['created_at']) ? \Illuminate\Support\Carbon::parse($proposal['created_at'])->format('d/m/Y') : '—' }}</td>
                        <td class="text-end"><a href="{{ route('user.siplah.show', ['userId'=>$userId,'number'=>$proposal['number']]) }}" class="btn btn-sm btn-soft-primary">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">Belum ada pengajuan SIPLah.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
@endsection
