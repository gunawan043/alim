@extends('layouts.master')
@section('title') Laporan Kerusakan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
    @slot('title') Laporan Kerusakan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0">Riwayat Laporan Kerusakan</h6>
                <a href="{{ route('sarpras.user.kerusakan.create', ['userId' => $userId]) }}" class="btn btn-sm btn-danger">
                    <i class="ri-error-warning-line me-1"></i>Buat Laporan Baru
                </a>
            </div>
            <div class="card-body p-0">
                @if($reports->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>No. Laporan</th>
                                <th>Aset</th>
                                <th>Tingkat</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $r)
                            <tr>
                                <td><code class="small">{{ $r->report_number }}</code></td>
                                <td class="small">{{ $r->asset?->asset_name ?? '-' }}</td>
                                <td>
                                    @php $lvl = ['ringan'=>'success','sedang'=>'warning','berat'=>'danger'][$r->damage_level] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $lvl }}-subtle text-{{ $lvl }}" style="font-size:10px;">
                                        {{ ucfirst($r->damage_level) }}
                                    </span>
                                </td>
                                <td class="small">{{ Str::limit($r->description, 50) }}</td>
                                <td>
                                    @php $st = ['pending'=>'warning','investigated'=>'info','resolved'=>'success','rejected'=>'secondary'][$r->status] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $st }}-subtle text-{{ $st }}" style="font-size:10px;">
                                        {{ ucfirst(str_replace('_',' ',$r->status)) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $r->created_at->format('d M Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-light text-muted rounded-circle fs-1"><i class="ri-error-warning-line"></i></span></div>
                    <h6 class="text-muted">Belum ada laporan kerusakan</h6>
                    <a href="{{ route('sarpras.user.kerusakan.create', ['userId' => $userId]) }}" class="btn btn-sm btn-danger mt-2">
                        <i class="ri-add-line me-1"></i>Buat Laporan
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="ri-error-warning-line text-danger me-2"></i>Aset Rusak</h6></div>
            <div class="card-body p-2">
                @if($damagedAssets->count() > 0)
                    <p class="small text-muted mb-2">{{ $damagedAssets->count() }} aset dalam kondisi rusak:</p>
                    @foreach($damagedAssets->take(5) as $a)
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="small">{{ Str::limit($a->asset_name, 20) }}</span>
                        <span class="badge bg-warning-subtle text-warning" style="font-size:9px;">
                            {{ ucfirst(str_replace('_',' ',$a->condition)) }}
                        </span>
                    </div>
                    @endforeach
                    @if($damagedAssets->count() > 5)
                    <div class="small text-muted mt-1">+{{ $damagedAssets->count() - 5 }} aset lain</div>
                    @endif
                @else
                    <p class="small text-muted">Semua aset dalam kondisi baik.</p>
                @endif
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="{{ route('sarpras.user.kerusakan.create', ['userId' => $userId]) }}" class="btn btn-danger btn-sm">
                <i class="ri-error-warning-line me-1"></i>Buat Laporan Kerusakan
            </a>
        </div>
    </div>
</div>
@endsection