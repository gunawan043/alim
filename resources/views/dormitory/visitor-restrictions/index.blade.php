@extends('layouts.master')
@section('title', 'Blacklist Tamu Asrama')
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kunjungan</a> @endslot
        @slot('title') Blacklist Tamu @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-forbid-2-line me-1 text-danger"></i>Blacklist Tamu</h5>
                    <p class="text-muted mb-0 small">Tamu dengan NIK, nomor HP, atau nama yang cocok akan ditolak saat pengajuan kunjungan.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        @if($canManage)
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#restrictionModal">
                                <i class="ri-add-line me-1"></i> Tambah Blacklist
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- ============================================================
                 TOOLBAR PENCARIAN (pola template)
            ============================================================ --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:520px;">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Cari nama / NIK / No. HP..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                        <i class="ri-search-line"></i> Cari
                    </button>
                </form>

                <div class="toolbar-actions">
                    <a href="{{ route('user.asrama.visitor-restrictions.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-light btn-sm" title="Reset filter">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </div>

            @if(request('search'))
                <div class="mb-3">
                    <span class="text-muted small">Filter aktif:</span>
                    <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                </div>
            @endif

            <div class="d-flex align-items-center gap-2 mb-2">
                <small class="text-muted">
                    <i class="ri-file-list-line me-1"></i> Menampilkan
                    <strong>{{ $restrictions->count() }}</strong> dari
                    <strong>{{ $restrictions->total() }}</strong> entri blacklist
                </small>
            </div>

            {{-- ============================================================
                 TABEL
            ============================================================ --}}
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th>Nama Tamu</th>
                            <th>NIK</th>
                            <th>No. HP</th>
                            <th>Alasan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($restrictions ?? [] as $i => $restriction)
                            <tr>
                                <td class="text-center text-muted">{{ $restrictions->firstItem() + $i }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $restriction->visitor_name }}</div>
                                    <small class="text-muted">oleh {{ $restriction->creator?->name ?? 'Sistem' }}</small>
                                </td>
                                <td>{{ $restriction->id_number ?: '—' }}</td>
                                <td>{{ $restriction->phone ?: '—' }}</td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($restriction->reason, 80) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $restriction->is_active ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $restriction->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($canManage)
                                        <div class="d-flex gap-1 justify-content-center">
                                            <form method="POST"
                                                  action="{{ route('user.asrama.visitor-restrictions.toggle', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'restrictionUuid' => $restriction->id]) }}"
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Aktif/Nonaktif">
                                                    <i class="ri-toggle-line"></i>
                                                </button>
                                            </form>
                                            <form method="POST"
                                                  action="{{ route('user.asrama.visitor-restrictions.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'restrictionUuid' => $restriction->id]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Hapus blacklist ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Blacklist">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted small">Lihat saja</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7" title="Belum ada tamu dalam blacklist"
                                message="Belum ada tamu yang dibatasi untuk asrama ini.">
                                @if($canManage)
                                    <button type="button" class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#restrictionModal">
                                        <i class="ri-add-line me-1"></i> Tambah Blacklist
                                    </button>
                                @endif
                            </x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($restrictions->hasPages())
                @include('shared._pagination', ['paginator' => $restrictions->withQueryString()])
            @endif
        </div>
    </div>

    {{-- ============================================================
         MODAL TAMBAH BLACKLIST
    ============================================================ --}}
    @if($canManage)
        <div class="modal fade" id="restrictionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.asrama.visitor-restrictions.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-forbid-2-line me-2 text-danger"></i>Tambah Blacklist</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fs-12 text-muted mb-1">Nama Tamu <span class="text-danger">*</span></label>
                                    <input type="text" name="visitor_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-12 text-muted mb-1">NIK</label>
                                    <input type="text" name="id_number" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-12 text-muted mb-1">No. HP</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fs-12 text-muted mb-1">Alasan <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" required placeholder="Alasan pembatasan kunjungan..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection