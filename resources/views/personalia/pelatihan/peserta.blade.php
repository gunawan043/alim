{{-- Pelatihan: Kelola Peserta --}}
@extends('layouts.master')
@section('title') Peserta Pelatihan @endsection

@push('css')
<style>
.page-header-card{background:linear-gradient(135deg,#eef2ff 0%,#f5f7ff 100%);border:1px solid #c7d2fe;padding:1.25rem 1.5rem;border-radius:.625rem}
[data-bs-theme="dark"] .page-header-card{background:linear-gradient(135deg,#1e1b4b 0%,#1e1a2e 100%);border-color:#4338ca}
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
    @slot('li_1') Personalia @endslot
    @slot('li_2') Pelatihan @endslot
    @slot('li_3') Peserta @endslot
    @slot('title') Kelola Peserta @endslot
@endcomponent

<div class="page-header-card d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-1">Kelola Peserta Pelatihan</h5>
        <p class="text-muted mb-0" style="font-size:.85rem">
            @if(isset($pelatihan))
                {{ $pelatihan->nama }}
            @else
                Kelola peserta pelatihan GTK
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.ats.pelatihan.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Daftar
        </a>
        @if(isset($pelatihan))
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPesertaModal">
            <i class="ri-add-line me-1"></i> Tambah Peserta
        </button>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ri-team-line text-indigo me-1"></i> Daftar Peserta</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze">
            <thead>
                <tr>
                    <th>No</th>
                    <th>GTK</th>
                    <th>Pelatihan</th>
                    <th>Status Pendaftaran</th>
                    <th>Sertifikat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pesertas as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-xs rounded-circle bg-indigo-subtle text-indigo d-flex align-items-center justify-content-center fw-bold" style="font-size:.7rem">
                                {{ strtoupper(substr($item->gtk->nama ?? 'G', 0, 1)) }}
                            </div>
                            <span class="fw-medium">{{ $item->gtk->nama ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="text-muted" style="font-size:.85rem">
                        @if($item->pelatihan)
                            {{ $item->pelatihan->nama }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($item->status === 'daftar')
                            <span class="badge bg-secondary-subtle text-secondary">Terdaftar</span>
                        @elseif($item->status === 'diterima')
                            <span class="badge bg-primary-subtle text-primary">Diterima</span>
                        @elseif($item->status === 'ditolak')
                            <span class="badge bg-danger-subtle text-danger">Ditolak</span>
                        @elseif($item->status === 'hadir')
                            <span class="badge bg-success-subtle text-success">Hadir</span>
                        @elseif($item->status === 'tidak_hadir')
                            <span class="badge bg-warning-subtle text-warning">Tidak Hadir</span>
                        @endif
                    </td>
                    <td>
                        @if($item->sertifikat)
                            <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i>Ada</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <form action="{{ route('user.ats.pelatihan.pesertaUpdateStatus', ['userId' => $userId, 'pesertaId' => $item->id, 'status' => 'diterima']) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light" title="Terima">
                                    <i class="ri-check-line text-success"></i>
                                </button>
                            </form>
                            <form action="{{ route('user.ats.pelatihan.pesertaUpdateStatus', ['userId' => $userId, 'pesertaId' => $item->id, 'status' => 'hadir']) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light" title="Tandai Hadir">
                                    <i class="ri-user-follow-line text-primary"></i>
                                </button>
                            </form>
                            <form action="{{ route('user.ats.pelatihan.pesertaHapus', ['userId' => $userId, 'pesertaId' => $item->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus peserta ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="999" class="text-center py-5">
                    <i class="ri-inbox-line" style="font-size:3rem;color:#9ca3af"></i>
                    <h6 class="mt-2 text-muted">Belum ada data</h6>
                    <p class="text-muted" style="font-size:.8rem">Data akan muncul di sini ketika sudah ada.</p>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pesertas->hasPages())
        <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan {{ $pesertas->firstItem() ?? 0 }} - {{ $pesertas->lastItem() ?? 0 }} dari {{ $pesertas->total() }} data</span>
            <nav>{{ $pesertas->appends(request()->query())->links() }}</nav>
        </div>
    @endif
</div>

{{-- Modal: Tambah Peserta --}}
@if(isset($pelatihan))
<div class="modal fade" id="addPesertaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-user-add-line me-1"></i> Tambah Peserta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.ats.pelatihan.pesertaDaftar', ['userId' => $userId, 'pelatihanId' => $pelatihan->id]) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih GTK</label>
                        <select name="gtk_ids[]" class="form-select" multiple size="6" required>
                            @foreach($gtkList as $gtk)
                                <option value="{{ $gtk->id }}">{{ $gtk->nama }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Gunakan Ctrl/Cmd + klik untuk memilih beberapa GTK.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Daftarkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection