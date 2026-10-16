{{-- Rapor GTK: Rekap Tahunan — pola UI mengikuti gtk/index.blade.php --}}
@extends('layouts.master')
@section('title') Rekap Tahunan GTK @endsection

@section('css')
    @include('shared._styles')
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
@endsection

@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Rapor GTK @endslot
        @slot('li_2') <a href="{{ route('user.rapor-gtk.akademik', $userId) }}">Akademik</a> @endslot
        @slot('title') Rekap Tahunan GTK @endslot
    @endcomponent

    {{-- Ringkasan --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-team-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total GTK</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $totalGtk ?? 0 }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>GTK dalam cakupan satuan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Rata-rata Keseluruhan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $rataKeseluruhan ?? '0.00' }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Akumulasi seluruh komponen</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-star-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Sangat Baik</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $sangatBaik ?? 0 }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Predikat A</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-error-warning-line text-danger"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Perlu Perbaikan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $perluPerbaikan ?? 0 }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Predikat di bawah C</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0">Rekap Penilaian GTK Tahun Ajaran {{ $tahunAjaran ? $tahunAjaran.'/'.($tahunAjaran + 1) : date('Y').'/'.(date('Y') + 1) }}</h5>
                    <p class="text-muted mb-0 small">Ringkasan evaluasi kinerja GTK selama satu tahun ajaran.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap no-print">
                    <a href="{{ route('user.rapor-gtk.akademik', $userId) }}" class="btn btn-outline-secondary btn-sm"><i class="ri-book-open-line me-1"></i>Akademik</a>
                    <a href="{{ route('user.rapor-gtk.disiplin', $userId) }}" class="btn btn-outline-secondary btn-sm"><i class="ri-shield-check-line me-1"></i>Disiplin</a>
                    <a href="{{ route('user.rapor-gtk.kepribadian', $userId) }}" class="btn btn-outline-secondary btn-sm"><i class="ri-user-heart-line me-1"></i>Kepribadian</a>
                    <a href="{{ route('user.rapor-gtk.administrasi', $userId) }}" class="btn btn-outline-secondary btn-sm"><i class="ri-file-list-3-line me-1"></i>Administrasi</a>
                    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="ri-printer-line me-1"></i>Cetak</button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('user.rapor-gtk.tahunan', $userId) }}" class="row g-2 align-items-end mb-3 no-print">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label small text-muted mb-1">Nama GTK</label>
                    <select name="gtk_id" class="form-select form-select-sm">
                        <option value="">Semua GTK</option>
                        @foreach(($gtks ?? collect()) as $gtk)
                            <option value="{{ $gtk->id }}" {{ request('gtk_id') == $gtk->id ? 'selected' : '' }}>
                                {{ $gtk->user?->name ?? 'GTK' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                    <select name="tahun_ajaran" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach(($academicYears ?? collect()) as $y)
                            <option value="{{ $y->name }}" {{ request('tahun_ajaran') === $y->name ? 'selected' : '' }}>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-5 col-md-12">
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filter</button>
                        <a href="{{ route('user.rapor-gtk.tahunan', $userId) }}" class="btn btn-light btn-sm"><i class="ri-refresh-line me-1"></i>Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-freeze mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px">No</th>
                            <th>Nama GTK</th>
                            <th class="text-center" style="width:110px">Akademik</th>
                            <th class="text-center" style="width:110px">Disiplin</th>
                            <th class="text-center" style="width:120px">Kepribadian</th>
                            <th class="text-center" style="width:120px">Administrasi</th>
                            <th class="text-center" style="width:110px">Rata-rata</th>
                            <th class="text-center" style="width:110px">Predikat</th>
                            <th class="text-center no-print" style="width:80px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tahunanList ?? [] as $item)
                            @php
                                $predikat = strtoupper((string) ($item['predikat'] ?? 'C'));
                                $predClass = match ($predikat) {
                                    'A' => 'bg-success-subtle text-success',
                                    'B' => 'bg-primary-subtle text-primary',
                                    'C' => 'bg-warning-subtle text-warning',
                                    default => 'bg-danger-subtle text-danger',
                                };
                                $words = preg_split('/\s+/', trim((string) ($item['nama'] ?? ''))) ?: [];
                                $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1).(count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : ''));
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle" style="font-size:.65rem;">{{ $initials }}</span>
                                        </div>
                                        <span class="fw-semibold">{{ $item['nama'] ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="text-center">{{ $item['akademik'] ?? '-' }}</td>
                                <td class="text-center">{{ $item['disiplin'] ?? '-' }}</td>
                                <td class="text-center">{{ $item['kepribadian'] ?? '-' }}</td>
                                <td class="text-center">{{ $item['administrasi'] ?? '-' }}</td>
                                <td class="text-center fw-semibold">{{ $item['rata_rata'] ?? '-' }}</td>
                                <td class="text-center"><span class="badge {{ $predClass }}">{{ $predikat }}</span></td>
                                <td class="text-center no-print">
                                    <a href="{{ route('user.rapor-gtk.tahunan', $userId) }}" class="btn btn-sm btn-soft-primary" title="Lihat"><i class="ri-eye-line"></i></a>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="9"
                                title="Belum ada data rekap"
                                message="Rekap tahunan GTK akan muncul setelah penilaian komponen tersedia." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($tahunanList) && method_exists($tahunanList, 'hasPages'))
                @include('shared._pagination', ['paginator' => $tahunanList])
            @endif
        </div>
    </div>
@endsection
