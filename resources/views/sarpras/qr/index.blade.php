@extends('layouts.master')
@section('title') QR Code & Audit @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') QR Code & Audit @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body text-center">
                <h2 class="mb-1">{{ number_format($totalAssets) }}</h2>
                <p class="text-muted mb-0">Total Aset</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body text-center">
                <h2 class="mb-1 text-success">{{ number_format($withQR) }}</h2>
                <p class="text-muted mb-0">Sudah Ada QR</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body text-center">
                <h2 class="mb-1 text-warning">{{ number_format($withoutQR) }}</h2>
                <p class="text-muted mb-0">Belum Ada QR</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body text-center">
                <h2 class="mb-1 text-info">{{ round(($totalAssets > 0 ? $withQR / $totalAssets * 100 : 0), 1) }}%</h2>
                <p class="text-muted mb-0">Coverage QR</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Menu QR Code & Audit</h5></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-qr-code-line fs-1 text-primary mb-2"></i>
                                <h5>Generate QR</h5>
                                <p class="text-muted small">Generate QR code untuk semua aset yang belum punya</p>
                                <form action="{{ route('sarpras.qr.generate-all') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm w-100" onclick="return confirm('Generate QR untuk semua aset?')">
                                        <i class="ri-qr-code-line me-1"></i> Generate Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-printer-line fs-1 text-info mb-2"></i>
                                <h5>Print QR Label</h5>
                                <p class="text-muted small">Cetak label QR untuk ditempelkan di aset</p>
                                <a href="{{ route('sarpras.qr.print') }}" class="btn btn-info btn-sm w-100">
                                    <i class="ri-printer-line me-1"></i> Print Label
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-scan-line fs-1 text-success mb-2"></i>
                                <h5>QR Scanner</h5>
                                <p class="text-muted small">Scan QR aset untuk melihat detail & audit</p>
                                <a href="{{ route('sarpras.qr.scanner') }}" class="btn btn-success btn-sm w-100">
                                    <i class="ri-scan-line me-1"></i> Buka Scanner
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-smartphone-line fs-1 text-warning mb-2"></i>
                                <h5>Audit Massal</h5>
                                <p class="text-muted small">Audit kondisi aset secara massal (mobile-friendly)</p>
                                <a href="{{ route('sarpras.qr.bulk-audit') }}" class="btn btn-warning btn-sm w-100">
                                    <i class="ri-list-check me-1"></i> Bulk Audit
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-file-pdf-line fs-1 text-danger mb-2"></i>
                                <h5>Download PDF</h5>
                                <p class="text-muted small">Download QR label sebagai PDF</p>
                                <a href="{{ route('sarpras.qr.pdf') }}" class="btn btn-danger btn-sm w-100">
                                    <i class="ri-download-cloud-line me-1"></i> Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-bar-chart-box-line fs-1 text-secondary mb-2"></i>
                                <h5>Lihat Aset</h5>
                                <p class="text-muted small">Lihat daftar aset untuk manajemen</p>
                                <a href="{{ route('sarpras.aset.index') }}" class="btn btn-secondary btn-sm w-100">
                                    <i class="ri-archive-line me-1"></i> Daftar Aset
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-search-line fs-1 text-warning mb-2"></i>
                                <h5>Lookup Aset</h5>
                                <p class="text-muted small">Cari aset secara manual via kode atau ID</p>
                                <a href="{{ route('sarpras.qr.lookup-page') }}" class="btn btn-warning btn-sm w-100">
                                    <i class="ri-search-line me-1"></i> Lookup
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
