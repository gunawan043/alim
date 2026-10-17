@extends('layouts.master')
@section('title') Laporan Sarpras @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Laporan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Laporan Sarana Prasarana</h5></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-file-list-3-line fs-1 text-primary mb-2"></i>
                                <h5>Inventaris Per Ruang</h5>
                                <p class="text-muted small">Daftar aset yang ditempatkan di setiap ruangan</p>
                                <a href="{{ route('sarpras.laporan.inventaris-per-ruang') }}" class="btn btn-primary btn-sm"><i class="ri-eye-line me-1"></i> Lihat</a>
                                <a href="{{ route('sarpras.laporan.inventaris-per-ruang.pdf') }}" class="btn btn-outline-primary btn-sm"><i class="ri-download-line me-1"></i> PDF</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-bar-chart-box-line fs-1 text-warning mb-2"></i>
                                <h5>Kondisi Aset</h5>
                                <p class="text-muted small">Laporan kondisi semua aset (Baik, Rusak Ringan, dll)</p>
                                <a href="{{ route('sarpras.laporan.kondisi-aset') }}" class="btn btn-warning btn-sm"><i class="ri-eye-line me-1"></i> Lihat</a>
                                <a href="{{ route('sarpras.laporan.kondisi-aset.pdf') }}" class="btn btn-outline-warning btn-sm"><i class="ri-download-line me-1"></i> PDF</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-exchange-funds-line fs-1 text-info mb-2"></i>
                                <h5>Peminjaman</h5>
                                <p class="text-muted small">Riwayat dan statistik peminjaman aset</p>
                                <a href="{{ route('sarpras.laporan.peminjaman') }}" class="btn btn-info btn-sm"><i class="ri-eye-line me-1"></i> Lihat</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-tools-line fs-1 text-success mb-2"></i>
                                <h5>Pemeliharaan</h5>
                                <p class="text-muted small">Riwayat dan biaya pemeliharaan aset</p>
                                <a href="{{ route('sarpras.laporan.pemeliharaan') }}" class="btn btn-success btn-sm"><i class="ri-eye-line me-1"></i> Lihat</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-money-dollar-circle-line fs-1 text-secondary mb-2"></i>
                                <h5>Nilai Aset</h5>
                                <p class="text-muted small">Nilai perolehan, nilai buku, dan penyusutan</p>
                                <a href="{{ route('sarpras.laporan.nilai-aset') }}" class="btn btn-secondary btn-sm"><i class="ri-eye-line me-1"></i> Lihat</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="ri-file-excel-line fs-1 text-danger mb-2"></i>
                                <h5>Export Excel</h5>
                                <p class="text-muted small">Export semua data aset ke Excel</p>
                                <a href="{{ route('sarpras.laporan.export') }}" class="btn btn-danger btn-sm"><i class="ri-download-cloud-line me-1"></i> Download</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
