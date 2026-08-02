@extends('layouts.master')
@section('title') Laporan Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Laporan @endslot
    @endcomponent

    <div class="row g-3">
        {{-- Presensi --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-success-subtle">
                                <i class="ri-calendar-check-line fs-24 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-muted mb-0" style="font-size:12px;">Presensi</p>
                            <h6 class="fw-bold mb-0">Rekap Kehadiran</h6>
                        </div>
                    </div>
                    <hr class="my-3">
                    <form action="{{ route('user.asrama.reports.attendance', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" method="GET" id="form-attendance">
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="month" class="form-select form-select-sm">
                                    @for($m=1;$m<=12;$m++)
                                        <option value="{{ $m }}" {{ $period['month']===$m ? 'selected':'' }}>{{ \Carbon\Carbon::createFromDate($period['year'],$m,1)->locale('id')->isoFormat('MMM') }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="year" class="form-select form-select-sm">
                                    @for($y=2024;$y<=date('Y')+2;$y++)
                                        <option value="{{ $y }}" {{ $period['year']===$y ? 'selected':'' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success w-100 mt-2">
                            <i class="ri-file-download-line"></i> Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Perizinan --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-primary-subtle">
                                <i class="ri-document-line fs-24 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-muted mb-0" style="font-size:12px;">Perizinan</p>
                            <h6 class="fw-bold mb-0">Laporan Izin</h6>
                        </div>
                    </div>
                    <hr class="my-3">
                    <form action="{{ route('user.asrama.reports.permits', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" method="GET">
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="month" class="form-select form-select-sm">
                                    @for($m=1;$m<=12;$m++)
                                        <option value="{{ $m }}" {{ $period['month']===$m ? 'selected':'' }}>{{ \Carbon\Carbon::createFromDate($period['year'],$m,1)->locale('id')->isoFormat('MMM') }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="year" class="form-select form-select-sm">
                                    @for($y=2024;$y<=date('Y')+2;$y++)
                                        <option value="{{ $y }}" {{ $period['year']===$y ? 'selected':'' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100 mt-2">
                            <i class="ri-file-download-line"></i> Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Pelanggaran --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-danger-subtle">
                                <i class="ri-error-warning-line fs-24 text-danger"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-muted mb-0" style="font-size:12px;">Pelanggaran</p>
                            <h6 class="fw-bold mb-0">Laporan Pelanggaran</h6>
                        </div>
                    </div>
                    <hr class="my-3">
                    <form action="{{ route('user.asrama.reports.violations', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" method="GET">
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="month" class="form-select form-select-sm">
                                    @for($m=1;$m<=12;$m++)
                                        <option value="{{ $m }}" {{ $period['month']===$m ? 'selected':'' }}>{{ \Carbon\Carbon::createFromDate($period['year'],$m,1)->locale('id')->isoFormat('MMM') }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="year" class="form-select form-select-sm">
                                    @for($y=2024;$y<=date('Y')+2;$y++)
                                        <option value="{{ $y }}" {{ $period['year']===$y ? 'selected':'' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger w-100 mt-2">
                            <i class="ri-file-download-line"></i> Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Penghargaan --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-md rounded-circle bg-warning-subtle">
                                <i class="ri-medal-line fs-24 text-warning"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-muted mb-0" style="font-size:12px;">Penghargaan</p>
                            <h6 class="fw-bold mb-0">Laporan Penghargaan</h6>
                        </div>
                    </div>
                    <hr class="my-3">
                    <form action="{{ route('user.asrama.reports.rewards', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" method="GET">
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="month" class="form-select form-select-sm">
                                    @for($m=1;$m<=12;$m++)
                                        <option value="{{ $m }}" {{ $period['month']===$m ? 'selected':'' }}>{{ \Carbon\Carbon::createFromDate($period['year'],$m,1)->locale('id')->isoFormat('MMM') }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="year" class="form-select form-select-sm">
                                    @for($y=2024;$y<=date('Y')+2;$y++)
                                        <option value="{{ $y }}" {{ $period['year']===$y ? 'selected':'' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-warning w-100 mt-2">
                            <i class="ri-file-download-line"></i> Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        {{-- Inventaris --}}
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="ri-tools-line me-1"></i> Inventaris Kamar</h6>
                    <p class="text-muted small mb-3">Ringkasan kondisi inventaris seluruh kamar</p>
                    <a href="{{ route('user.asrama.reports.inventories', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-sm btn-outline-secondary w-100">
                        Lihat Laporan
                    </a>
                </div>
            </div>
        </div>

        {{-- Kebersihan --}}
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="ri-broom-line me-1"></i> Kebersihan</h6>
                    <p class="text-muted small mb-3">Ringkasan evaluasi kebersihan</p>
                    <a href="{{ route('user.asrama.reports.sanitation', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-sm btn-outline-secondary w-100">
                        Lihat Laporan
                    </a>
                </div>
            </div>
        </div>

        {{-- Penghuni --}}
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="ri-building-line me-1"></i> Penghuni & Kapasitas</h6>
                    <p class="text-muted small mb-3">Rekap jumlah penghuni per kamar</p>
                    <a href="{{ route('user.asrama.reports.occupancy', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-sm btn-outline-secondary w-100">
                        Lihat Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
