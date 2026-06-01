@extends('layouts.master')
@section('title') Aturan Cuti & Izin @endsection

@section('content')
@php $userId = request()->route('userId') ?? Auth::id(); @endphp
@component('components.breadcrumb')
    @slot('li_1') Cuti & Izin @endslot
    @slot('title') Pengaturan @endslot
@endcomponent

@component('components.hrd-page-header', [
    'subtitle' => 'Kelola aturan dan kebijakan cuti & izin untuk GTK PUSTIK.',
    'icon' => 'ri-settings-3-line',
    'color' => 'slate',
])
@endcomponent

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-calendar-check-line me-2 text-primary"></i>Kuota Cuti Tahunan</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Jumlah Hari Cuti Tahunan</label>
                        <input type="number" class="form-control" value="12" min="1">
                        <small class="text-muted">Jumlah hari cuti tahunan yang diberikan kepada setiap GTK.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sisa Cuti Diakumulasi</label>
                        <select class="form-select">
                            <option value="yes">Ya, akumulasi maksimal 6 hari</option>
                            <option value="no">Tidak, tidak diakumulasi</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-time-line me-2 text-warning"></i>Minimal Pengajuan</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Minimal Pengajuan (hari sebelum)</label>
                        <input type="number" class="form-control" value="3" min="1">
                        <small class="text-muted">Pengajuan cuti harus diajukan minimal H-n sebelum tanggal mulai.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Maksimal Durasi Cuti (hari)</label>
                        <input type="number" class="form-control" value="14" min="1">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-shield-check-line me-2 text-info"></i>Kebijakan Cuti Sakit</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Wajib Lampiran Surat Dokter</label>
                        <select class="form-select">
                            <option value="yes">Ya</option>
                            <option value="no">Tidak</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Maksimal Cuti Sakit Tanpa Surat (hari)</label>
                        <input type="number" class="form-control" value="2" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection