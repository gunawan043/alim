@extends('layouts.master')
@section('title') Permintaan Pengadaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('title') Pengadaan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0">Riwayat Permintaan Pengadaan</h6>
                <a href="{{ route('sarpras.user.pengadaan.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success">
                    <i class="ri-add-line me-1"></i>Ajukan Pengadaan
                </a>
            </div>
            <div class="card-body p-0">
                @if($procurements->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>No. Pengajuan</th>
                                <th>Nama Barang</th>
                                <th>Jumlah</th>
                                <th>Estimasi Budget</th>
                                <th>Urgensi</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($procurements as $p)
                            <tr>
                                <td><code class="small">{{ $p->request_number }}</code></td>
                                <td class="small">{{ $p->items->first()?->item_name ?? '-' }}</td>
                                <td class="text-center small">{{ $p->items->first()?->quantity ?? '-' }} {{ $p->items->first()?->unit ?? '' }}</td>
                                <td class="text-end small">
                                    {{ $p->total_estimated_budget ? 'Rp ' . number_format($p->total_estimated_budget,0,',','.') : '-' }}
                                </td>
                                <td>
                                    @php $u = ['biasa'=>'secondary','urgent'=>'warning','kritis'=>'danger'][$p->urgency] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $u }}-subtle text-{{ $u }}" style="font-size:10px;">
                                        {{ ucfirst($p->urgency) }}
                                    </span>
                                </td>
                                <td>
                                    @php $st = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','completed'=>'info'][$p->status] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $st }}-subtle text-{{ $st }}" style="font-size:10px;">
                                        {{ ucfirst(str_replace('_',' ',$p->status)) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $p->created_at->format('d M Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-light text-muted rounded-circle fs-1"><i class="ri-shopping-cart-line"></i></span></div>
                    <h6 class="text-muted">Belum ada permintaan pengadaan</h6>
                    <a href="{{ route('sarpras.user.pengadaan.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success mt-2">
                        <i class="ri-add-line me-1"></i>Ajukan Pengadaan
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="ri-shopping-cart-line text-success me-2"></i>Pengadaan Saya</h6></div>
            <div class="card-body p-2">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small text-muted">Total Ajuan</span>
                    <strong>{{ $procurements->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small text-muted">Pending</span>
                    <strong>{{ $procurements->where('status','pending')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="small text-muted">Disetujui</span>
                    <strong>{{ $procurements->where('status','approved')->count() }}</strong>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="{{ route('sarpras.user.pengadaan.create', ['userId' => $userId]) }}" class="btn btn-success btn-sm">
                <i class="ri-add-line me-1"></i>Ajukan Pengadaan Baru
            </a>
        </div>
    </div>
</div>
@endsection