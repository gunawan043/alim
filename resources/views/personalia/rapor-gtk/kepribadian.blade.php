{{-- Rapor GTK: Penilaian Kepribadian --}}
@extends('layouts.master')
@section('title') Penilaian Kepribadian @endsection

@section('css')
    @include('shared._styles')
    <style>
        @media print { .no-print { display: none !important; } }
        .badge-status { font-size: .78rem; padding: .35em .7em; }
    </style>
@endsection

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Rapor GTK @endslot
    @slot('li_2') <a href="{{ route('user.rapor-gtk.tahunan', $userId) }}">Rekap Tahunan</a> @endslot
    @slot('title') Penilaian Kepribadian GTK @endslot
@endcomponent

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2">
                            <i class="ri-team-line text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total GTK</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $totalGtk ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-3 fs-2">
                            <i class="ri-checkbox-circle-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Rata-rata Sopan Santun</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $rataSopanSantun ?? '0' }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-3 fs-2">
                            <i class="ri-star-line text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Rata-rata Tanggung Jawab</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $rataTanggungJawab ?? '0' }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-3 fs-2">
                            <i class="ri-error-warning-line text-danger"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Skor Kepribadian</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $skorKepribadian ?? '0' }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar rounded-2 border p-3 mb-3 no-print">
    <form method="GET" action="{{ route('user.rapor-gtk.kepribadian', $userId) }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Nama GTK</label>
            <select name="gtk_id" class="form-select form-select-sm">
                <option value="">Semua GTK</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:.8rem">Periode</label>
            <select name="periode" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="smester">Smester</option>
                <option value="tahunan">Tahunan</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
            <a href="{{ route('user.rapor-gtk.kepribadian', $userId) }}" class="btn btn-light btn-sm"><i class="ri-reset-right-line me-1"></i>Reset</a>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-end">
            <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-table-2 text-primary me-1"></i> Daftar Penilaian Kepribadian GTK</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width:48px">No</th>
                    <th >Nama GTK</th>
                    <th class="text-center">Sopan Santun</th>
                    <th class="text-center">Tanggung Jawab</th>
                    <th class="text-center">Integritas</th>
                    <th class="text-center">Skor</th>
                    <th class="text-center">Predikat</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kepribadianList ?? [] as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem;width:28px;height:28px">
                                    {{ strtoupper(substr($item['nama'] ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-medium">{{ $item['nama'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="text-center">{{ $item['sopan_santun'] ?? '-' }}</td>
                        <td class="text-center">{{ $item['tanggung_jawab'] ?? '-' }}</td>
                        <td class="text-center">{{ $item['integritas'] ?? '-' }}</td>
                        <td class="text-center fw-bold">{{ $item['skor'] ?? '-' }}</td>
                        <td class="text-center">
                            @php $predikat = $item['predikat'] ?? 'C'; @endphp
                            @if($predikat == 'A')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">A</span>
                            @elseif($predikat == 'B')
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">B</span>
                            @elseif($predikat == 'C')
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">C</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">D</span>
                            @endif
                        </td>
                        <td class="text-center no-print">
                            <a href="{{ route('user.rapor-gtk.kepribadian', $userId) }}" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        </td>
                    </tr>
                @empty
                    <x-empty-state colspan="8"
                        title="Belum ada data"
                        message="Data penilaian akan muncul di sini." />
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($kepribadianList) && method_exists($kepribadianList, 'hasPages') && $kepribadianList->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center no-print">
            <span class="text-muted small">Menampilkan {{ $kepribadianList->firstItem() ?? 0 }} - {{ $kepribadianList->lastItem() ?? 0 }} dari {{ $kepribadianList->total() }} data</span>
            <nav>{{ $kepribadianList->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>
@endsection
