@extends('waka.master')

@section('title') Surat Masuk @endsection

@section('css')
<style>
    .stat-card { border: 0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    .stat-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
    .stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
    .table-freeze td, .table-freeze th { vertical-align: middle; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('title') Surat Masuk @endslot
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

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-sm"><span class="avatar-title bg-primary-subtle text-primary rounded fs-4"><i class="ri-mail-line"></i></span></span>
                    <div>
                        <div class="stat-label">Total Surat</div>
                        <div class="stat-value">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-sm"><span class="avatar-title bg-warning-subtle text-warning rounded fs-4"><i class="ri-inbox-line"></i></span></span>
                    <div>
                        <div class="stat-label">Baru</div>
                        <div class="stat-value text-warning">{{ number_format($stats['baru']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-sm"><span class="avatar-title bg-info-subtle text-info rounded fs-4"><i class="ri-share-forward-line"></i></span></span>
                    <div>
                        <div class="stat-label">Didisposisi</div>
                        <div class="stat-value text-info">{{ number_format($stats['didisposisi']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-sm"><span class="avatar-title bg-success-subtle text-success rounded fs-4"><i class="ri-checkbox-circle-line"></i></span></span>
                    <div>
                        <div class="stat-label">Selesai</div>
                        <div class="stat-value text-success">{{ number_format($stats['selesai']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('waka.surat-masuk.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Cari</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nomor surat, pengirim, atau perihal…">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach(\App\Models\SuratMasuk::STATUS_OPTIONS as $val => $label)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Sifat</label>
                    <select name="sifat" class="form-select">
                        <option value="">Semua</option>
                        @foreach(\App\Models\SuratMasuk::SIFAT_OPTIONS as $val => $label)
                            <option value="{{ $val }}" {{ request('sifat') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Diterima Dari</label>
                    <input type="date" name="dari" class="form-control" value="{{ request('dari') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Sampai</label>
                    <input type="date" name="sampai" class="form-control" value="{{ request('sampai') }}">
                </div>
                <div class="col-12 d-flex gap-2 pt-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                    <a href="{{ route('waka.surat-masuk.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title mb-0"><i class="ri-mail-open-line me-2 text-primary"></i>Daftar Surat Masuk</h5>
            <a href="{{ route('waka.surat-masuk.create') }}" class="btn btn-primary btn-sm">
                <i class="ri-add-line me-1"></i>Tambah Surat
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>Nomor Surat</th>
                            <th>Pengirim</th>
                            <th>Perihal</th>
                            <th>Diterima</th>
                            <th>Sifat</th>
                            <th>Status</th>
                            <th class="text-end" style="width:120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suratMasukList as $i => $surat)
                            @php
                                $statusTone = match ($surat->status) {
                                    'baru' => 'warning',
                                    'didisposisi' => 'info',
                                    'selesai' => 'success',
                                    default => 'secondary',
                                };
                                $sifatTone = match ($surat->sifat) {
                                    'rahasia' => 'danger',
                                    'penting' => 'warning',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>{{ $suratMasukList->firstItem() + $i }}</td>
                                <td>
                                    <strong>{{ $surat->nomor_surat }}</strong>
                                    <div class="small text-muted">{{ $surat->tanggal_surat?->format('d/m/Y') }}</div>
                                </td>
                                <td>{{ $surat->pengirim }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($surat->perihal, 60) }}</td>
                                <td>{{ $surat->tanggal_diterima?->format('d/m/Y') }}</td>
                                <td><span class="badge bg-{{ $sifatTone }}-subtle text-{{ $sifatTone }}">{{ \App\Models\SuratMasuk::SIFAT_OPTIONS[$surat->sifat] ?? ucfirst($surat->sifat) }}</span></td>
                                <td><span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}">{{ \App\Models\SuratMasuk::STATUS_OPTIONS[$surat->status] ?? ucfirst($surat->status) }}</span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('waka.surat-masuk.show', $surat->id) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('waka.surat-masuk.edit', $surat->id) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri-edit-line"></i></a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"
                                                data-bs-toggle="modal" data-bs-target="#deleteSuratModal"
                                                data-action="{{ route('waka.surat-masuk.destroy', $surat->id) }}"
                                                data-name="{{ $surat->nomor_surat }}"><i class="ri-delete-bin-line"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="8" title="Belum ada surat masuk"
                                message="Catat surat masuk pertama agar arsip surat rapi dan mudah dicari.">
                                <a href="{{ route('waka.surat-masuk.create') }}" class="btn btn-primary mt-3">
                                    <i class="ri-add-line me-1"></i>Tambah Surat
                                </a>
                            </x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($suratMasukList->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $suratMasukList])
            </div>
        @endif
    </div>

    <form id="deleteSuratForm" method="POST" class="d-none" action="">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-confirm id="deleteSuratModal" class="zoomIn"
        icon="https://cdn.lordicon.com/gsqxdxog.json"
        primaryColor="#f7b84b" secondaryColor="#f06548"
        title="Yakin ingin menghapus?"
        submitLabel="Ya, Hapus!"
        submitIcon="ri-delete-bin-line"
        submitClass="btn-danger"
        formId="deleteSuratForm">
        <p class="text-center text-muted mb-0" id="deleteSuratMessage">Surat masuk akan dihapus dari arsip.</p>
    </x-modal-confirm>
@endsection

@section('script')
<script>
    document.getElementById('deleteSuratModal')?.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;
        document.getElementById('deleteSuratForm').action = button.getAttribute('data-action') || '';
        var name = button.getAttribute('data-name') || '';
        document.getElementById('deleteSuratMessage').textContent = 'Surat "' + name + '" akan dihapus dari arsip.';
    });
</script>
@endsection
