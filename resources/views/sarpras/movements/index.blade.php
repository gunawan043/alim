@extends('layouts.master')
@section('title') Mutasi Aset @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Mutasi Aset
    @endcomponent

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-arrow-left-right-line me-2 text-primary"></i>Workflow Mutasi Aset</h5>
                <p class="text-muted small mb-0">Perpindahan bertahap: pengajuan → persetujuan → pengiriman → penerimaan → verifikasi.</p>
            </div>
            <a href="{{ route('sarpras.perpindahan.index') }}" class="btn btn-outline-primary btn-sm"><i class="ri-history-line me-1"></i>Riwayat Perpindahan</a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua status</option>@foreach(\App\Models\AssetMovement::STATUSES as $status)<option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $status)) }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                <div class="col-md-1"><a href="{{ route('sarpras.movements.index') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a></div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No. Mutasi</th><th>Aset</th><th>Dari</th><th>Ke</th><th>Pengaju</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($movements as $movement)
                            @php $tone = ['requested'=>'warning','approved'=>'info','in_transit'=>'primary','received'=>'info','verified'=>'success','completed'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$movement->status] ?? 'secondary'; @endphp
                            <tr>
                                <td><code class="small">{{ $movement->movement_number }}</code></td>
                                <td><div class="fw-medium">{{ $movement->asset?->asset_name ?? '-' }}</div><small class="text-muted">{{ $movement->asset?->asset_code }}</small></td>
                                <td class="small">{{ $movement->fromRoom?->room_name ?? '-' }}</td>
                                <td class="small">{{ $movement->toRoom?->room_name ?? '-' }}</td>
                                <td class="small">{{ $movement->requester?->name ?? '-' }}</td>
                                <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst(str_replace('_',' ', $movement->status)) }}</span></td>
                                <td class="small">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end"><a href="{{ route('sarpras.movements.show', ['id' => $movement->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td>
                            </tr>
                        @empty
                            <x-empty-state colspan="8" title="Belum ada mutasi aset" message="Mutasi aset akan tampil di sini setelah pengajuan dibuat." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($movements->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $movements])</div>@endif
    </div>
@endsection
