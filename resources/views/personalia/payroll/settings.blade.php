{{-- Payroll: Settings — Pengaturan Payroll --}}
@extends('layouts.master')
@section('title') Pengaturan Gaji @endsection

@push('css')
<style>
.page-header-card{
  background:linear-gradient(135deg,#fffbeb 0%,#fffef5 100%);
  border:1px solid #fde68a;
  padding:1.25rem 1.5rem;
  border-radius:.625rem;
}
[data-bs-theme="dark"] .page-header-card{
  background:linear-gradient(135deg,#1c1400 0%,#1f1800 100%);
  border-color:#d97706;
}
.stat-card{transition:all .25s ease;cursor:default}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.table-freeze{table-layout:auto;min-width:max-content;width:100%;margin-bottom:0}
.table-freeze th,.table-freeze td{vertical-align:middle;padding:12px 16px;word-break:break-word}
.table-freeze th:first-child,.table-freeze td:first-child{position:sticky;left:0;z-index:10;background:#fff;min-width:200px;box-shadow:2px 0 5px rgba(0,0,0,.05)}
.table-freeze thead th{position:sticky;top:0;z-index:20;font-weight:600;background:#f8fafc;border-bottom:2px solid #e2e8f0}
[data-bs-theme="dark"] .table-freeze th:first-child,[data-bs-theme="dark"] .table-freeze td:first-child{background:#1e293b}
[data-bs-theme="dark"] .table-freeze thead th{background:#1e293b}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Gaji & Kompensasi @endslot
    @slot('li_2') Pengaturan @endslot
    @slot('title') Pengaturan Payroll @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Pengaturan Payroll</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Kelola komponen gaji, tarif BPJS, dan rumus tunjangan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.payroll.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-4">
    {{-- Komponen Gaji --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line text-warning me-1"></i> Komponen Gaji</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Kelola komponen utama pembentuk gaji GTK.</p>
                <div class="mb-3">
                    <label class="form-label">Nama Komponen</label>
                    <input type="text" class="form-control form-control-sm" placeholder="cth: Gaji Pokok, Tunjangan Tetap">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis</label>
                    <select class="form-select form-select-sm">
                        <option value="pendapatan">Pendapatan</option>
                        <option value="potongan">Potongan</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nominal Default (Rp)</label>
                    <input type="number" class="form-control form-control-sm" placeholder="0" min="0">
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm"><i class="ri-add-line me-1"></i> Tambah</button>
                    <button class="btn btn-outline-secondary btn-sm"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
                <hr class="my-3">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Komponen</th>
                                <th>Jenis</th>
                                <th class="text-end">Default</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted small">Belum ada komponen.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tarif BPJS --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-shield-star-line text-warning me-1"></i> Tarif BPJS</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Pengaturan tarif iuran BPJS Ketenagakerjaan dan Kesehatan.</p>

                <h6 class="fw-semibold text-dark mb-2">BPJS Ketenagakerjaan</h6>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-muted">JHT Employer (%)</label>
                        <input type="number" class="form-control form-control-sm" value="3.7" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">JHT Employee (%)</label>
                        <input type="number" class="form-control form-control-sm" value="2" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">JP Employer (%)</label>
                        <input type="number" class="form-control form-control-sm" value="2" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">JP Employee (%)</label>
                        <input type="number" class="form-control form-control-sm" value="1" step="0.1">
                    </div>
                </div>

                <hr class="my-3">

                <h6 class="fw-semibold text-dark mb-2">BPJS Kesehatan</h6>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-muted">Employer (%)</label>
                        <input type="number" class="form-control form-control-sm" value="4" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Employee (%)</label>
                        <input type="number" class="form-control form-control-sm" value="1" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Maks. Iuran (Rp)</label>
                        <input type="number" class="form-control form-control-sm" value="12000000" min="0">
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary btn-sm"><i class="ri-reset-line me-1"></i> Reset</button>
                    <button class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Pajak --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-file-chart-line text-warning me-1"></i> Pengaturan Pajak</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Pengaturan tarif pajak penghasilan (PPh Pasal 21).</p>
                <div class="mb-3">
                    <label class="form-label small text-muted">Status PTKP</label>
                    <select class="form-select form-select-sm">
                        <option value="TK/0">TK/0 (Tanpa Tanggungan)</option>
                        <option value="K/0">K/0 (1 Tanggungan)</option>
                        <option value="K/1">K/1 (2 Tanggungan)</option>
                        <option value="K/2">K/2 (3 Tanggungan)</option>
                        <option value="K/3">K/3 (3+ Tanggungan)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Tarif PPh Pasal 21 (%)</label>
                    <input type="number" class="form-control form-control-sm" value="5" step="0.1" min="0" max="100">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">PTKP Tahunan (Rp)</label>
                    <input type="number" class="form-control form-control-sm" value="54000000" min="0">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="pph21Enabled" checked>
                    <label class="form-check-label small" for="pph21Enabled">Aktifkan perhitungan PPh Pasal 21 otomatis</label>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary btn-sm"><i class="ri-reset-line me-1"></i> Reset</button>
                    <button class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Rumus Tunjangan --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-calculator-line text-warning me-1"></i> Rumus Tunjangan</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Pengaturan rumus dan batas maksimal tunjangan.</p>
                <div class="mb-3">
                    <label class="form-label small text-muted">Tunjangan Transport</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" placeholder="0" min="0">
                        <span class="input-group-text">/bulan</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Tunjangan Makan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" placeholder="0" min="0">
                        <span class="input-group-text">/hari</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Tunjangan Komunikasi</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" placeholder="0" min="0">
                        <span class="input-group-text">/bulan</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Uang Lembur</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" placeholder="0" min="0">
                        <span class="input-group-text">/jam</span>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary btn-sm"><i class="ri-reset-line me-1"></i> Reset</button>
                    <button class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection