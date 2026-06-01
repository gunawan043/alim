@extends('layouts.master')
@section('title') Ajukan Cuti @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 100%);border:1px solid #bfdbfe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);border-color:#4338ca}
.quota-item{border-radius:.5rem;padding:.75rem 1rem;background:#f0f9ff;border:1px solid #bae6fd;display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem}
.quota-item .label{font-size:.8rem;color:#0369a1;font-weight:500}
.quota-item .value{font-size:1.1rem;font-weight:700;color:#0369a1}
.form-card{border:.5px solid #bfdbfe;border-radius:.625rem;overflow:hidden}
.form-card .card-header{background:#f0f9ff;border-bottom:1px solid #bfdbfe;padding:.75rem 1.25rem;font-weight:600;font-size:.9rem}
[data-bs-theme="dark"] .form-card{border-color:#4338ca}
[data-bs-theme="dark"] .form-card .card-header{background:#1e1b4b;border-bottom-color:#4338ca}
[data-bs-theme="dark"] .quota-item{background:#1e1b4b;border-color:#4338ca}
[data-bs-theme="dark"] .quota-item .label{color:#93c5fd}
[data-bs-theme="dark"] .quota-item .value{color:#93c5fd}
</style>
@endpush

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') Cuti & Izin @endslot
    @slot('li_2') <a href="{{ route('user.cuti.index', ['userId' => $userId]) }}">Daftar Pengajuan</a> @endslot
    @slot('title') Ajukan Cuti @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Ajukan Cuti Baru</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">Ajukan cuti atau izin sesuai dengan jatah yang tersedia</p>
    </div>
    <a href="{{ route('user.cuti.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card form-card">
            <div class="card-header"><i class="ri-file-edit-line me-1"></i> Form Pengajuan Cuti</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('user.cuti.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                        <select name="cuti_template_id" class="form-select @error('cuti_template_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Jenis Cuti --</option>
                            @foreach($templates as $t)
                            <option value="{{ $t->id }}" {{ old('cuti_template_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->nama }} ({{ $t->jumlah_hari }} hari)
                            </option>
                            @endforeach
                        </select>
                        @error('cuti_template_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                   value="{{ old('tanggal_mulai') }}" required>
                            @error('tanggal_mulai') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                   value="{{ old('tanggal_selesai') }}" required>
                            @error('tanggal_selesai') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan / Keterangan</label>
                        <textarea name="alasan" class="form-control @error('alasan') is-invalid @enderror"
                                  rows="3" placeholder="Jelaskan alasan pengajuan cuti...">{{ old('alasan') }}</textarea>
                        @error('alasan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="ri-send-plane-line me-1"></i> Kirim Pengajuan</button>
                        <a href="{{ route('user.cuti.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card form-card mb-3">
            <div class="card-header"><i class="ri-information-line me-1"></i> Informasi</div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="ri-info-fill me-1"></i>
                    <strong>Periode:</strong> {{ $period->name ?? 'Aktif' }}
                </div>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2"><i class="ri-checkbox-circle-line text-success me-1"></i> Pengajuan akan diverifikasi oleh atasan</li>
                    <li class="mb-2"><i class="ri-checkbox-circle-line text-success me-1"></i> Tanggal mulai harus hari ini atau lebih</li>
                    <li class="mb-2"><i class="ri-checkbox-circle-line text-success me-1"></i> Cuti sakit tanpa batas (dengan surat dokter)</li>
                </ul>
            </div>
        </div>
        @if($quotas->count())
        <div class="card form-card">
            <div class="card-header"><i class="ri-pie-chart-line me-1"></i> Kuota Saya</div>
            <div class="card-body">
                @foreach($quotas as $q)
                @php $used = $q->jumlah_hari - $q->tersisa; @endphp
                <div class="quota-item">
                    <div>
                        <div class="label">{{ $q->template->nama ?? '-' }}</div>
                        <div class="small text-muted">Terpakai: {{ $used }} dari {{ $q->jumlah_hari }} hari</div>
                    </div>
                    <div class="value">{{ $q->tersisa }}<span style="font-size:.75rem;font-weight:400"> hari</span></div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection