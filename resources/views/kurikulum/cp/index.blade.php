@extends('layouts.master')
@section('title') Capaian Pembelajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') CP @endslot
        @slot('title') Capaian Pembelajaran @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Capaian Pembelajaran (CP)</h4>
                <p class="text-muted small mb-0">Target kompetensi per mapel &amp; fase — dasar penyusunan Tujuan Pembelajaran (TP).</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cp-modal" onclick="openCreate()">
                <i class="ri-add-line me-1"></i> Tambah CP
            </button>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Fase</label>
                    <select name="fase" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach($faseOptions as $fase)
                            <option value="{{ $fase }}" {{ request('fase') === $fase ? 'selected' : '' }}>{{ $fase }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary"><i class="ri-filter-3-line me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mapel</th>
                            <th>Fase</th>
                            <th>Elemen</th>
                            <th>Deskripsi</th>
                            <th class="text-center">TP</th>
                            <th class="text-center">Urutan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cpList as $cp)
                            <tr>
                                <td class="fw-medium">{{ $cp->subject?->name ?? '-' }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ $cp->fase }}</span></td>
                                <td>{{ $cp->elemen ?: '—' }}</td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($cp->deskripsi, 90) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary">{{ (int) ($tpCounts[$cp->id] ?? 0) }}</span>
                                </td>
                                <td class="text-center">{{ $cp->urutan }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-warning"
                                        data-cp="{{ json_encode([
                                            'id' => $cp->id,
                                            'subject_id' => $cp->subject_id,
                                            'fase' => $cp->fase,
                                            'elemen' => $cp->elemen,
                                            'deskripsi' => $cp->deskripsi,
                                            'urutan' => $cp->urutan,
                                            'is_active' => (bool) $cp->is_active,
                                        ]) }}"
                                        onclick="openEdit(this)">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('user.kurikulum.cp.destroy', ['userId' => $userId, 'id' => $cp->id]) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus CP ini?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada CP. Klik <strong>Tambah CP</strong> untuk memulai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cp-modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="cp-form" action="{{ route('user.kurikulum.cp.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="cp-method" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cp-modal-title">Tambah CP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" id="cp-subject" class="form-select" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fase <span class="text-danger">*</span></label>
                                <input type="text" name="fase" id="cp-fase" class="form-control" list="fase-options" maxlength="5" required placeholder="mis. D">
                                <datalist id="fase-options">
                                    @foreach($faseOptions as $fase)
                                        <option value="{{ $fase }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Urutan</label>
                                <input type="number" name="urutan" id="cp-urutan" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Elemen</label>
                                <input type="text" name="elemen" id="cp-elemen" class="form-control" maxlength="100" placeholder="mis. Bilangan">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi CP <span class="text-danger">*</span></label>
                                <textarea name="deskripsi" id="cp-deskripsi" rows="4" class="form-control" required
                                    placeholder="Contoh: Peserta didik dapat memahami bilangan bulat dan operasinya..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" id="cp-active" class="form-check-input" value="1" checked>
                                    <label class="form-check-label" for="cp-active">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var cpStoreUrl = @json(route('user.kurikulum.cp.store', ['userId' => $userId]));
        var cpUpdateUrl = @json(route('user.kurikulum.cp.update', ['userId' => $userId, 'id' => '__ID__']));

        function openCreate() {
            document.getElementById('cp-modal-title').textContent = 'Tambah CP';
            document.getElementById('cp-form').action = cpStoreUrl;
            document.getElementById('cp-method').value = 'POST';
            document.getElementById('cp-subject').value = '';
            document.getElementById('cp-fase').value = '';
            document.getElementById('cp-urutan').value = 0;
            document.getElementById('cp-elemen').value = '';
            document.getElementById('cp-deskripsi').value = '';
            document.getElementById('cp-active').checked = true;
        }

        function openEdit(btn) {
            var cp = JSON.parse(btn.dataset.cp);
            document.getElementById('cp-modal-title').textContent = 'Edit CP';
            document.getElementById('cp-form').action = cpUpdateUrl.replace('__ID__', cp.id);
            document.getElementById('cp-method').value = 'PUT';
            document.getElementById('cp-subject').value = cp.subject_id;
            document.getElementById('cp-fase').value = cp.fase || '';
            document.getElementById('cp-urutan').value = cp.urutan || 0;
            document.getElementById('cp-elemen').value = cp.elemen || '';
            document.getElementById('cp-deskripsi').value = cp.deskripsi || '';
            document.getElementById('cp-active').checked = cp.is_active !== false;
            new bootstrap.Modal(document.getElementById('cp-modal')).show();
        }
    </script>
@endsection
