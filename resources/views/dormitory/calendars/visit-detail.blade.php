@extends('layouts.master')

@section('title', 'Detail Kunjungan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Detail Kunjungan</h4>
                <a href="{{ route('user.calendar.visit.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Informasi Pengunjung</h5></div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th width="40%">Nama</th><td>{{ $visit->visitor_name }}</td></tr>
                        <tr><th>NIK/KTP</th><td>{{ $visit->visitor_id_number ?? '-' }}</td></tr>
                        <tr><th>Telepon</th><td>{{ $visit->visitor_phone ?? '-' }}</td></tr>
                        <tr><th>Hubungan</th><td><code>{{ $visit->visitor_relationship }}</code></td></tr>
                        <tr><th>Keperluan</th><td><code>{{ $visit->purpose }}</code></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Santri & Lokasi</h5></div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th width="40%">Santri</th><td>{{ $visit->student->nama ?? '-' }}</td></tr>
                        <tr><th>NIS</th><td>{{ $visit->student->nis ?? '-' }}</td></tr>
                        <tr><th>Asrama</th><td>{{ $visit->dormitory->name ?? '-' }}</td></tr>
                        <tr><th>Kamar</th><td>{{ $visit->room->kode ?? $visit->room->nomor ?? '-' }}</td></tr>
                        <tr><th>Rencana Datang</th><td>{{ $visit->expected_arrival_datetime->format('d M Y H:i') }}</td></tr>
                        <tr><th>Durasi</th><td>{{ $visit->expected_meet_duration_minutes ?? 60 }} menit</td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Aksi Check-in/out</h5></div>
                <div class="card-body">
                    @if($visit->status === 'approved' || $visit->status === 'pending')
                        <form action="{{ route('user.calendar.visit.check-in', ['userId' => $userId, 'id' => $visit->id]) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-success w-100">
                                <i class="ri-login-box-line me-1"></i> Check-in Sekarang
                            </button>
                        </form>
                    @elseif($visit->status === 'arrived')
                        <p class="text-success">
                            <i class="ri-login-box-line"></i> Check-in: {{ $visit->check_in_at?->format('d M Y H:i') }}
                        </p>
                        <form action="{{ route('user.calendar.visit.check-out', ['userId' => $userId, 'id' => $visit->id]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-warning w-100">
                                <i class="ri-logout-box-line me-1"></i> Check-out Sekarang
                            </button>
                        </form>
                    @elseif($visit->status === 'checked_out')
                        <div class="alert alert-secondary mb-0">
                            <i class="ri-checkbox-circle-line"></i> Selesai<br>
                            Check-in: {{ $visit->check_in_at?->format('d M H:i') }}<br>
                            Check-out: {{ $visit->check_out_at?->format('d M H:i') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection