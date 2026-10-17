@extends('layouts.master')
@section('title') Riwayat Kepulangan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Riwayat Kepulangan – {{ $student->name ?? '-' }} @endslot
    @endcomponent

    {{-- Stat Cards --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat min-h-100">
                <div class="card-body">
                    <div class="float-end text-muted d-none d-lg-block text-end fw-bold">{{ $stats['total_permits'] }}</div>
                    <div>Total Izin</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat min-h-100">
                <div class="card-body">
                    <div class="float-end fw-bold text-success">{{ $stats['on_time_pct'] }}%</div>
                    <div class="text-muted">Kembali Tepat Waktu</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat min-h-100">
                <div class="card-body">
                    <div class="float-end text-muted fw-bold text-warning">{{ $stats['pending'] }}</div>
                    <div class="text-muted">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat min-h-100">
                <div class="card-body">
                    <div class="float-end text-muted fw-bold text-danger">{{ $stats['late_returns'] }}</div>
                    <div class="text-muted">Terlambat</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ri-history-line me-2 text-primary"></i>Riwayat Kepulangan</h5>
        </div>
        <div class="table-responsive">
            <table class="table align-middle table-nowrap table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Tujuan</th>
                        <th>Berangkat</th>
                        <th>Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permits as $permit)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $permit->created_at->format('d M Y') }}</td>
                            <td><span class="badge bg-soft text-{{ $permit->status === 'approved' ? 'success' : ($permit->status === 'overdue' ? 'danger' : 'warning') }}">{{ $permit->permit_type_text }}</span></td>
                            <td>{{ $permit->destination ?? '-' }}</td>
                            <td>{{ $permit->departure_datetime->format('d/M H:i') }}</td>
                            <td>
                                @if($permit->actual_return_datetime)
                                    {{ $permit->actual_return_datetime->format('d/M H:i') }}
                                @else
                                    <span class="text-muted">Belum</span>
                                @endif
                            </td>
                            <td>
                                @if($permit->status === 'returned')
                                    <span class="badge bg-success">Kembali ✓</span>
                                @elseif($permit->status === 'overdue')
                                    <span class="badge bg-danger">Terlambat</span>
                                @elseif($permit->status === 'approved')
                                    <span class="badge bg-warning">Izin Aktif</span>
                                @else
                                    <span class="badge bg-secondary">{{ $permit->status }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('user.asrama.permits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}" class="btn btn-xs btn-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="ri-inbox-line fs-1"></i>
                                <br>Tidak ada riwayat izin kepulangan untuk santri ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
