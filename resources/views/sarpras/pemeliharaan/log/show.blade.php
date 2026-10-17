@extends('layouts.master')
@section('title') Detail Riwayat Perawatan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pemeliharaan.log.index') }}">Riwayat Perawatan</a> @endslot
    @slot('title') Detail @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Detail Riwayat Perawatan</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted fw-medium" style="width:200px">Jenis Perawatan</td>
                                <td><strong>{{ $log->maintenance_type }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Target</td>
                                <td>
                                    @if($log->asset)
                                        <span class="badge bg-primary-subtle text-primary me-1">Aset</span>
                                        <a href="{{ route('sarpras.aset.show', ['id' => $log->asset_id]) }}" class="fw-medium">{{ $log->asset->asset_name }}</a>
                                        @if($log->asset->asset_code)
                                            <br><code class="small text-muted">{{ $log->asset->asset_code }}</code>
                                        @endif
                                    @elseif($log->room)
                                        <span class="badge bg-info-subtle text-info me-1">Ruang</span>
                                        {{ $log->room->room_name }}
                                        @if($log->room->building)
                                            <small class="text-muted"> — {{ $log->room->building->building_name }}</small>
                                        @endif
                                    @elseif($log->building)
                                        <span class="badge bg-secondary-subtle text-secondary me-1">Gedung</span>
                                        {{ $log->building->building_name }}
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Tanggal Perawatan</td><td>{{ $log->maintenance_date?->format('d/m/Y') }}</td></tr>
                            <tr><td class="text-muted fw-medium">Petugas</td><td>{{ $log->performer?->name ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Vendor</td><td>{{ $log->vendor_name ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Biaya Aktual</td>
                                <td>{{ $log->actual_cost ? 'Rp ' . number_format($log->actual_cost, 0, ',', '.') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Kondisi Sebelum</td>
                                <td>
                                    @if($log->condition_before)
                                        <span class="badge bg-warning-subtle text-warning">{{ ucfirst(str_replace('_',' ', $log->condition_before)) }}</span>
                                    @else - @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Kondisi Sesudah</td>
                                <td>
                                    @if($log->condition_after)
                                        @php $colors=['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary']; @endphp
                                        <span class="badge bg-{{ $colors[$log->condition_after] ?? 'secondary' }}-subtle text-{{ $colors[$log->condition_after] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_',' ', $log->condition_after)) }}
                                        </span>
                                    @else - @endif
                                </td>
                            </tr>
                            @if($log->work_description)
                            <tr><td class="text-muted fw-medium">Deskripsi Pekerjaan</td><td>{{ $log->work_description }}</td></tr>
                            @endif
                            @if($log->parts_replaced)
                            <tr><td class="text-muted fw-medium">Suku Cadang Diganti</td><td>{{ $log->parts_replaced }}</td></tr>
                            @endif
                            @if($log->next_action_needed)
                            <tr><td class="text-muted fw-medium">Tindakan Berikutnya</td><td>{{ $log->next_action_needed }}</td></tr>
                            @endif
                            @if($log->notes)
                            <tr><td class="text-muted fw-medium">Catatan</td><td>{{ $log->notes }}</td></tr>
                            @endif
                            <tr><td class="text-muted fw-medium">Dicatat Oleh</td><td>{{ $log->creator?->name ?? '-' }} — {{ $log->created_at->format('d/m/Y H:i') }}</td></tr>
                            @if($log->schedule)
                            <tr>
                                <td class="text-muted fw-medium">Jadwal Terlink</td>
                                <td>
                                    <a href="{{ route('sarpras.pemeliharaan.schedule.show', ['id' => $log->schedule_id]) }}" class="badge bg-info-subtle text-info text-decoration-none">
                                        {{ $log->schedule->maintenance_type }} — {{ $log->schedule->next_maintenance_date?->format('d/m/Y') }}
                                    </a>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- SIDEBAR --}}
    <div class="col-lg-4">
        @if($log->asset)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Info Aset</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">Nama</td><td class="fw-medium">{{ $log->asset->asset_name }}</td></tr>
                    <tr><td class="text-muted small">Kode</td><td><code class="small">{{ $log->asset->asset_code ?? '-' }}</code></td></tr>
                    <tr><td class="text-muted small">Kondisi</td><td>{{ ucfirst(str_replace('_',' ', $log->asset->condition)) }}</td></tr>
                    <tr><td class="text-muted small">Ruang</td><td>{{ $log->asset->room?->room_name ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="card-footer bg-transparent border-top">
                <a href="{{ route('sarpras.aset.show', ['id' => $log->asset_id]) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="ri-eye-line me-1"></i> Lihat Detail Aset
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection