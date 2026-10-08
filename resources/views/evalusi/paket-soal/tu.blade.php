@extends('layouts.master')
@section('title', 'TU — Paket Soal Final')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Evaluasi @endslot
        @slot('title') Tata Usaha — Cetak & Perbanyak @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="alert alert-info small" role="alert">
        <i class="ri-information-line me-1"></i>TU menerima paket final langsung dari sistem dan hanya mengelola <strong>operasional produksi</strong> (jumlah cetak, status, tanggal, petugas). Isi soal tidak dapat diubah.
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100"><div class="card-body py-3">
                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Paket Final</p>
                <h3 class="fw-bold ff-secondary mb-0">{{ $stats['total'] }}</h3>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100"><div class="card-body py-3">
                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Didistribusikan</p>
                <h3 class="fw-bold ff-secondary mb-0 text-success">{{ $stats['didistribusikan'] }}</h3>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100"><div class="card-body py-3">
                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Lembar Cetak</p>
                <h3 class="fw-bold ff-secondary mb-0 text-primary">{{ number_format($stats['total_cetak']) }}</h3>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100"><div class="card-body py-3">
                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Produksi Selesai</p>
                <h3 class="fw-bold ff-secondary mb-0">{{ $stats['selesai'] }}</h3>
            </div></div>
        </div>
    </div>

    <div class="accordion" id="tuPaketAccordion">
        @forelse($pakets as $index => $paket)
            <div class="accordion-item mb-2 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#paket-{{ $paket->id }}">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-primary-subtle text-primary">{{ $paket->kode_paket }}</span>
                            <strong>{{ $paket->judul }}</strong>
                            <span class="text-muted small">{{ $paket->kisiKisi?->subject?->name }} · {{ $paket->kisiKisi?->gradeLevel?->name }} · {{ $paket->kisiKisi?->academicYear?->name }}</span>
                            @if($paket->distributed_at)
                                <span class="badge bg-success-subtle text-success">Didistribusikan {{ $paket->distributed_at->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </button>
                </h2>
                <div id="paket-{{ $paket->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#tuPaketAccordion">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <h6 class="fw-semibold"><i class="ri-add-circle-line me-1"></i>Catat Perintah Cetak</h6>
                                <form method="POST" action="{{ route('user.tu-paket-soal.print-jobs.store', ['userId' => $userId, 'paketUuid' => $paket->id]) }}" class="row g-2">
                                    @csrf
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Jumlah Cetak</label>
                                        <input type="number" name="jumlah_cetak" class="form-control form-control-sm" min="1" value="{{ $paket->jumlah_soal_aktual * 40 }}" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="antri">Antri</option>
                                            <option value="proses">Proses</option>
                                            <option value="selesai">Selesai</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Tanggal Produksi</label>
                                        <input type="date" name="tanggal_produksi" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Petugas</label>
                                        <input type="text" name="petugas" class="form-control form-control-sm" value="{{ auth()->user()->name }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Catatan</label>
                                        <input type="text" name="catatan" class="form-control form-control-sm" maxlength="2000">
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-sm btn-primary"><i class="ri-save-line me-1"></i>Simpan Perintah</button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-7">
                                <h6 class="fw-semibold"><i class="ri-list-check me-1"></i>Daftar Produksi</h6>
                                @forelse($paket->printJobs as $job)
                                    <form method="POST" action="{{ route('user.tu-paket-soal.print-jobs.update', ['userId' => $userId, 'paketUuid' => $paket->id, 'jobId' => $job->id]) }}"
                                          class="row g-1 align-items-end border rounded p-2 mb-2">
                                        @csrf @method('PUT')
                                        <div class="col-3">
                                            <label class="form-label small mb-1">Jumlah</label>
                                            <input type="number" name="jumlah_cetak" class="form-control form-control-sm" value="{{ $job->jumlah_cetak }}" min="1">
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label small mb-1">Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                @foreach(\App\Models\PaketSoalPrintJob::STATUS_OPTIONS as $value => $label)
                                                    <option value="{{ $value }}" {{ $job->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label small mb-1">Tanggal</label>
                                            <input type="date" name="tanggal_produksi" class="form-control form-control-sm" value="{{ $job->tanggal_produksi?->format('Y-m-d') }}">
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label small mb-1">Petugas</label>
                                            <input type="text" name="petugas" class="form-control form-control-sm" value="{{ $job->petugas }}">
                                        </div>
                                        <div class="col-9">
                                            <input type="text" name="catatan" class="form-control form-control-sm" value="{{ $job->catatan }}" placeholder="Catatan">
                                        </div>
                                        <div class="col-3">
                                            <button class="btn btn-sm btn-soft-success w-100"><i class="ri-check-line me-1"></i>Update</button>
                                        </div>
                                    </form>
                                @empty
                                    <p class="text-muted small mb-0">Belum ada perintah cetak untuk paket ini.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center py-5 text-muted">
                <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                Belum ada paket soal final yang didistribusikan ke Tata Usaha.
            </div></div>
        @endforelse
    </div>
@endsection
