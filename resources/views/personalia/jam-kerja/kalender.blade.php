@extends('layouts.master')
@section('title') Kalender Jam Kerja @endsection
@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1px solid #c4b5fd;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#100c1f 0%,#150e22 100%);border-color:#5b21b6}
@media print{.no-print{display:none!important}}
.badge-status{font-size:.78rem;padding:.35em .7em}
</style>
@endpush

@section('content')
@php
$userId = request()->route('userId') ?? auth()->id();
$currentUser = auth()->user();
$isAdmin = $currentUser->hasAnyRole(['Personalia','Super Admin','Admin Tata Usaha']);
$bulan = request('bulan', now()->month);
$tahun = request('tahun', now()->year);
$monthName = \Carbon\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');
$jamKerjaAktif = \App\Models\JamKerja::where('is_active',true)->get();
$shifts = \App\Models\Shift::where('is_active',true)->get();
@endphp

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#8b5cf618;color:#7c3aed;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-calendar-todo-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Kalender Jam Kerja</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Pemandangan kalender jam kerja dan shift GTK</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.jam-kerja.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Jam Kerja</a>
        @if($isAdmin)
        <a href="{{ route('user.jam-kerja.create', $userId) }}" class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i>Tambah</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-time-line text-primary"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Jam Kerja Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $jamKerjaAktif->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-team-line text-info"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Shift Aktif</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $shifts->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Hari Kerja</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ now()->daysInMonth }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-moon-line text-warning"></i></span></div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;letter-spacing:.5px">Total Jam/Bulan</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $jamKerjaAktif->count() > 0 ? round($jamKerjaAktif->first()->diffInHours(\Carbon\Carbon::parse($jamKerjaAktif->first()->jam_pulang)->addDays(now()->daysInMonth)) ?? 0) : 0 }} j</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card no-print mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-0" style="font-size:.78rem">Bulan</label>
                <select name="bulan" class="form-select form-select-sm">
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $bulan==$m?'selected':'' }}>{{ \Carbon\Carbon::create(2026,$m,1)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" style="font-size:.78rem">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    @foreach(range(now()->year-2, now()->year+1) as $y)
                    <option value="{{ $y }}" {{ $tahun==$y?'selected':'' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0" style="font-size:.78rem">Jam Kerja</label>
                <select name="jam_kerja_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($jamKerjaAktif as $jk)
                    <option value="{{ $jk->id }}">{{ $jk->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-line me-1"></i>Tampilkan</button>
                    <button onclick="window.print()" class="btn btn-light btn-sm"><i class="ri-printer-line"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ri-calendar-check-line text-primary me-1"></i> Kalender {{ $monthName }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered text-center mb-0" style="table-layout:fixed">
                <thead>
                    <tr class="bg-light">
                        <th class="text-muted" style="width:14%">Min</th>
                        <th style="width:14%">Sen</th>
                        <th style="width:14%">Sel</th>
                        <th style="width:14%">Rab</th>
                        <th style="width:14%">Kam</th>
                        <th style="width:14%">Jum</th>
                        <th class="text-muted" style="width:14%">Sab</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $firstDay = \Carbon\Carbon::create($tahun, $bulan, 1);
                    $daysInMonth = $firstDay->daysInMonth;
                    $startDow = $firstDay->dayOfWeek;
                    $totalCells = ceil(($daysInMonth + $startDow) / 7) * 7;
                    $day = 1;
                    @endphp
                    @for($cell=0;$cell<$totalCells;$cell+=7)
                    <tr>
                        @for($dow=0;$dow<7;$dow++)
                        @php $idx = $cell + $dow; $dayNum = $idx - $startDow + 1; @endphp
                        @if($idx < $startDow || $dayNum > $daysInMonth)
                        <td class="p-2 bg-light-subtle" style="opacity:.4">
                            <span class="d-block text-muted" style="font-size:.75rem">&nbsp;</span>
                        </td>
                        @else
                        @php $isWeekend = $dow==0 || $dow==6; @endphp
                        <td class="p-2 {{ $isWeekend ? 'bg-danger-subtle' : '' }}" style="min-width:70px;vertical-align:top">
                            <span class="d-block fw-semibold mb-1 {{ $isWeekend ? 'text-danger' : '' }}" style="font-size:.8rem">{{ $dayNum }}</span>
                            @if(!$isWeekend && $jamKerjaAktif->count() > 0)
                            <div class="small mb-1" style="font-size:.65rem;color:#7c3aed">
                                <i class="ri-arrow-up-line"></i> {{ $jamKerjaAktif->first()->jam_masuk }}<br>
                                <i class="ri-arrow-down-line"></i> {{ $jamKerjaAktif->first()->jam_pulang }}
                            </div>
                            @elseif($isWeekend)
                            <span class="badge bg-danger-subtle text-danger badge-status" style="font-size:.6rem">Libur</span>
                            @endif
                        </td>
                        @endif
                        @endfor
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        <div class="row g-3 p-3 border-top">
            <div class="col-auto">
                <span class="badge bg-light text-dark"><i class="ri-time-line me-1"></i> Jam Kerja Aktif</span>
            </div>
            <div class="col-auto">
                <span class="badge bg-danger-subtle text-danger"><i class="ri-calendar-close-line me-1"></i> Akhir pekan (Libur)</span>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header border-bottom-dashed">
        <h5 class="card-title mb-0"><i class="ri-list-check-2 text-primary me-1"></i> Ringkasan {{ $monthName }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Hari Kerja</th>
                        <th>Total Jam</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jamKerjaAktif as $jk)
                    @php $hariKerja = $jk->hari_kerja ?? 5; $totalJam = $jk->diffInHours(\Carbon\Carbon::parse($jk->jam_pulang)) * $daysInMonth / $hariKerja; @endphp
                    <tr>
                        <td class="fw-semibold">{{ $jk->nama }}</td>
                        <td><span class="badge bg-light text-dark">{{ $jk->jam_masuk }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ $jk->jam_pulang }}</span></td>
                        <td><span class="badge bg-success-subtle text-success badge-status">{{ $hariKerja }} hari/minggu</span></td>
                        <td><span class="fw-semibold">{{ round($totalJam) }} jam</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Tidak ada jam kerja aktif.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection