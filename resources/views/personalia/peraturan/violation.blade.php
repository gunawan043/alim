@extends('layouts.master')
@section('title') Pelanggaran GTK @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<style>.stat-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.08);transition:.25s}</style>
@endsection

@section('content')
@php $userId = request()->route('userId') ?? auth()->id(); @endphp

@include('components.personalia-page-header', [
    'title' => 'Catatan Pelanggaran GTK',
    'description' => 'Pencatatan pelanggaran peraturan oleh setiap GTK',
    'icon' => 'ri-alert-line',
    'iconColor' => '#EF4444',
    'breadcrumbs' => [
        ['label' => 'Personalia', 'url' => route('user.dashboard', $userId)],
        ['label' => 'Peraturan', 'url' => route('user.peraturan.index', $userId)],
        ['label' => 'Pelanggaran GTK'],
    ],
    'tabs' => [
        ['label' => 'Dokumen', 'route' => 'user.peraturan.index', 'userId' => $userId],
        ['label' => 'Kategori', 'route' => 'user.peraturan.kategori', 'userId' => $userId],
        ['label' => 'Tipe Pelanggaran', 'route' => 'user.peraturan.pelanggaran', 'userId' => $userId],
        ['label' => 'Catatan GTK', 'route' => 'user.peraturan.violation', 'userId' => $userId, 'active' => true],
    ],
])

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card stat-card border-start border-4 border-secondary">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm"><span class="avatar-title bg-secondary-subtle rounded fs-3"><i class="ri-error-warning-line text-secondary"></i></span></div>
                    <div><small class="text-muted text-uppercase">Aktif</small><h4 class="mb-0">{{ $stats['active_violations'] ?? 0 }}</h4></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="ri-checkbox-line text-success"></i></span></div>
                    <div><small class="text-muted text-uppercase">Tingkat Ringan</small><h4 class="mb-0">{{ $stats['by_tingkat']['ringan'] ?? 0 }}</h4></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="ri-alert-line text-warning"></i></span></div>
                    <div><small class="text-muted text-uppercase">Tingkat Sedang</small><h4 class="mb-0">{{ $stats['by_tingkat']['sedang'] ?? 0 }}</h4></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm"><span class="avatar-title bg-danger-subtle rounded fs-3"><i class="ri-fire-line text-danger"></i></span></div>
                    <div><small class="text-muted text-uppercase">Tingkat Berat</small><h4 class="mb-0">{{ $stats['by_tingkat']['berat'] ?? 0 }}</h4></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1"></i> Daftar Catatan Pelanggaran</h5>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="month" name="bulan" class="form-control form-control-sm" value="{{ request('bulan') }}">
                <button class="btn btn-sm btn-light"><i class="ri-filter-line"></i></button>
            </form>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addViolation">
                <i class="ri-add-line me-1"></i> Catat
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>GTK</th>
                    <th>Judul</th>
                    <th>Jenis</th>
                    <th>Tingkat</th>
                    <th>Sanksi</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($violations as $v)
                <tr>
                    <td>{{ $v->tanggal->format('d/m/Y') }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:30px;height:30px">
                                {{ strtoupper(substr($v->gtk->nama ?? '?', 0, 1)) }}
                            </div>
                            <span class="fw-medium">{{ $v->gtk->nama ?? '-' }}</span>
                        </div>
                    </td>
                    <td><span class="fw-medium">{{ $v->judul }}</span></td>
                    <td>
                        <span class="badge bg-light text-dark">
                            @switch($v->jenis)
                                @case('teguran_lisan') Teguran Lisan @break
                                @case('teguran_tulisan') Teguran Tulisan @break
                                @case('sp1') SP 1 @break
                                @case('sp2') SP 2 @break
                                @case('sp3') SP 3 @break
                                @default {{ $v->jenis }}
                            @endswitch
                        </span>
                    </td>
                    <td>
                        @if($v->tingkat == 'ringan')<span class="badge bg-success-subtle text-success">Ringan</span>
                        @elseif($v->tingkat == 'sedang')<span class="badge bg-warning-subtle text-warning">Sedang</span>
                        @elseif($v->tingkat == 'berat')<span class="badge bg-danger-subtle text-danger">Berat</span>
                        @else<span class="badge bg-secondary">-</span>@endif
                    </td>
                    <td class="text-muted" style="font-size:.85rem;max-width:180px">{{ Str::limit($v->sanksi, 40) ?? '-' }}</td>
                    <td>
                        @if($v->status == 'aktif')<span class="badge bg-danger-subtle text-danger">Aktif</span>
                        @elseif($v->status == 'selesai')<span class="badge bg-success-subtle text-success">Selesai</span>
                        @else<span class="badge bg-secondary">{{ ucfirst($v->status) }}</span>@endif
                    </td>
                    <td class="text-center">
                        <form action="{{ route('user.peraturan.violation.destroy', [$userId, $v->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus catatan pelanggaran ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-5 text-muted">
                    <i class="ri-shield-check-line" style="font-size:3rem;opacity:.4"></i>
                    <h6 class="mt-2">Tidak ada catatan pelanggaran</h6>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($violations->hasPages())
    <div class="card-footer bg-white">
        {{ $violations->links() }}
    </div>
    @endif
</div>

<div class="modal fade" id="addViolation" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('user.peraturan.violation.store', $userId) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-error-warning-line me-1"></i> Catat Pelanggaran</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">GTK <span class="text-danger">*</span></label>
                        <select name="gtk_id" class="form-select" required>
                            <option value="">-- Pilih GTK --</option>
                            @foreach($gtkList as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis <span class="text-danger">*</span></label>
                        <select name="jenis" class="form-select" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="teguran_lisan">Teguran Lisan</option>
                            <option value="teguran_tulisan">Teguran Tulisan</option>
                            <option value="sp1">SP 1</option>
                            <option value="sp2">SP 2</option>
                            <option value="sp3">SP 3</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                        <select name="tingkat" class="form-select" required>
                            <option value="ringan">Ringan</option>
                            <option value="sedang">Sedang</option>
                            <option value="berat">Berat</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" required placeholder="Misal: Terlambat masuk kerja">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="Detail pelanggaran..."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Masa Berlaku (bulan)</label>
                        <input type="number" name="masa_berlaku" class="form-control" min="1" placeholder="6">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Sanksi</label>
                        <textarea name="sanksi" class="form-control" rows="2" placeholder="Sanksi yang diberikan..."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bukti (opsional)</label>
                        <input type="file" name="bukti_path" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                            <option value="diarsipkan">Diarsipkan</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="ri-save-line me-1"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
