@extends('layouts.master')

@section('title', 'Checkpoint Patroli')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2')
            <a href="{{ route('user.keamanan.patrol.index', ['userId' => $userId]) }}">Patroli & Ronda</a>
        @endslot
        @slot('title') Checkpoint @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex flex-wrap gap-2 align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-0">Checkpoint Patroli</h5>
                        <p class="text-muted mb-0 small">Setiap checkpoint memiliki QR yang dipindai petugas saat ronda.</p>
                    </div>
                    <a href="{{ route('user.keamanan.patrol.index', ['userId' => $userId]) }}" class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Jadwal
                    </a>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#checkpointModal">
                        <i class="ri-add-line me-1"></i> Tambah Checkpoint
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Checkpoint</th>
                                    <th>Pos Jaga</th>
                                    <th>Lokasi</th>
                                    <th>Pemindaian</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($checkpoints as $checkpoint)
                                    <tr>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $checkpoint->code }}</span></td>
                                        <td class="fw-semibold">{{ $checkpoint->name }}</td>
                                        <td>{{ $checkpoint->post->name ?? '—' }}</td>
                                        <td>{{ $checkpoint->location ?: '—' }}</td>
                                        <td><span class="badge bg-info-subtle text-info">{{ $checkpoint->logs_count }} kali</span></td>
                                        <td>
                                            @if($checkpoint->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-qr"
                                                                data-name="{{ $checkpoint->name }}"
                                                                data-url="{{ route('user.keamanan.patrol.checkpoints.qr', ['userId' => $userId, 'checkpoint' => $checkpoint->id]) }}"
                                                                data-scan="{{ $checkpoint->scanUrl() }}">
                                                            <i class="ri-qr-code-line text-primary me-2"></i> Lihat QR
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-edit"
                                                                data-id="{{ $checkpoint->id }}"
                                                                data-code="{{ $checkpoint->code }}"
                                                                data-name="{{ $checkpoint->name }}"
                                                                data-post="{{ $checkpoint->post_id }}"
                                                                data-location="{{ $checkpoint->location }}"
                                                                data-notes="{{ $checkpoint->notes }}"
                                                                data-active="{{ $checkpoint->is_active ? 1 : 0 }}"
                                                                data-url="{{ route('user.keamanan.patrol.checkpoints.update', ['userId' => $userId, 'checkpoint' => $checkpoint->id]) }}">
                                                            <i class="ri-pencil-line text-secondary me-2"></i> Edit
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('user.keamanan.patrol.checkpoints.destroy', ['userId' => $userId, 'checkpoint' => $checkpoint->id]) }}"
                                                              data-confirm="Hapus checkpoint {{ $checkpoint->name }}? Riwayat pemindaiannya juga terhapus.">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="ri-delete-bin-line me-2"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="7" title="Belum ada checkpoint"
                                        message="Tambahkan titik pemeriksaan yang akan dipindai saat ronda.">
                                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#checkpointModal">
                                            <i class="ri-add-line me-1"></i> Tambah Checkpoint
                                        </button>
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH --}}
    <div class="modal fade" id="checkpointModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-map-pin-add-line me-2 text-primary"></i> Tambah Checkpoint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.keamanan.patrol.checkpoints.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="CP-01" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Gerbang Utama" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pos Jaga</label>
                                <select name="post_id" class="form-select">
                                    <option value="">— Semua pos —</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}" {{ old('post_id') === $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div class="modal fade" id="checkpointEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-pencil-line me-2 text-primary"></i> Edit Checkpoint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="checkpointEditForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="editCode" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="editName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pos Jaga</label>
                                <select name="post_id" id="editPost" class="form-select">
                                    <option value="">— Semua pos —</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}">{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="location" id="editLocation" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive">
                                    <label class="form-check-label" for="editActive">Checkpoint aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL QR --}}
    <div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-qr-code-line me-2 text-primary"></i> QR Checkpoint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 id="qrName" class="mb-3"></h6>
                    <img id="qrImage" src="" alt="QR Checkpoint" style="width: 240px; height: 240px;">
                    <p class="small text-muted mt-3 mb-1">Tautan pemindaian:</p>
                    <code id="qrScanUrl" class="small d-block text-break"></code>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    <a id="qrOpen" href="#" target="_blank" class="btn btn-primary">
                        <i class="ri-external-link-line me-1"></i> Buka Halaman Scan
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('checkpointEditForm').action = this.dataset.url;
            document.getElementById('editCode').value = this.dataset.code || '';
            document.getElementById('editName').value = this.dataset.name || '';
            document.getElementById('editPost').value = this.dataset.post || '';
            document.getElementById('editLocation').value = this.dataset.location || '';
            document.getElementById('editNotes').value = this.dataset.notes || '';
            document.getElementById('editActive').checked = this.dataset.active === '1';
            new bootstrap.Modal(document.getElementById('checkpointEditModal')).show();
        });
    });

    document.querySelectorAll('.btn-qr').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('qrName').textContent = this.dataset.name;
            document.getElementById('qrImage').src = this.dataset.url;
            document.getElementById('qrScanUrl').textContent = this.dataset.scan;
            document.getElementById('qrOpen').href = this.dataset.scan;
            new bootstrap.Modal(document.getElementById('qrModal')).show();
        });
    });
});
</script>
@endpush
