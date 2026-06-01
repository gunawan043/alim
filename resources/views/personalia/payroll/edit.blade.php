{{-- Payroll: Edit — Form Edit Slip Gaji --}}
@extends('layouts.master')
@section('title') Edit Gaji @endsection

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
    @slot('li_2') Edit Gaji @endslot
    @slot('title') Edit Gaji @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Edit Gaji GTK</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Perbarui record slip gaji GTK.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.payroll.index', $userId) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-edit-2-line text-warning me-1"></i> Form Edit Gaji GTK</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.payroll.update', [$userId, $id]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama GTK <span class="text-danger">*</span></label>
                            <select name="gtk_id" class="form-select">
                                <option value="">-- Pilih GTK --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bulan <span class="text-danger">*</span></label>
                            <select name="bulan" class="form-select">
                                <option value="">-- Bulan --</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <select name="tahun" class="form-select">
                                @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-semibold text-dark mb-3"><i class="ri-money-dollar-circle-line me-1"></i> Komponen Gaji</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Gaji Pokok (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="gaji_pokok" class="form-control" placeholder="0" min="0">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-semibold text-dark mb-0"><i class="ri-gift-line me-1"></i> Tunjangan</h6>
                        <button type="button" id="add-tunjangan" class="btn btn-outline-primary btn-sm"><i class="ri-add-line me-1"></i> Tambah Baris</button>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Jenis Tunjangan</th>
                                    <th style="width:160px" class="text-end">Nominal (Rp)</th>
                                    <th style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody id="tunjangan-rows">
                                <tr>
                                    <td>
                                        <select name="tunjangan[jenis][]" class="form-select form-select-sm">
                                            <option value="">-- Pilih --</option>
                                            <option>Tunjangan Tetap</option>
                                            <option>Tunjangan Transport</option>
                                            <option>Tunjangan Makan</option>
                                            <option>Tunjangan Komunikasi</option>
                                            <option>Tunjangan Kesehatan</option>
                                        </select>
                                    </td>
                                    <td><input type="number" name="tunjangan[nominal][]" class="form-control form-control-sm text-end" placeholder="0" min="0"></td>
                                    <td class="text-center"><button type="button" class="btn btn-link text-danger p-0 btn-remove-row"><i class="ri-delete-bin-line"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-semibold text-dark mb-0"><i class="ri-subtract-line me-1"></i> Potongan</h6>
                        <button type="button" id="add-potongan" class="btn btn-outline-danger btn-sm"><i class="ri-add-line me-1"></i> Tambah Baris</button>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Jenis Potongan</th>
                                    <th style="width:160px" class="text-end">Nominal (Rp)</th>
                                    <th style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody id="potongan-rows">
                                <tr>
                                    <td>
                                        <select name="potongan[jenis][]" class="form-select form-select-sm">
                                            <option value="">-- Pilih --</option>
                                            <option>PPh Pasal 21</option>
                                            <option>Iuran BPJS TK</option>
                                            <option>Iuran BPJS Kesehatan</option>
                                            <option>Potongan Absensi</option>
                                            <option>Tabungan Wajib</option>
                                        </select>
                                    </td>
                                    <td><input type="number" name="potongan[nominal][]" class="form-control form-control-sm text-end" placeholder="0" min="0"></td>
                                    <td class="text-center"><button type="button" class="btn btn-link text-danger p-0 btn-remove-row"><i class="ri-delete-bin-line"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-semibold text-dark mb-3"><i class="ri-calculator-line me-1"></i> Ringkasan Gaji</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted" style="font-size:11px">TOTAL TUNJANGAN</label>
                            <div class="form-control form-control-sm bg-light fw-semibold text-end" id="total-tunjangan">Rp 0</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted" style="font-size:11px">TOTAL POTONGAN</label>
                            <div class="form-control form-control-sm bg-light fw-semibold text-end" id="total-potongan">Rp 0</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted" style="font-size:11px">GAJI POKOK</label>
                            <div class="form-control form-control-sm bg-light fw-semibold text-end" id="gaji-pokok-display">Rp 0</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted" style="font-size:11px">GAJI BERSIH</label>
                            <div class="form-control form-control-sm bg-success-subtle fw-bold text-end text-success" id="gaji-bersih">Rp 0</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('user.payroll.index', $userId) }}" class="btn btn-secondary btn-sm">Batal</a>
                        <button type="submit" class="btn btn-success btn-sm"><i class="ri-save-line me-1"></i> Update Gaji</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Info GTK</h5>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-4">
                    <i class="ri-user-search-line" style="font-size:3rem;opacity:.4"></i>
                    <p class="mt-2 mb-1 text-muted" style="font-size:.8rem">Pilih GTK untuk melihat ringkasan data gaji.</p>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-history-line me-1"></i> Riwayat Perubahan</h5>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-3">
                    <i class="ri-time-line" style="font-size:2.5rem;opacity:.4"></i>
                    <p class="mt-2 mb-0 text-muted small">Riwayat perubahan akan tampil setelah data disimpan.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection