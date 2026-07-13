@extends('layouts.master')

@section('title', 'Detail Kepulangan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Detail Izin & Kepulangan</h4>
                <a href="{{ route('user.calendar.return.index', ['userId' => $userId]) }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Informasi Santri</h5></div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th width="40%">Nama</th><td>{{ $permit->student->nama ?? '-' }}</td></tr>
                        <tr><th>NIS</th><td>{{ $permit->student->nis ?? '-' }}</td></tr>
                        <tr><th>Asrama</th><td>{{ $permit->dormitory->name ?? '-' }}</td></tr>
                        <tr><th>Kamar</th><td>{{ $permit->room->kode ?? $permit->room->nomor ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Rincian Izin</h5></div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th width="40%">Jenis Izin</th><td><code>{{ $permit->permit_type }}</code></td></tr>
                        <tr><th>Tujuan</th><td>{{ $permit->destination ?? '-' }}</td></tr>
                        <tr><th>Keperluan</th><td>{{ $permit->purpose ?? '-' }}</td></tr>
                        <tr><th>Berangkat</th><td>{{ $permit->departure_datetime->format('d M Y H:i') }}</td></tr>
                        <tr><th>Rencana Kembali</th><td>{{ $permit->expected_return_datetime->format('d M Y H:i') }}</td></tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @php
                                    $statusBadge = match($permit->status) {
                                        'returned' => 'success',
                                        'approved' => 'info',
                                        'overdue' => 'danger',
                                        'rejected' => 'secondary',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($permit->status) }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Penjemput</h5></div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th width="35%">Nama</th><td>{{ $permit->companion_name ?? '-' }}</td></tr>
                        <tr><th>Hubungan</th><td>{{ $permit->companion_relation ?? '-' }}</td></tr>
                        <tr><th>Telepon</th><td>{{ $permit->companion_phone ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>

            @if($permit->status === 'approved' || $permit->status === 'overdue')
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Catat Kepulangan</h5></div>
                <div class="card-body">
                    <form action="{{ route('user.calendar.return.mark-returned', ['userId' => $userId, 'id' => $permit->id]) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label">Waktu Kembali <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="actual_return_datetime" class="form-control"
                                   value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Opsional"></textarea>
                        </div>
                        <button class="btn btn-success w-100">
                            <i class="ri-check-line me-1"></i> Tandai Sudah Kembali
                        </button>
                    </form>
                </div>
            </div>
            @elseif($permit->status === 'returned')
            <div class="card bg-success-subtle">
                <div class="card-body text-center">
                    <i class="ri-check-double-line" style="font-size:3rem;color:#0ab39c"></i>
                    <h5 class="mt-2">Sudah Kembali</h5>
                    <p class="text-muted">{{ $permit->actual_return_datetime?->format('d M Y H:i') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection