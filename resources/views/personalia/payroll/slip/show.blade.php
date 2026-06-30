{{-- Payroll Slip: Detail Slip Gaji --}}
@extends('layouts.master')
@section('title') Detail Slip Gaji @endsection

@push('css')
<style>
.slip-card{background:linear-gradient(135deg,#fffbeb 0%,#fffef5 100%);border:1px solid #fcd34d;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .slip-card{background:linear-gradient(135deg,#1c1400 0%,#2a1e00 100%);border-color:#d97706}
.slip-detail{font-size:.85rem}
.earning-row{color:#059669}
.deduction-row{color:#dc2626}
.total-row{background:#fef3c7;border-radius:.5rem}
@media print{
    .no-print{display:none!important}
    .main-content{padding:0}
}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Gaji & Kompensasi @endslot
    @slot('li_2') Slip Gaji @endslot
    @slot('title') Detail Slip Gaji @endslot
@endcomponent

<div class="slip-card mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:#f59e0b18;color:#d97706;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-money-dollar-circle-line fs-4"></i>
        </div>
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size:1.1rem">Slip Gaji GTK</h4>
            <p class="mb-0 text-muted" style="font-size:.8rem">{{ $payroll->gtk?->nama ?? '-' }} — {{ str_pad($payroll->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->tahun }}</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 no-print">
        <a href="{{ route('user.payroll-slip.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
        <a href="{{ route('user.payroll-slip.pdf', [$userId, $payroll->id]) }}" class="btn btn-warning btn-sm"><i class="ri-printer-line me-1"></i>Cetak PDF</a>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="ri-printer-fill me-1"></i>Print Halaman</button>
    </div>
</div>

<div class="row g-4">
    {{-- Header / Meta --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-information-line text-primary me-1"></i>Informasi Slip</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 slip-detail">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width:140px">Nama GTK</td>
                                <td>: <strong>{{ $payroll->gtk?->nama ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">No. Induk</td>
                                <td>: {{ $payroll->gtk?->nik ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Periode</td>
                                <td>: <strong>{{ str_pad($payroll->bulan, 2, '0', STR_PAD_LEFT) }} / {{ $payroll->tahun }}</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width:140px">Status</td>
                                <td>:
                                    @php
                                        $s = $payroll->status ?? 'draft';
                                        $mc = ['draft'=>'warning','published'=>'primary','paid'=>'success','void'=>'danger'];
                                        $c = $mc[$s] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ ucfirst($s) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tanggal Bayar</td>
                                <td>: {{ $payroll->tanggal_bayar ? \Carbon\Carbon::parse($payroll->tanggal_bayar)->format('d F Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Dibuat Oleh</td>
                                <td>: {{ $payroll->pembuat?->name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Earnings --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line text-success me-1"></i> Komponen Pendapatan</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="bg-light" style="width:48px">No</th>
                            <th class="bg-light">Komponen</th>
                            <th class="bg-light text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">1</td>
                            <td class="earning-row"><strong>Gaji Pokok</strong></td>
                            <td class="text-end earning-row fw-semibold">Rp {{ number_format((float)$payroll->gaji_pokok, 0, ',', '.') }}</td>
                        </tr>
                        @if(is_array($payroll->detail_tunjangan) && count($payroll->detail_tunjangan) > 0)
                            @php $idx = 2; @endphp
                            @foreach($payroll->detail_tunjangan as $row)
                                <tr>
                                    <td class="text-center">{{ $idx++ }}</td>
                                    <td class="earning-row">- {{ $row['jenis'] ?? 'Tunjangan' }}</td>
                                    <td class="text-end earning-row">Rp {{ number_format((float)($row['nominal'] ?? 0), 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @endif
                        <tr style="border-top:2px solid #16a34a">
                            <td colspan="2" class="fw-semibold">Total Pendapatan</td>
                            <td class="text-end fw-bold text-success">Rp {{ number_format((float)($payroll->gaji_pokok + $payroll->total_tunjangan), 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Deductions --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-light-subtle border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line text-danger me-1"></i> Komponen Potongan</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="bg-light" style="width:48px">No</th>
                            <th class="bg-light">Potongan</th>
                            <th class="bg-light text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(is_array($payroll->detail_potongan) && count($payroll->detail_potongan) > 0)
                            @php $idx = 1; @endphp
                            @foreach($payroll->detail_potongan as $row)
                                <tr>
                                    <td class="text-center">{{ $idx++ }}</td>
                                    <td class="deduction-row">{{ $row['jenis'] ?? 'Potongan' }}</td>
                                    <td class="text-end deduction-row">Rp {{ number_format((float)($row['nominal'] ?? 0), 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr style="border-top:2px solid #dc2626">
                                <td colspan="2" class="fw-semibold">Total Potongan</td>
                                <td class="text-end fw-bold text-danger">Rp {{ number_format((float)$payroll->total_potongan, 0, ',', '.') }}</td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <small>Tidak ada potongan</small>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Take Home Pay --}}
    <div class="col-12">
        <div class="card total-row" style="border:1px solid #d97706">
            <div class="card-body text-center py-4">
                <div class="text-muted text-uppercase small mb-1" style="letter-spacing:1px">GAJI BERSIH (Take Home Pay)</div>
                <h1 class="fw-bold mb-0" style="color:#d97706;font-size:2.2rem">Rp {{ number_format((float)$payroll->gaji_bersih, 0, ',', '.') }}</h1>
                <div class="small text-muted mt-2">
                    Periode {{ str_pad($payroll->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->tahun }} • {{ $payroll->gtk?->nama ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    @if(!empty($payroll->catatan))
        <div class="col-12">
            <div class="alert alert-warning small mb-0">
                <i class="ri-information-line me-1"></i>
                <strong>Catatan:</strong> {{ $payroll->catatan }}
            </div>
        </div>
    @endif
</div>
@endsection
