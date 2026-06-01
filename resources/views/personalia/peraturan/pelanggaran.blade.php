@extends('layouts.master')
@section('title') Master Pelanggaran @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<style>.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); transition: .25s; }</style>
@endsection

@section('content')
@php
$userId = request()->route('userId') ?? auth()->id();
@endphp

@include('components.personalia-page-header', [
    'title' => 'Master Tipe Pelanggaran',
    'description' => 'Daftar jenis pelanggaran dan poin sanksi yang berlaku',
    'icon' => 'ri-alert-line',
    'iconColor' => '#EF4444',
    'breadcrumbs' => [
        ['label' => 'Personalia', 'url' => route('user.dashboard', $userId)],
        ['label' => 'Peraturan', 'url' => route('user.peraturan.index', $userId)],
        ['label' => 'Pelanggaran'],
    ],
    'tabs' => [
        ['label' => 'Dokumen', 'route' => 'user.peraturan.index', 'userId' => $userId],
        ['label' => 'Kategori', 'route' => 'user.peraturan.kategori', 'userId' => $userId],
        ['label' => 'Tipe Pelanggaran', 'route' => 'user.peraturan.pelanggaran', 'userId' => $userId, 'active' => true],
        ['label' => 'Catatan GTK', 'route' => 'user.peraturan.violation', 'userId' => $userId],
    ],
])

<div class="row g-3 mb-3">
    @php
        $byJenis = $items->groupBy('jenis');
    @endphp
    <div class="col-md-4">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="ri-checkbox-line text-success"></i></span></div>
                    <div><small class="text-muted">Pelanggaran Ringan</small><h4 class="mb-0">{{ $byJenis['ringan']->count() ?? 0 }}</h4></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="ri-error-warning-line text-warning"></i></span></div>
                    <div><small class="text-muted">Pelanggaran Sedang</small><h4 class="mb-0">{{ $byJenis['sedang']->count() ?? 0 }}</h4></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm"><span class="avatar-title bg-danger-subtle rounded fs-3"><i class="ri-close-circle-line text-danger"></i></span></div>
                    <div><small class="text-muted">Pelanggaran Berat</small><h4 class="mb-0">{{ $byJenis['berat']->count() ?? 0 }}</h4></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-list-check me-1"></i> Daftar Tipe Pelanggaran</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Pelanggaran</th>
                                <th>Jenis</th>
                                <th>Poin</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $i)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $i->nama }}</div>
                                    <small class="text-muted">{{ Str::limit($i->deskripsi, 60) }}</small>
                                </td>
                                <td>
                                    @if($i->jenis == 'ringan')<span class="badge bg-success-subtle text-success">Ringan</span>
                                    @elseif($i->jenis == 'sedang')<span class="badge bg-warning-subtle text-warning">Sedang</span>
                                    @else<span class="badge bg-danger-subtle text-danger">Berat</span>@endif
                                </td>
                                <td><span class="fw-semibold">{{ $i->poin }}</span></td>
                                <td>
                                    @if($i->is_active)<span class="badge bg-primary-subtle text-primary">Aktif</span>
                                    @else<span class="badge bg-secondary">Nonaktif</span>@endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-soft-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editP{{ $i->id }}"><i class="ri-edit-2-line"></i></button>
                                    <form action="{{ route('user.peraturan.pelanggaran.destroy', [$userId, $i->id]) }}" method="POST" class="d-inline">@csrf @method('DELETE')
                                        <button class="btn btn-soft-danger btn-sm" onclick="return confirm('Hapus tipe pelanggaran ini?')"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <div class="modal fade" id="editP{{ $i->id }}">
                                <div class="modal-dialog">
                                    <form action="{{ route('user.peraturan.pelanggaran.update', [$userId, $i->id]) }}" method="POST" class="modal-content">
                                        @csrf @method('PUT')
                                        <div class="modal-header"><h5 class="modal-title">Edit Tipe Pelanggaran</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" value="{{ $i->nama }}" required></div>
                                            <div class="mb-2"><label class="form-label">Jenis</label>
                                                <select name="jenis" class="form-select">
                                                    <option value="ringan" {{ $i->jenis=='ringan'?'selected':'' }}>Ringan</option>
                                                    <option value="sedang" {{ $i->jenis=='sedang'?'selected':'' }}>Sedang</option>
                                                    <option value="berat" {{ $i->jenis=='berat'?'selected':'' }}>Berat</option>
                                                </select>
                                            </div>
                                            <div class="mb-2"><label class="form-label">Poin</label><input type="number" name="poin" class="form-control" value="{{ $i->poin }}" min="0" required></div>
                                            <div class="mb-2"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2">{{ $i->deskripsi }}</textarea></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $i->is_active?'checked':'' }} id="act{{ $i->id }}"><label class="form-check-label" for="act{{ $i->id }}">Aktif</label></div>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada tipe pelanggaran. Tambahkan di samping.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-add-line me-1"></i> Tambah Tipe Pelanggaran</h5></div>
            <div class="card-body">
                <form action="{{ route('user.peraturan.pelanggaran.store', $userId) }}" method="POST">
                    @csrf
                    <div class="mb-2"><label class="form-label">Nama <span class="text-danger">*</span></label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Jenis <span class="text-danger">*</span></label>
                        <select name="jenis" class="form-select" required>
                            <option value="ringan">Ringan</option>
                            <option value="sedang">Sedang</option>
                            <option value="berat">Berat</option>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Poin Sanksi <span class="text-danger">*</span></label><input type="number" name="poin" class="form-control" min="0" required></div>
                    <div class="mb-2"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2"></textarea></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="actNew"><label class="form-check-label" for="actNew">Aktif</label></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-save-line me-1"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
