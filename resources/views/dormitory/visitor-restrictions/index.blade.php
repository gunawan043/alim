@extends('layouts.master')
@section('title', 'Blacklist Tamu Asrama')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kunjungan</a> @endslot
        @slot('title') Blacklist Tamu @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
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
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama/NIK/HP..." value="{{ request('search') }}">
                            <button class="btn btn-primary btn-sm"><i class="ri-search-line"></i></button>
                        </form>
                        @if($canManage)
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#restrictionModal">
                                <i class="ri-add-line me-1"></i> Tambah Blacklist
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0">
                    <thead>
                        <tr>
                            <th>Nama Tamu</th>
                            <th>NIK</th>
                            <th>No. HP</th>
                            <th>Alasan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($restrictions as $restriction)
                            <tr>
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
                                            <form method="POST" action="{{ route('user.asrama.visitor-restrictions.toggle', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'restrictionUuid' => $restriction->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-warning" title="Aktif/Nonaktif"><i class="ri-toggle-line"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('user.asrama.visitor-restrictions.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'restrictionUuid' => $restriction->id]) }}" onsubmit="return confirm('Hapus blacklist ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted small">Lihat saja</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada tamu dalam blacklist.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($restrictions->hasPages())
                <div class="mt-3">@include('shared._pagination', ['paginator' => $restrictions])</div>
            @endif
        </div>
    </div>

    @if($canManage)
        <div class="modal fade" id="restrictionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.asrama.visitor-restrictions.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-forbid-2-line me-2 text-danger"></i>Tambah Blacklist</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nama Tamu <span class="text-danger">*</span></label>
                                    <input type="text" name="visitor_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NIK</label>
                                    <input type="text" name="id_number" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No. HP</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Alasan <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" required placeholder="Alasan pembatasan kunjungan..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-danger"><i class="ri-save-line me-1"></i>Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
