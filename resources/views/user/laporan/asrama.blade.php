@extends('layouts.master')

@section('title') Laporan Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Semua Laporan</a> @endslot
        @slot('title') Laporan Asrama @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Asrama</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['total']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Kapasitas</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($summary['kapasitas']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Penghuni Aktif</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($summary['penghuni']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Sisa Kapasitas</div>
                <div class="fs-3 fw-bold text-warning">{{ number_format($summary['kosong']) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.asrama', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Cari Asrama</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama asrama…">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-home-heart-line me-2 text-primary"></i>Daftar Asrama</h5>
            <a href="{{ route('user.laporan.pdf', array_merge(['userId' => $userId, 'type' => 'asrama'], request()->query())) }}"
               target="_blank" class="btn btn-sm btn-danger">
                <i class="ri-file-pdf-2-line me-1"></i>Cetak PDF
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>Nama Asrama</th>
                            <th>Kamar</th>
                            <th>Penghuni</th>
                            <th>Kapasitas</th>
                            <th>Sisa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dormitories as $i => $dorm)
                            @php
                                $sisa = max(0, (int) $dorm->capacity - (int) $dorm->active_residents_count);
                                $penuh = $dorm->capacity > 0 && $dorm->active_residents_count >= $dorm->capacity;
                            @endphp
                            <tr>
                                <td>{{ $dormitories->firstItem() + $i }}</td>
                                <td class="fw-medium">{{ $dorm->name }}</td>
                                <td>{{ number_format($dorm->rooms_count) }}</td>
                                <td>{{ number_format($dorm->active_residents_count) }}</td>
                                <td>{{ number_format($dorm->capacity) }}</td>
                                <td>
                                    <span class="badge bg-{{ $penuh ? 'danger' : 'success' }}-subtle text-{{ $penuh ? 'danger' : 'success' }}">
                                        {{ number_format($sisa) }}
                                    </span>
                                </td>
                                <td>
                                    @if(! $dorm->is_active)
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @elseif($penuh)
                                        <span class="badge bg-warning-subtle text-warning">Penuh</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">Tersedia</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7" title="Tidak ada data asrama"
                                message="Tidak ada asrama yang sesuai filter." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($dormitories->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $dormitories])
            </div>
        @endif
    </div>
@endsection
