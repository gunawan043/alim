@extends('layouts.master')
@section('title') Detail Booking Ruangan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.booking.index') }}">Booking</a> @endslot
    @slot('title') Detail @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- STATUS BADGE --}}
@php
    $statusConfig = [
        'pending'   => ['warning', 'Menunggu Persetujuan'],
        'approved'  => ['success', 'Disetujui'],
        'rejected'  => ['danger',  'Ditolak'],
        'cancelled' => ['secondary','Dibatalkan'],
        'completed' => ['info',    'Selesai'],
    ];
    $sc = $statusConfig[$booking->status] ?? ['secondary','-'];
@endphp
<div class="alert alert-{{ $sc[0] }} d-flex align-items-center gap-2 py-2">
    <i class="ri-information-line fs-5"></i>
    <strong>Status:</strong> {{ $sc[1] }}
    @if($booking->status === 'rejected' && $booking->rejection_reason)
        <span class="text-muted small">— Alasan: {{ $booking->rejection_reason }}</span>
    @endif
</div>

<div class="row">
    {{-- MAIN CONTENT --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detail Booking Ruangan</h5>
                <div class="d-flex gap-1 flex-wrap">
                    @if($booking->status === 'pending')
                        <a href="{{ route('sarpras.booking.approve', ['id' => $booking->id]) }}" class="btn btn-success btn-sm" onclick="return confirm('Setuju dengan booking ini?')">
                            <i class="ri-check-line me-1"></i> Approve
                        </a>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ri-close-line me-1"></i> Tolak
                        </button>
                    @endif
                    @if($booking->status === 'approved')
                        <a href="{{ route('sarpras.booking.complete', ['id' => $booking->id]) }}" class="btn btn-primary btn-sm" onclick="return confirm('Tandai booking ini selesai?')">
                            <i class="ri-check-double-line me-1"></i> Selesai
                        </a>
                    @endif
                    @if($booking->booked_by === auth()->id() && in_array($booking->status, ['pending','approved']))
                        <a href="{{ route('sarpras.booking.cancel', ['id' => $booking->id]) }}" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Batalkan booking ini?')">
                            <i class="ri-close-circle-line me-1"></i> Batalkan
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted fw-medium" style="width:200px">Ruang</td>
                                <td>
                                    @if($booking->room)
                                        <a href="{{ route('sarpras.ruang.show', ['id' => $booking->room_id]) }}" class="fw-medium">{{ $booking->room->room_name }}</a>
                                        @if($booking->room->building)
                                            <br><small class="text-muted">{{ $booking->room->building->building_name }}</small>
                                        @endif
                                    @else — @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Nama Kegiatan</td><td>{{ $booking->event_name ?? '-' }}</td></tr>
                            <tr><td class="text-muted fw-medium">Tujuan</td><td>{{ $booking->purpose }}</td></tr>
                            <tr><td class="text-muted fw-medium">Tanggal</td><td>{{ $booking->booking_date?->format('d/m/Y') }}</td></tr>
                            <tr>
                                <td class="text-muted fw-medium">Waktu</td>
                                <td>
                                    {{ substr($booking->start_time, 0, 5) }} — {{ substr($booking->end_time, 0, 5) }}
                                    @if($booking->setup_time)
                                        <span class="text-muted small"> (persiapan: {{ substr($booking->setup_time, 0, 5) }})</span>
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Jumlah Peserta</td><td>{{ $booking->participants_count ?? '-' }}</td></tr>
                            <tr>
                                <td class="text-muted fw-medium">Requester</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{ $booking->user?->name ?? '-' }}</span>
                                        @if($booking->user?->email)
                                            <small class="text-muted">({{ $booking->user->email }})</small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr><td class="text-muted fw-medium">Unit Kerja</td><td>{{ $booking->workUnit?->name ?? '-' }}</td></tr>
                            @if($booking->approved_at)
                            <tr>
                                <td class="text-muted fw-medium">Disetujui</td>
                                <td>{{ $booking->approver?->name ?? '-' }} &mdash; {{ $booking->approved_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($booking->actual_start_time)
                            <tr><td class="text-muted fw-medium">Mulai Aktual</td><td>{{ substr($booking->actual_start_time, 0, 5) }}</td></tr>
                            @endif
                            @if($booking->actual_end_time)
                            <tr><td class="text-muted fw-medium">Selesai Aktual</td><td>{{ substr($booking->actual_end_time, 0, 5) }}</td></tr>
                            @endif
                            @if($booking->condition_after)
                            <tr><td class="text-muted fw-medium">Kondisi Ruang Sesudah</td><td>{{ $booking->condition_after }}</td></tr>
                            @endif
                            @if($booking->notes)
                            <tr><td class="text-muted fw-medium">Catatan</td><td>{{ $booking->notes }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- FORM PENOLAKAN --}}
        @if($booking->status === 'pending')
        <div class="modal fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tolak Booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('sarpras.booking.reject', ['id' => $booking->id]) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger"><i class="ri-close-line me-1"></i> Tolak Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- SIDEBAR INFO RUANG --}}
    <div class="col-lg-4">
        @if($booking->room)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Info Ruang</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">Nama</td><td class="fw-medium">{{ $booking->room->room_name }}</td></tr>
                    <tr><td class="text-muted small">Gedung</td><td>{{ $booking->room->building?->building_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Kapasitas</td><td>{{ $booking->room->capacity ? $booking->room->capacity . ' orang' : '-' }}</td></tr>
                    <tr><td class="text-muted small">Lantai</td><td>{{ $booking->room->floor ?? '-' }}</td></tr>
                    <tr><td class="text-muted small">Fasilitas</td><td>{{ $booking->room->facilities ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="card-footer bg-transparent border-top">
                <a href="{{ route('sarpras.ruang.show', ['id' => $booking->room_id]) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="ri-eye-line me-1"></i> Lihat Detail Ruang
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection