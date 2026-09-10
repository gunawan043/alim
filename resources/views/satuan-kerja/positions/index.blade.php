@extends('layouts.master')
@section('title') Manajemen Jabatan — {{ $workUnit->name }} @endsection

@section('css')
<style>
    .table-freeze th:first-child,
    .table-freeze td:first-child {
        position: sticky;
        left: 0;
        z-index: 100;
        min-width: 44px;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        background: inherit;
    }
    .table-freeze th {
        position: sticky;
        top: 0;
        z-index: 20;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        background: #f8f9fa;
    }
    [data-bs-theme="dark"] .table-freeze th {
        background: #1e293b;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Satuan Kerja @endslot
        @slot('li_2') {{ $workUnit->name }} @endslot
        @slot('title') Manajemen Jabatan GTK @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Manajemen Jabatan GTK</h5>
                            <p class="text-muted mb-0">Satuan Kerja: <strong>{{ $workUnit->name }}</strong></p>
                        </div>
                        <div class="col-sm-auto d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" id="massUpdateBtn"
                                data-bs-toggle="modal" data-bs-target="#massUpdateModal"
                                @if($orders->isEmpty()) disabled @endif>
                                <i class="ri-magic-line me-1"></i>Ubah Massal
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Cari nama GTK..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="jabatan_id" class="form-control">
                                <option value="">Semua Jabatan</option>
                                @foreach($jabatans as $j)
                                    <option value="{{ $j->id }}" {{ request('jabatan_id')===$j->id ? 'selected' : '' }}>
                                        {{ $j->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.satuan-kerja.positions', ['workUnitId' => $workUnitId, 'userId' => $userId]) }}"
                               class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle table-freeze">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>#</th>
                                    <th>Nama GTK</th>
                                    <th>Jabatan</th>
                                    <th>Jenis GTK</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $i => $order)
                                <tr>
                                    <td><input type="checkbox" class="row-checkbox" value="{{ $order->id }}"></td>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <span class="fw-medium">{{ $order->name }}</span>
                                        <br><small class="text-muted">{{ $order->email }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ $order->employment->jabatan ?? '<span class="text-muted">-</span>' }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $order->employment->jenis_gtk ?? '-' }}</td>
                                    <td>
                                        @if($order->employment?->status_kepegawaian)
                                            <span class="badge bg-soft-secondary">{{ $order->employment->status_kepegawaian }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-soft-primary btn-sm edit-btn"
                                            data-user-id="{{ $order->id }}"
                                            data-user-name="{{ $order->name }}"
                                            data-current-jabatan="{{ $order->employment->jabatan ?? '' }}"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="ri-folder-open-line fs-1 d-block mb-2"></i>
                                        Belum ada GTK di satuan kerja ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Jabatan GTK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label">GTK</label>
                        <input type="text" class="form-control" id="editUserName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan Saat Ini</label>
                        <input type="text" class="form-control" id="editCurrentJabatan" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan Baru <span class="text-danger">*</span></label>
                        <select name="jabatan_id" id="editJabatanId" class="form-select">
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach($jabatans as $j)
                                <option value="{{ $j->id }}" data-name="{{ $j->name }}">{{ $j->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="editError" class="text-danger small" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="savePosition">
                        <i class="ri-check-line me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Mass Update Modal --}}
    <div class="modal fade" id="massUpdateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Jabatan Massal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">GTK yang terpilih akan diubah jabatannya sekaligus.</p>
                    <div class="mb-3">
                        <label class="form-label">Jabatan Baru <span class="text-danger">*</span></label>
                        <select name="jabatan_id" id="massJabatanId" class="form-select">
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach($jabatans as $j)
                                <option value="{{ $j->id }}" data-name="{{ $j->name }}">{{ $j->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="massError" class="text-danger small" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="saveMassPosition">
                        <i class="ri-check-line me-1"></i> Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.dataset.userId;
            const userName = button.dataset.userName;
            const currentJabatan = button.dataset.currentJabatan;
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUserName').value = userName;
            document.getElementById('editCurrentJabatan').value = currentJabatan || '-';
            document.getElementById('editJabatanId').value = '';
            document.getElementById('editError').style.display = 'none';
        });

        document.getElementById('savePosition').addEventListener('click', async function() {
            const userId = document.getElementById('editUserId').value;
            const jabatanId = document.getElementById('editJabatanId').value;
            const errorEl = document.getElementById('editError');

            if (!jabatanId) {
                errorEl.textContent = 'Pilih jabatan terlebih dahulu.';
                errorEl.style.display = 'block';
                return;
            }

            errorEl.style.display = 'none';
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

            try {
                const res = await fetch(`{{ route('user.satuan-kerja.positions.update', ['workUnitId' => $workUnitId, 'userId' => $userId]) }}/${userId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ jabatan_id: jabatanId })
                });
                const data = await res.json();
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(editModal);
                    modal.hide();
                    location.reload();
                } else {
                    errorEl.textContent = data.message || 'Gagal memperbarui jabatan.';
                    errorEl.style.display = 'block';
                }
            } catch (e) {
                errorEl.textContent = 'Terjadi kesalahan sistem.';
                errorEl.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-check-line me-1"></i> Simpan';
            }
        });

        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        const massModal = document.getElementById('massUpdateModal');
        document.getElementById('saveMassPosition').addEventListener('click', async function() {
            const checked = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            const jabatanId = document.getElementById('massJabatanId').value;
            const errorEl = document.getElementById('massError');

            if (checked.length === 0) {
                errorEl.textContent = 'Pilih minimal satu GTK.';
                errorEl.style.display = 'block';
                return;
            }
            if (!jabatanId) {
                errorEl.textContent = 'Pilih jabatan terlebih dahulu.';
                errorEl.style.display = 'block';
                return;
            }

            errorEl.style.display = 'none';
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

            try {
                const res = await fetch(`{{ route('user.satuan-kerja.positions.mass-update', ['workUnitId' => $workUnitId, 'userId' => $userId]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ ids: checked, jabatan_id: jabatanId })
                });
                const data = await res.json();
                if (data.success) {
                    massModal.classList.remove('show');
                    massModal.style.display = '';
                    location.reload();
                } else {
                    errorEl.textContent = data.message || 'Gagal memperbarui jabatan.';
                    errorEl.style.display = 'block';
                }
            } catch (e) {
                errorEl.textContent = 'Terjadi kesalahan sistem.';
                errorEl.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-check-line me-1"></i> Terapkan';
            }
        });
    });
    </script>
@endsection
