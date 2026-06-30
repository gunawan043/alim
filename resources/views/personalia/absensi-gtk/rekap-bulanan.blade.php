{{-- Absensi GTK: Rekap Bulanan --}}
@extends('layouts.master')
@section('title') Rekap Bulanan Absensi GTK @endsection

@push('css')
<style>
.stat-card{transition:all .25s ease;cursor:default}.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.page-header-card{background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border:1px solid #93c5fd;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#0c1929 0%,#1e3a5f 100%);border-color:#3b82f6}
.recap-card{overflow-x:auto}
.recap-table th{position:sticky;top:0;z-index:10;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;background:#f8fafc;white-space:nowrap}
.recap-table td{font-size:.82rem;vertical-align:middle;padding:8px 6px}
.recap-table .day-cell{text-align:center;width:28px;min-width:28px}
.recap-table .gtk-name{text-align:left;min-width:200px;padding-left:12px}
.badge-status{font-size:.72rem;padding:.3em .55rem}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Kehadiran GTK @endslot
    @slot('li_2') Rekap Bulanan @endslot
    @slot('title') Rekap Bulanan Absensi GTK @endslot
@endcomponent

<div class="page-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#3b82f618;color:#2563eb;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-file-chart-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Rekap Bulanan</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">Rekap kehadiran GTK per bulan</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('user.absensi-gtk.harian', $userId) }}" class="btn btn-light btn-sm"><i class="ri-calendar-check-line me-1"></i>Harian</a>
        <a href="{{ route('user.absensi-gtk.izin', $userId) }}" class="btn btn-light btn-sm"><i class="ri-file-list-2-line me-1"></i>Izin/Sakit</a>
        <a href="{{ route('user.absensi-gtk.index', $userId) }}" class="btn btn-outline-primary btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('user.absensi-gtk.rekap-bulanan', $userId) }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-0" style="font-size:.8rem">Bulan</label>
                <select name="bulan" class="form-select form-select-sm">
                    @php $months = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; @endphp
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}" {{ ($bulan ?? '') == $m ? 'selected' : '' }}>{{ $months[$m] }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-0" style="font-size:.8rem">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    @for($y=date('Y')-3;$y<=date('Y')+1;$y++)
                        <option value="{{ $y }}" {{ ($tahun ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary btn-sm"><i class="ri-search-line me-1"></i>Tampilkan</button>
            </div>
        </form>
    </div>
</div>

{{-- Recaps Table --}}
<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-file-chart-line text-primary me-1"></i> Rekap per GTK</h5>
        <span class="text-muted small">{{ \Carbon\Carbon::create(date('Y'), $bulan ?? now()->month, 1)->format('F Y') }}</span>
    </div>
    <div class="table-responsive recap-card">
        <table class="table table-bordered align-middle mb-0 recap-table">
            <thead>
                <tr>
                    <th class="bg-light gtk-name">GTK</th>
                    @php
                        $daysInMonth = (int) \Carbon\Carbon::create($tahun ?? date('Y'), $bulan ?? date('n'), 1)->daysInMonth;
                    @endphp
                    @for($d=1;$d<=$daysInMonth;$d++)
                        <th class="bg-light day-cell">{{ $d }}</th>
                    @endfor
                    <th class="bg-light text-center">H</th>
                    <th class="bg-light text-center">S</th>
                    <th class="bg-light text-center">I</th>
                    <th class="bg-light text-center">A</th>
                    <th class="bg-light text-center">C</th>
                    <th class="bg-light text-center">DL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekap as $gtkId => $records)
                    @php
                        $gtk = $records->first()?->gtk;
                        if (!$gtk) continue;
                        $statusCounts = ['hadir'=>0,'sakit'=>0,'izin'=>0,'alpa'=>0,'cuti'=>0,'dinas_luar'=>0];
                    @endphp
                    <tr>
                        <td class="gtk-name">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="font-size:.65rem;width:26px;height:26px">
                                    {{ strtoupper(substr($gtk->nama ?? 'G', 0, 1)) }}
                                </div>
                                <span class="fw-semibold text-truncate" style="max-width:180px">{{ $gtk->nama }}</span>
                            </div>
                        </td>
                        @for($day=1;$day<=$daysInMonth;$day++)
                            @php
                                $record = $records->firstWhere('tanggal', $tahun.'-'.str_pad($bulan ?? date('n'),2,'0',STR_PAD_LEFT).'-'.str_pad($day,2,'0',STR_PAD_LEFT));
                                $status = $record?->status;
                                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + ($status ? 1 : 0);
                            @endphp
                            <td class="day-cell">
                                @if($status)
                                    @php
                                        $colors = [
                                            'hadir' => 'success', 'sakit' => 'warning', 'izin' => 'info',
                                            'alpa' => 'danger', 'cuti' => 'secondary', 'dinas_luar' => 'primary',
                                        ];
                                        $c = $colors[$status] ?? 'light';
                                    @endphp
                                    <span class="badge badge-status bg-{{ $c }}-subtle text-{{ $c }}">{{ strtoupper(substr($status,0,1)) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endfor
                        <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $statusCounts['hadir'] }}</span></td>
                        <td class="text-center"><span class="badge bg-warning-subtle text-warning">{{ $statusCounts['sakit'] }}</span></td>
                        <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $statusCounts['izin'] }}</span></td>
                        <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $statusCounts['alpa'] }}</span></td>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">{{ $statusCounts['cuti'] }}</span></td>
                        <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $statusCounts['dinas_luar'] }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $daysInMonth + 9 }}" class="text-center py-5">
                            <div style="color:#3b82f6;opacity:.4"><i class="ri-file-chart-line" style="font-size:3rem"></i></div>
                            <h5 class="mt-2 fw-semibold">Tidak ada data</h5>
                            <p class="text-muted mb-0 small">Tidak ada catatan absensi untuk periode ini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
