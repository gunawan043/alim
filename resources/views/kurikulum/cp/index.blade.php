@extends('layouts.master')
@section('title', 'Capaian Pembelajaran')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') CP @endslot
        @slot('title') Capaian Pembelajaran @endslot
    @endcomponent

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Capaian Pembelajaran (CP)</h4>
            <p class="text-muted mb-0 small">Target kompetensi per mapel &amp; fase — dasar penyusunan Tujuan Pembelajaran (TP).</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cp-modal" onclick="openCreate()">
            <i class="ri-add-line align-bottom me-1"></i> Tambah CP
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}" class="row g-3 align-items-end">
                <div class="col-xxl-4 col-sm-6">
                    <label class="form-label">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select">
                        <option value="">— Semua Mapel —</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <label class="form-label">Fase</label>
                    <select name="fase" class="form-select">
                        <option value="">— Semua Fase —</option>
                        @foreach($faseOptions as $fase)
                            <option value="{{ $fase }}" {{ request('fase') === $fase ? 'selected' : '' }}>{{ $fase }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-3 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="ri-search-line align-bottom me-1"></i> Filter</button>
                    <a href="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line align-bottom"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-flag-2-line text-primary me-1"></i> Daftar CP</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $cpList->count() }} CP</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mapel</th>
                        <th class="text-center">Fase</th>
                        <th>Elemen</th>
                        <th>Deskripsi</th>
                        <th class="text-center">TP</th>
                        <th class="text-center">Urutan</th>
                        <th class="text-end" style="width:110px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cpList as $cp)
                        <tr>
                            <td class="fw-medium">{{ $cp->subject?->name ?? '-' }}</td>
                            <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $cp->fase }}</span></td>
                            <td>{{ $cp->elemen ?: '—' }}</td>
                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($cp->deskripsi, 90) }}</td>
                            <td class="text-center">
                                <span class="badge {{ ($tpCounts[$cp->id] ?? 0) > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ (int) ($tpCounts[$cp->id] ?? 0) }}
                                </span>
                            </td>
                            <td class="text-center">{{ $cp->urutan }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-soft-warning"
                                    data-cp="{{ json_encode([
                                        'id' => $cp->id,
                                        'subject_id' => $cp->subject_id,
                                        'fase' => $cp->fase,
                                        'elemen' => $cp->elemen,
                                        'deskripsi' => $cp->deskripsi,
                                        'urutan' => $cp->urutan,
                                        'is_active' => (bool) $cp->is_active,
                                    ]) }}"
                                    onclick="openEdit(this)" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <form action="{{ route('user.kurikulum.cp.destroy', ['userId' => $userId, 'id' => $cp->id]) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus CP ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-flag-2-line fs-1 d-block mb-2"></i>
                                    Belum ada CP. Klik <strong>Tambah CP</strong> untuk memulai.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade zoomIn" id="cp-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" id="cp-form" action="{{ route('user.kurikulum.cp.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="cp-method" value="POST">
                    <div class="modal-header bg-primary-subtle p-3">
                        <h5 class="modal-title" id="cp-modal-title"><i class="ri-flag-2-line me-1"></i> Tambah CP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <button type="submit" class="btn btn-success"><i class="ri-save-line align-bottom me-1"></i> Simpan</button>
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
            document.getElementById('cp-modal-title').innerHTML = '<i class="ri-flag-2-line me-1"></i> Tambah CP';
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
            document.getElementById('cp-modal-title').innerHTML = '<i class="ri-pencil-line me-1"></i> Edit CP';
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
