@extends('layouts.master')
@section('title') Mata Pelajaran @endsection

@section('css')
<style>
    .group-card { border-left: 4px solid #3b82f6; }
    .group-card.lokal { border-left-color: #10b981; }
    .group-card.pilihan { border-left-color: #f59e0b; }
</style>
@endsection

@section('content')
@php
    $userId = request()->route('userId') ?? Auth::id();
@endphp

@component('components.breadcrumb')
    @slot('li_1') Master Data @endslot
    @slot('title') Mata Pelajaran @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card" id="mataPelajaranList">
            <div class="card-header border-bottom-dashed">
                <div class="row g-3 align-items-center">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">
                            <i class="ri-book-line text-primary me-1"></i>
                            Mata Pelajaran
                        </h5>
                    </div>
                    <div class="col-sm-auto">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMapel">
                            <i class="ri-add-line me-1"></i> Tambah Mapel
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                @forelse($grouped as $group)
                    <div class="border-bottom py-3 px-4 {{ $loop->first ? '' : 'pt-4' }}">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $group['label'] === 'Nasional' ? 'primary' : ($group['label'] === 'Muatan Lokal' ? 'success' : 'warning') }}-subtle text-{{ $group['label'] === 'Nasional' ? 'primary' : ($group['label'] === 'Muatan Lokal' ? 'success' : 'warning') }}">
                                    <i class="ri-group-line me-1"></i>{{ $group['label'] }}
                                </span>
                                <span class="badge bg-dark-subtle text-dark">{{ $group['count'] }} mapel</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:50px;">Kode</th>
                                        <th>Nama Mata Pelajaran</th>
                                        <th style="width:80px;text-align:center;">SKS</th>
                                        <th style="width:80px;text-align:center;">Status</th>
                                        <th style="width:120px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($group['items'] as $subject)
                                        <tr class="{{ $subject->is_active ? '' : 'table-secondary' }}">
                                            <td class="text-center">
                                                <span class="badge bg-dark-subtle text-dark" style="font-size:11px;">{{ $subject->code ?? '—' }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium" style="font-size:14px;">{{ $subject->name }}</div>
                                                @if($subject->description)
                                                    <small class="text-muted">{{ $subject->description }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $subject->credit_hours ?? '—' }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $subject->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                    {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary"
                                                        onclick="editMapel('{{ $subject->id }}', '{{ addslashes($subject->name) }}', '{{ addslashes($subject->code ?? '') }}', '{{ $subject->category }}', '{{ $subject->credit_hours ?? '' }}', '{{ addslashes($subject->description ?? '') }}')">
                                                    <i class="ri-edit-2-line"></i>
                                                </button>
                                                <a href="{{ route('user.schools.master-data.mata-pelajaran.toggle', ['userId' => $userId, 'subjectId' => $subject->id]) }}"
                                                   class="btn btn-sm btn-outline-{{ $subject->is_active ? 'warning' : 'success' }}">
                                                    <i class="ri-{{ $subject->is_active ? 'close-line' : 'check-line' }}"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Belum ada mapel</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-light rounded-circle">
                                <i class="ri-book-line fs-1 text-muted"></i>
                            </div>
                        </div>
                        <h6 class="text-muted">Belum ada mata pelajaran</h6>
                        <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalMapel">
                            <i class="ri-add-line me-1"></i> Tambah Mapel
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah / Edit Mapel --}}
<div class="modal fade" id="modalMapel" tabindex="-1" aria-labelledby="modalMapelLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMapelLabel">
                    <i class="ri-book-line me-1"></i>Tambah Mata Pelajaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.schools.master-data.mata-pelajaran.store', ['userId' => $userId]) }}">
                @csrf
                <input type="hidden" name="id" id="mapel_id">

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="mapel_name" required placeholder="Contoh: Bahasa Arab">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kode</label>
                            <input type="text" class="form-control" name="code" id="mapel_code" placeholder="Contoh: BA">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SKS (Credit Hours)</label>
                            <input type="number" class="form-control" name="credit_hours" id="mapel_credit" min="1" max="10" placeholder="4">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori / Kelompok</label>
                        <select class="form-select" name="category" id="mapel_category">
                            <option value="nasional">Nasional</option>
                            <option value="muatan_lokal">Muatan Lokal</option>
                            <option value="pilihan">Pilihan</option>
                            <option value="lain">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" class="form-control" name="description" id="mapel_desc" placeholder="Deskripsi singkat (opsional)">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="mapel_active" value="1" checked>
                        <label class="form-check-label" for="mapel_active">Mapel aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
function editMapel(id, name, code, category, credit, desc) {
    document.getElementById('mapel_id').value = id;
    document.getElementById('mapel_name').value = name;
    document.getElementById('mapel_code').value = code;
    document.getElementById('mapel_category').value = category;
    document.getElementById('mapel_credit').value = credit;
    document.getElementById('mapel_desc').value = desc;
    document.getElementById('modalMapelLabel').innerHTML = '<i class="ri-edit-2-line me-1"></i>Edit Mata Pelajaran';
    var modal = new bootstrap.Modal(document.getElementById('modalMapel'));
    modal.show();
}

document.getElementById('modalMapel').addEventListener('hidden.bs.modal', function () {
    document.getElementById('mapel_id').value = '';
    document.getElementById('mapel_name').value = '';
    document.getElementById('mapel_code').value = '';
    document.getElementById('mapel_category').value = 'nasional';
    document.getElementById('mapel_credit').value = '';
    document.getElementById('mapel_desc').value = '';
    document.getElementById('mapel_active').checked = true;
    document.getElementById('modalMapelLabel').innerHTML = '<i class="ri-book-line me-1"></i>Tambah Mata Pelajaran';
});
</script>
@endsection