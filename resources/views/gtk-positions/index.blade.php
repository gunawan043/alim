@extends('layouts.master')
@section('title') Manajemen Jabatan GTK @endsection

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
        @slot('li_1') GTK @endslot
        @slot('li_2') Manajemen Jabatan @endslot
        @slot('title') Atur Jabatan GTK @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Manajemen Jabatan GTK</h5>
                            <p class="text-muted mb-0">Daftar seluruh GTK beserta jabatan yang dapat diatur</p>
                        </div>
                        <div class="col-sm-auto d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" id="massUpdateBtn"
                                data-bs-toggle="modal" data-bs-target="#massUpdateModal"
                                @if($gtks->isEmpty()) disabled @endif>
                                <i class="ri-magic-line me-1"></i>Ubah Massal
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Cari nama GTK..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="jenis_gtk" class="form-control">
                                <option value="">Semua Jenis GTK</option>
                                <option value="Tenaga Pendidik Pondok" {{ request('jenis_gtk')=='Tenaga Pendidik Pondok' ? 'selected' : '' }}>Tenaga Pendidik Pondok</option>
                                <option value="Guru" {{ request('jenis_gtk')=='Guru' ? 'selected' : '' }}>Guru</option>
                                <option value="Tendik" {{ request('jenis_gtk')=='Tendik' ? 'selected' : '' }}>Tendik</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="jabatan_id" class="form-control">
                                <option value="">Semua Jabatan</option>
                                @foreach($jabatans as $jabatan)
                                    <option value="{{ $jabatan->id }}" {{ request('jabatan_id')==$jabatan->id ? 'selected' : '' }}>
                                        {{ $jabatan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status_kepegawaian" class="form-control">
                                <option value="">Semua Status</option>
                                @foreach(\App\Models\GtkEmployment::STATUS_LABELS as $key => $label)
                                    <option value="{{ $key }}" {{ request('status_kepegawaian')==$key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('user.gtk-positions.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>

                    <div class="massal-selected-banner" id="selectedBanner" style="display:none;">
                        <div class="alert alert-info d-flex align-items-center justify-content-between mb-3">
                            <span><i class="ri-checkbox-multiple-line me-1"></i>
                                <strong id="selectedCount">0</strong> GTK dipilih</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelection">Hapus Pilih</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-freeze table-hover align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                    <th>#</th>
                                    <th>GTK</th>
                                    <th>NIP</th>
                                    <th>Jenis GTK</th>
                                    <th>Jabatan Saat Ini</th>
                                    <th>Status Kepegawaian</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gtks as $gtk)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $gtk->id }}"></td>
                                        <td>{{ $loop->iteration + ($gtks->currentPage() - 1) * $gtks->perPage() }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($gtk->gtkProfile?->profile_photo)
                                                    <img src="{{ $gtk->gtkProfile->profile_photo }}" alt="" width="32" height="32" class="rounded-circle">
                                                @endif
                                                <div>
                                                    <span class="fw-medium">{{ $gtk->name }}</span>
                                                    <br><small class="text-muted">{{ $gtk->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><small class="text-muted">{{ $gtk->gtkProfile?->nik ?? '-' }}</small></td>
                                        <td><span class="badge bg-soft-primary text-primary">{{ $gtk->employment?->jenis_gtk ?? '-' }}</span></td>
                                        <td>
                                            <span class="position-current">{{ $gtk->employment?->jabatan ?? '<span class="text-muted">Belum ada</span>' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-soft-{{ $gtk->employment?->status_kepegawaian == 'PTY' || $gtk->employment?->status_kepegawaian == 'GTY' ? 'success' : 'secondary' }} text-{{ $gtk->employment?->status_kepegawaian == 'PTY' || $gtk->employment?->status_kepegawaian == 'GTY' ? 'success' : 'secondary' }}">
                                                {{ $gtk->employment?->status_kepegawaian_text ?? $gtk->employment?->status_kepegawaian ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-light btn-edit-position"
                                                data-user-id="{{ $gtk->id }}"
                                                data-current-jabatan="{{ $gtk->employment?->jabatan_id ?? '' }}"
                                                data-current-name="{{ $gtk->employment?->jabatan ?? '' }}"
                                                data-bs-toggle="modal" data-bs-target="#editPositionModal">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                                            Belum ada data GTK
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $gtks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Position Modal --}}
    <div class="modal fade" id="editPositionModal" tabindex="-1" aria-hidden="true">
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
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editPositionModal');
    const massModal = document.getElementById('massUpdateModal');

    document.querySelectorAll('.btn-edit-position').forEach(btn => {
        btn.addEventListener('click', () => {
            const userId = btn.dataset.userId;
            const currentName = btn.dataset.currentName;
            const currentJabatan = btn.dataset.currentJabatan;

            document.getElementById('editUserId').value = userId;
            document.getElementById('editUserName').value = currentName || '-';
            document.getElementById('editCurrentJabatan').value = currentName || '(belum ada)';
            document.getElementById('editJabatanId').value = currentJabatan || '';
            document.getElementById('editError').style.display = 'none';
        });
    });

    document.getElementById('savePosition').addEventListener('click', async () => {
        const userId = document.getElementById('editUserId').value;
        const jabatanId = document.getElementById('editJabatanId').value;
        const errorEl = document.getElementById('editError');

        if (!jabatanId) {
            errorEl.textContent = 'Pilih jabatan terlebih dahulu.';
            errorEl.style.display = 'block';
            return;
        }

        errorEl.style.display = 'none';
        const btn = document.getElementById('savePosition');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        try {
            const res = await fetch(`{{ route('user.gtk-positions.update', ['userId' => $userId, 'id' => '__ID__']) }}`.replace('__ID__', userId), {
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
                editModal.classList.remove('show');
                editModal.style.display = '';
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

    // Select all checkboxes
    document.getElementById('selectAll').addEventListener('change', (e) => {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked);
        updateSelectedBanner();
    });

    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedBanner);
    });

    function updateSelectedBanner() {
        const checked = document.querySelectorAll('.row-checkbox:checked').length;
        const banner = document.getElementById('selectedBanner');
        const count = document.getElementById('selectedCount');
        if (checked > 0) {
            banner.style.display = 'block';
            count.textContent = checked;
        } else {
            banner.style.display = 'none';
        }
    }

    document.getElementById('clearSelection').addEventListener('click', () => {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateSelectedBanner();
    });

    document.getElementById('saveMassPosition').addEventListener('click', async () => {
        const checked = [...document.querySelectorAll('.row-checkbox:checked')].map(cb => cb.value);
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
        const btn = document.getElementById('saveMassPosition');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        try {
            const res = await fetch(`{{ route('user.gtk-positions.mass-update', ['userId' => $userId]) }}`, {
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
