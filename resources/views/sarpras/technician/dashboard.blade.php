@extends('layouts.master')
@section('title') Workspace Teknisi @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Workspace Teknisi
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Work Order Aktif','value'=>$stats['active'],'tone'=>'primary','icon'=>'ri-tools-line'],
        ['label'=>'Ditugaskan','value'=>$stats['assigned'],'tone'=>'info','icon'=>'ri-user-received-line'],
        ['label'=>'Selesai','value'=>$stats['completed'],'tone'=>'success','icon'=>'ri-check-double-line'],
        ['label'=>'Belum Diambil','value'=>$stats['available'],'tone'=>'warning','icon'=>'ri-inbox-unarchive-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.teknisi.dashboard')" :reset="route('sarpras.teknisi.dashboard')">
        <div class="col-md-4"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Order / aset / lokasi…"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['draft'=>'Draft','dispatched'=>'Dispatched','in_progress'=>'Dikerjakan','paused'=>'Jeda','waiting_parts'=>'Menunggu Sparepart','completed'=>'Selesai','closed'=>'Ditutup'] as $k=>$v)<option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
    </x-sarpras-filter-card>

    @if($available->isNotEmpty())
        <div class="card mb-3 border-info">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-inbox-unarchive-line me-1 text-info"></i>Work Order Belum Diambil <span class="badge bg-info ms-2">{{ $available->count() }}</span></h5></div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Order #</th><th>Aset</th><th>Tipe</th><th>Jadwal</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @foreach($available as $wo)
                    <tr><td><code>{{ $wo->order_number }}</code></td><td><div>{{ $wo->target_name }}</div><small class="text-muted">{{ $wo->target_location }}</small></td><td><span class="badge bg-secondary">{{ ucfirst($wo->type) }}</span></td><td class="small">{{ $wo->scheduled_date?->format('d/m/Y') ?? '-' }}</td><td class="text-end"><form action="{{ route('sarpras.teknisi.claim', ['orderId' => $wo->id]) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-info"><i class="ri-hand-heart-line me-1"></i>Ambil</button></form></td></tr>
                @endforeach
            </tbody></table></div></div>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0"><i class="ri-tools-line me-1"></i>Work Order Saya</h5><span class="badge bg-primary">{{ $orders->where('status', 'in_progress')->count() }} aktif</span></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Order #</th><th>Aset</th><th>Tipe</th><th>Status</th><th>Jadwal</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($orders as $wo)
                <tr>
                    <td><code>{{ $wo->order_number }}</code></td>
                    <td><div class="fw-medium">{{ $wo->target_name }}</div><small class="text-muted">{{ $wo->asset?->asset_code ?? $wo->target_location }}</small></td>
                    <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ', $wo->type)) }}</span></td>
                    <td><span class="badge bg-{{ $wo->status === 'in_progress' ? 'success' : ($wo->status === 'completed' ? 'primary' : 'info') }}-subtle text-{{ $wo->status === 'in_progress' ? 'success' : ($wo->status === 'completed' ? 'primary' : 'info') }}">{{ ucfirst(str_replace('_',' ', $wo->status)) }}</span></td>
                    <td class="small">{{ $wo->scheduled_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-end"><a href="{{ route('sarpras.teknisi.show', ['orderId' => $wo->id]) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
                </tr>
            @empty
                <x-empty-state colspan="6" title="Tidak ada work order" message="Work order yang ditugaskan URT akan muncul di sini." />
            @endforelse
        </tbody></table></div></div>
        @if($orders->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $orders])</div>@endif
    </div>
@endsection
