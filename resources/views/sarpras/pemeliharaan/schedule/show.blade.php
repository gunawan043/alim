@extends('layouts.master')
@section('title') Detail Jadwal Pemeliharaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}">Jadwal Pemeliharaan</a> @endslot
    @slot('title') Detail @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- STATUS BADGE --}}
@php
    $isOverdue = $schedule->is_active && $schedule->next_maintenance_date && $schedule->next_maintenance_date->isPast();
    $isSoon = $schedule->is_active && $schedule->next_maintenance_date && $schedule->next_maintenance_date->diffInDays(now()) <= 7;
@endphp
<div class="d-flex gap-2 flex-wrap mb-3">
    @if($isOverdue)
        <span class="badge bg-danger fs-6"><i class="ri-error-warning-line me-1"></i> OVERDUE — Jadwal sudah terlewat!</span>
    @elseif($isSoon)
        <span class="badge bg-warning fs-6"><i class="ri-time-line me-1"></i> MENDEKATI — Jadwal dalam {{ $schedule->next_maintenance_date->diffInDays(now()) }} hari</span>
    @else
        <span class="badge bg-success fs-6"><i class="ri-check-line me-1"></i> AKTIF</span>
    @endif
    @if(!$schedule->is_active)
        <span class="badge bg-secondary fs-6">NONAKTIF</span>
    @endif
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detail Jadwal Pemeliharaan</h5>
                <div class="d-flex gap-1 flex-wrap">
                    @if($schedule->is_active)
                        <a href="{{ route('sarpras.pemeliharaan.schedule.edit', ['id' => $schedule->id]) }}" class="btn btn-warning btn-sm">
                            <i class="ri-pencil-line me-1"></i> Edit
                        </a>
                    @endif
                    <form action="{{ route('sarpras.pemeliharaan.schedule.destroy', ['id' => $schedule->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="ri-delete-bin-line"></i></button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted fw-medium" style="width:200px">Jenis Perawatan</td>
                                <td><strong>{{ $schedule->maintenance_type }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-medium">Target</td>
                                <td>
                                    @if($schedule->asset)
                                        <span class="badge bg-primary-subtle text-primary me-1">Aset</span>
                                        <a href="{{ route('sarpras.aset.show', ['id' => $schedule->asset_id]) }}" class="fw-medium">{{ $schedule->asset->asset_name }}</a>
                                        @if($schedule->asset->asset_code)
                                            <br><code class="small text-muted">{{ $schedule->asset->asset_code }}</code>
                                        @endif
                                    @elseif($schedule->room)
                                        <span class="badge bg-info-subtle text-info me-1">Ruang</span>
                                        {{ $schedule->room->room_name }}
                                        @if($schedule->room->building)
                                            <small class="text-muted"> — {{ $schedule->room->building->building_name }}</small>
                                        @endif
                                    @elseif($schedule->building)
                                        <span class="badge bg-secondary-subtle text-secondary me-1">Gedung</span>
                                        {{ $schedule->building->building_name }}
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Frekuensi</td><td>{{ ucfirst(str_replace('_',' ', $schedule->frequency)) }}</td></tr>
                            <tr><td class="text-muted fw-medium">Tanggal Terakhir</td><td>{{ $schedule->last_maintenance_date?->format('d/m/Y') ?? '<span class="text-muted">Belum pernah</span>' }}</td></tr>
                            <tr>
                                <td class="text-muted fw-medium">Jadwal Berikutnya</td>
                                <td>
                                    <strong class="{{ $isOverdue ? 'text-danger' : '' }}">{{ $schedule->next_maintenance_date?->format('d/m/Y') }}</strong>
                                    @if($schedule->next_maintenance_date)
                                        <span class="text-muted small">({{ $schedule->next_maintenance_date->diffForHumans() }})</span>
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Penanggung Jawab</td><td>{{ $schedule->responsibleUser?->name ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Vendor / Penyedia Jasa</td><td>{{ $schedule->vendor_name ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Estimasi Biaya</td>
                                <td>{{ $schedule->estimated_cost ? 'Rp ' . number_format($schedule->estimated_cost, 0, ',', '.') : '-' }}</td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Pengingat</td><td>{{ $schedule->reminder_days_before ? $schedule->reminder_days_before . ' hari sebelumnya' : '-' }}</td></tr>
                            @if($schedule->notes)
                            <tr><td class="text-muted fw-medium">Catatan</td><td>{{ $schedule->notes }}</td></tr>
                            @endif
                            <tr><td class="text-muted fw-medium">Aktif</td>
                                <td>
                                    @if($schedule->is_active)
                                        <span class="badge bg-success-subtle text-success">Ya</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Tidak</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TOMBOL CATAT PERAWATAN --}}
        <div class="card mt-3 border-primary">
            <div class="card-header bg-primary-subtle">
                <h5 class="card-title mb-0 text-primary"><i class="ri-tools-line me-1"></i> Catat Perawatan</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Catat perawatan yang telah dilakukan agar jadwal berikutnya otomatis diperbarui.</p>
                <a href="{{ route('sarpras.pemeliharaan.log.create') }}?schedule_id={{ $schedule->id }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i> Catat Perawatan Baru
                </a>
            </div>
        </div>
    </div>

    {{-- SIDEBAR: RIWAYAT PERAWATAN --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-history-line me-1"></i> Riwayat Perawatan</h5></div>
            <div class="card-body p-0">
                @if($schedule->logs && $schedule->logs->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($schedule->logs->sortByDesc('maintenance_date') as $log)
                        <a href="{{ route('sarpras.pemeliharaan.log.show', ['id' => $log->id]) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="fw-medium">{{ $log->maintenance_type }}</small>
                                    <br><small class="text-muted">{{ $log->maintenance_date?->format('d/m/Y') }}</small>
                                </div>
                                <div class="text-end">
                                    @if($log->actual_cost)
                                        <small class="text-muted">Rp {{ number_format($log->actual_cost, 0, ',', '.') }}</small><br>
                                    @endif
                                    <span class="badge bg-{{ $log->condition_after === 'baik' ? 'success' : 'warning' }}-subtle small">
                                        {{ ucfirst(str_replace('_',' ', $log->condition_after ?? '-')) }}
                                    </span>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="ri-inbox-archive-line fs-2 text-muted"></i>
                        <p class="text-muted small mb-0">Belum ada riwayat perawatan.</p>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent border-top">
                <a href="{{ route('sarpras.pemeliharaan.log.index') }}?schedule_id={{ $schedule->id }}" class="btn btn-outline-secondary btn-sm w-100">
                    Lihat Semua Riwayat
                </a>
            </div>
        </div>
    </div>
</div>
@endsection