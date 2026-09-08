@extends('layouts.master')
@section('title')
    @lang('Manajemen Massal GTK')
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .table-container {
            position: relative;
            width: 100%;
            overflow-x: auto;
        }
        .table-freeze {
            table-layout: auto;
            min-width: max-content;
            margin-bottom: 0;
            width: 100%;
        }
        .table-freeze th,
        .table-freeze td {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            vertical-align: middle;
            padding: 10px 14px;
            word-break: break-word;
        }
        .table-freeze th:first-child,
        .table-freeze td:first-child {
            position: sticky;
            left: 0;
            z-index: 100;
            min-width: 44px;
            max-width: 44px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            background: inherit;
        }
        .table-freeze thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            background: #f8f9fa;
        }
        .col-hidden {
            display: none !important;
        }
        .filter-group {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
        }
        .filter-group-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-group-title i {
            color: #0a5f9e;
            font-size: 16px;
        }
        .massal-selected-banner {
            position: sticky;
            top: 0;
            z-index: 50;
        }
    </style>
@endsection
@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
    @endphp
    @component('components.breadcrumb')
        @slot('li_1') Data GTK @endslot
        @slot('li_2') <a href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}">Guru</a> @endslot
        @slot('title') Manajemen Massal @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger m-3">{{ session('error') }}</div>
    @endif

    {{-- Mass Update Modal --}}
    <div class="modal fade" id="massUpdateModal" tabindex="-1" aria-labelledby="massUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="massUpdateModalLabel">
                        <i class="ri-edit-fill me-2 text-primary"></i>Update Massal GTK
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="ri-information-line me-2 fs-5"></i>
                        <div>
                            <strong>{{ $gtkList->total() }}</strong> GTK ditemukan dari filter.<br>
                            <small class="text-muted">Centang GTK yang ingin diupdate, lalu isi field di bawah ini. Field yang tidak diisi tidak akan mengubah data.</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Status Kepegawaian</label>
                            <select class="form-select" id="massStatusKepegawaian">
                                <option value="">— Biarkan —</option>
                                <option value="PTT">PTT</option>
                                <option value="PTY">PTY</option>
                                <option value="GTT">GTT</option>
                                <option value="GTY">GTY</option>
                                <option value="KONTRAK">Kontrak</option>
                                <option value="Percobaan">Percobaan</option>
                                <option value="Magang">Magang</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis GTK</label>
                            <select class="form-select" id="massJenisGtk">
                                <option value="">— Biarkan —</option>
                                @foreach($jenisGtk as $j)
                                    <option value="{{ $j->id }}">{{ $j->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jabatan</label>
                            <select class="form-select" id="massJabatan">
                                <option value="">— Biarkan —</option>
                                @foreach($jabatan as $j)
                                    <option value="{{ $j->id }}">{{ $j->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TMT (Tanggal Mulai Tugas)</label>
                            <input type="date" class="form-control" id="massTmt">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor SK</label>
                            <input type="text" class="form-control" id="massNomorSk" placeholder="Contoh: 123/SK/2024">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal SK</label>
                            <input type="date" class="form-control" id="massTanggalSk">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pangkat/Golongan</label>
                            <input type="text" class="form-control" id="massPangkat" placeholder="III/a, dst">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Satuan Kerja</label>
                            <select class="form-select" id="massWorkUnit">
                                <option value="">— Biarkan —</option>
                                @foreach($workUnits as $wu)
                                    <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="confirmMassUpdate">
                        <i class="ri-save-fill me-1"></i> Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">
                                <i class="ri-edit-box-line text-primary me-2"></i>Manajemen Massal GTK
                            </h5>
                            <p class="text-muted mb-0 small">
                                Centang GTK yang ingin diupdate, lalu klik <strong>Update Massal</strong>.
                                Total: <span class="fw-semibold">{{ $gtkList->total() }}</span> GTK
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                <i class="ri-filter-3-line me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filter Collapse --}}
                <div class="collapse" id="filterCollapse">
                    <div class="card-body border-bottom">
                        <form method="GET" action="{{ route('user.gtk.massal', ['userId' => $userId]) }}" id="filterForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small">Pencarian</label>
                                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Nama, Email, NIK...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Status Kepegawaian</label>
                                    <select class="form-select form-select-sm" name="status_kepegawaian">
                                        <option value="">Semua</option>
                                        <option value="PTT" {{ request('status_kepegawaian') == 'PTT' ? 'selected' : '' }}>PTT</option>
                                        <option value="PTY" {{ request('status_kepegawaian') == 'PTY' ? 'selected' : '' }}>PTY</option>
                                        <option value="GTT" {{ request('status_kepegawaian') == 'GTT' ? 'selected' : '' }}>GTT</option>
                                        <option value="GTY" {{ request('status_kepegawaian') == 'GTY' ? 'selected' : '' }}>GTY</option>
                                        <option value="Tetap" {{ request('status_kepegawaian') == 'Tetap' ? 'selected' : '' }}>Tetap</option>
                                        <option value="KONTRAK" {{ request('status_kepegawaian') == 'KONTRAK' ? 'selected' : '' }}>Kontrak</option>
                                        <option value="Percobaan" {{ request('status_kepegawaian') == 'Percobaan' ? 'selected' : '' }}>Percobaan</option>
                                        <option value="Magang" {{ request('status_kepegawaian') == 'Magang' ? 'selected' : '' }}>Magang</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Status Aktif</label>
                                    <select class="form-select form-select-sm" name="status_aktif">
                                        <option value="">Semua</option>
                                        <option value="1" {{ request('status_aktif') == '1' ? 'selected' : '' }}>Aktif</option>
                                        <option value="0" {{ request('status_aktif') == '0' ? 'selected' : '' }}>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Satuan Kerja</label>
                                    <select class="form-select form-select-sm" name="satuan_kerja">
                                        <option value="">Semua</option>
                                        @foreach($workUnits as $wu)
                                            <option value="{{ $wu->id }}" {{ request('satuan_kerja') == $wu->id ? 'selected' : '' }}>{{ $wu->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="ri-search-line me-1"></i>Terapkan
                                    </button>
                                    <a href="{{ route('user.gtk.massal', ['userId' => $userId]) }}" class="btn btn-sm btn-light">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Bulk Action Bar (sticky) --}}
                <div id="bulkActionBar" class="px-3 py-2 bg-primary-subtle border-bottom d-none align-items-center gap-3 massal-selected-banner">
                    <span class="badge bg-primary" id="bulkSelectedCount">0</span>
                    <span class="text-muted small">GTK terpilih</span>
                    <div class="vr"></div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#massUpdateModal">
                        <i class="ri-edit-fill me-1"></i>Update Massal
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="bulkClearBtn">
                        <i class="ri-close-circle-line me-1"></i>Hapus Pilihan
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="selectAllBtn">
                        <i class="ri-checkbox-multiple-line me-1"></i>Pilih Semua
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze" id="massalTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36px;min-width:36px;" class="text-center">
                                        <input type="checkbox" id="bulkSelectAll" class="form-check-input" style="cursor:pointer;">
                                    </th>
                                    <th>Nama GTK</th>
                                    <th>Email</th>
                                    <th>No HP</th>
                                    <th>Jabatan</th>
                                    <th>Status</th>
                                    <th>TMT</th>
                                    <th>Satuan Kerja</th>
                                    <th>Aktif</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                @forelse($gtkList as $gtk)
                                    <tr data-gtk-id="{{ $gtk->id }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input bulk-gtk-select" value="{{ $gtk->id }}" data-name="{{ $gtk->name }}" style="cursor:pointer;">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="avatar-xs">
                                                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle" style="width:32px;height:32px;font-size:12px;">
                                                            {{ strtoupper(substr($gtk->name, 0, 1)) }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    <a href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $gtk->id]) }}" class="text-reset fw-semibold">
                                                        {{ $gtk->name }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td><a href="mailto:{{ $gtk->email }}" class="text-reset">{{ $gtk->email }}</a></td>
                                        <td>{{ $gtk->gtkContact?->no_hp ?? '-' }}</td>
                                        <td>{{ $gtk->employment?->jabatan ?? '-' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match ($gtk->employment?->status_kepegawaian) {
                                                    'GTT', 'PTT' => 'success',
                                                    'GTY', 'PTY' => 'secondary',
                                                    'Tetap' => 'primary',
                                                    'KONTRAK' => 'warning',
                                                    default => 'light',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                                {{ $gtk->employment?->status_kepegawaian ?? '-' }}
                                            </span>
                                        </td>
                                        <td>{{ $gtk->employment?->tmt ? \Carbon\Carbon::parse($gtk->employment->tmt)->format('d/m/Y') : '-' }}</td>
                                        <td>
                                            @if($gtk->gtkWorkUnits->isNotEmpty())
                                                @foreach($gtk->gtkWorkUnits as $gu)
                                                    @php $wu = \App\Models\WorkUnit::find($gu->work_unit_id); @endphp
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $wu->name ?? 'N/A' }}</span>
                                                @endforeach
                                            @else - @endif
                                        </td>
                                        <td>
                                            @if($gtk->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                                colors="primary:#121331,secondary:#08a88a" style="width:60px;height:60px"></lord-icon>
                                            <h5 class="mt-2 text-muted">Tidak ada GTK ditemukan</h5>
                                            <p class="text-muted small">Ubah filter pencarian untuk menemukan GTK</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($gtkList->hasPages())
                    <div class="card-footer bg-transparent border-top-0 py-3">
                        {{ $gtkList->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    function escHtml(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

    document.addEventListener('DOMContentLoaded', function () {
        const bulkSelectAll = document.getElementById('bulkSelectAll');
        const bulkRows      = document.querySelectorAll('.bulk-gtk-select');
        const actionBar     = document.getElementById('bulkActionBar');
        const countEl       = document.getElementById('bulkSelectedCount');
        const selectAllBtn  = document.getElementById('selectAllBtn');

        function refreshBulkBar() {
            const checked = document.querySelectorAll('.bulk-gtk-select:checked');
            countEl.textContent = checked.length;
            if (checked.length > 0) {
                actionBar.classList.remove('d-none');
                actionBar.classList.add('d-flex');
            } else {
                actionBar.classList.add('d-none');
                actionBar.classList.remove('d-flex');
            }
            if (bulkSelectAll) {
                bulkSelectAll.checked = checked.length === bulkRows.length && bulkRows.length > 0;
            }
            bulkRows.forEach(cb => {
                const tr = cb.closest('tr');
                if (tr) tr.classList.toggle('table-primary', cb.checked);
            });
        }

        if (bulkSelectAll) {
            bulkSelectAll.addEventListener('change', function () {
                bulkRows.forEach(cb => {
                    cb.checked = this.checked;
                    const tr = cb.closest('tr');
                    if (tr) tr.classList.toggle('table-primary', this.checked);
                });
                refreshBulkBar();
            });
        }
        bulkRows.forEach(cb => {
            cb.addEventListener('change', refreshBulkBar);
        });

        const bulkClearBtn = document.getElementById('bulkClearBtn');
        if (bulkClearBtn) {
            bulkClearBtn.addEventListener('click', function () {
                bulkRows.forEach(cb => {
                    cb.checked = false;
                    const tr = cb.closest('tr');
                    if (tr) tr.classList.remove('table-primary');
                });
                if (bulkSelectAll) bulkSelectAll.checked = false;
                refreshBulkBar();
            });
        }

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                const allChecked = Array.from(bulkRows).every(cb => cb.checked);
                bulkRows.forEach(cb => {
                    cb.checked = !allChecked;
                    const tr = cb.closest('tr');
                    if (tr) tr.classList.toggle('table-primary', !allChecked);
                });
                if (bulkSelectAll) bulkSelectAll.checked = !allChecked;
                refreshBulkBar();
            });
        }

        // Load jabatan when jenis_gtk changes in modal
        const massJenisSelect = document.getElementById('massJenisGtk');
        const massJabatanSelect = document.getElementById('massJabatan');
        if (massJenisSelect && massJabatanSelect) {
            massJenisSelect.addEventListener('change', async function () {
                const val = this.value;
                massJabatanSelect.innerHTML = '<option value="">— Biarkan —</option>';
                if (!val) return;
                try {
                    const res = await fetch('{{ "/$userId" }}/master-data/jabatan-by-jenis?jenis_gtk_id=' + val);
                    const data = await res.json();
                    (data.data || []).forEach(j => {
                        massJabatanSelect.innerHTML += `<option value="${escHtml(j.id)}">${escHtml(j.name)}</option>`;
                    });
                } catch(e) { console.error(e); }
            });
        }

        document.getElementById('confirmMassUpdate').addEventListener('click', async function () {
            const checked = document.querySelectorAll('.bulk-gtk-select:checked');
            if (!checked.length) {
                Swal.fire('Perhatian', 'Pilih GTK terlebih dahulu', 'warning');
                return;
            }
            const ids = Array.from(checked).map(cb => cb.value);
            const statusKepegawaian = document.getElementById('massStatusKepegawaian').value || null;
            const jenisGtkId = document.getElementById('massJenisGtk').value || null;
            const jabatanId = document.getElementById('massJabatan').value || null;
            const tmt = document.getElementById('massTmt').value || null;
            const nomorSk = document.getElementById('massNomorSk').value || null;
            const tanggalSk = document.getElementById('massTanggalSk').value || null;
            const pangkat = document.getElementById('massPangkat').value || null;
            const workUnitId = document.getElementById('massWorkUnit').value || null;

            const hasChanges = statusKepegawaian || jenisGtkId || jabatanId || tmt || nomorSk || tanggalSk || pangkat || workUnitId;
            if (!hasChanges) {
                Swal.fire('Perhatian', 'Pilih minimal satu field untuk diupdate', 'warning');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> Memproses...';

            try {
                const res = await fetch('{{ route("user.gtk.mass-update", ["userId" => $userId]) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        ids,
                        status_kepegawaian: statusKepegawaian,
                        jenis_gtk_id: jenisGtkId,
                        jabatan_id: jabatanId,
                        tmt,
                        nomor_sk: nomorSk,
                        tanggal_sk: tanggalSk,
                        pangkat_golongan: pangkat,
                        work_unit_id: workUnitId,
                    })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message, timer: 1500, showConfirmButton: false });
                    bootstrap.Modal.getInstance(document.getElementById('massUpdateModal')).hide();
                    setTimeout(() => window.location.reload(), 1600);
                } else {
                    Swal.fire('Error', data.message || 'Terjadi kesalahan', 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="ri-save-fill me-1"></i> Terapkan';
            }
        });
    });
    </script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
