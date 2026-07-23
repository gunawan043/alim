@extends('layouts.master')
@section('title') Statistik Kepulangan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Statistik Kepulangan @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center">
                <i class="ri-calendar-event-line fs-4 me-3"></i>
                <div>
                    <strong>Statistik bulan ini</strong>
                    — {{ now()->format('F Y') }} di {{ $dormitory->name }}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat min-h-100">
                <div class="card-body">
                    <h3 class="fw-bold">{{ $stats['total_this_month'] }}</h3>
                    <p class="text-muted mb-0">Total Izin Bulan Ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat min-h-100">
                <div class="card-body">
                    <h3 class="fw-bold text-success">{{ $stats['returned_count'] }}</h3>
                    <p class="text-muted mb-0">Sudah Kembali</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat min-h-100">
                <div class="card-body">
                    <h3 class="fw-bold text-warning">{{ $stats['pending_count'] }}</h3>
                    <p class="text-muted mb-0">Menunggu Approval</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat min-h-100">
                <div class="card-body">
                    <h3 class="fw-bold text-danger">{{ $stats['overdue_count'] }}</h3>
                    <p class="text-muted mb-0">Terlambat Kembali</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-pie-chart-line me-2 text-primary"></i>Izin Berdasarkan Jenis</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['by_type'] as $type => $count)
                                <tr>
                                    <td>{{ Str::ucfirst(str_replace('_', ' ', $type)) }}</td>
                                    <td class="text-end"><strong>{{ $count }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center">Tidak ada data bulan ini</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-user-star-line me-2 text-primary"></i>Top 5 Santri Terbanyak Izin</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Santri</th>
                                <th class="text-end">Izin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['top_students'] as $idx => $entry)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $entry->student->name ?? '-' }}</td>
                                    <td class="text-end"><strong>{{ $entry->total }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center">Tidak ada data bulan ini</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
            <i class="ri-arrow-left-line me-1"></i> Kembali ke Kedatangan Santri
        </a>
    </div>
@endsection
