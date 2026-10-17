@extends('layouts.master')
@section('title', 'Kurikulum Pembelajaran per Jenjang')

@section('css')
    <style>
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stat-label { font-size: 0.72rem; }
        .table-freeze thead th { position: sticky; top: 0; z-index: 2; }
        .jenjang-card .table td, .jenjang-card .table th { padding: 0.45rem 0.6rem; }
        .hour-cell { font-variant-numeric: tabular-nums; }
    </style>
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $totalJenjang = $jenjangs->count();
        $totalMapel = $jenjangs->sum(fn ($j) => count($j['rows']));
        $totalPemadatan = $jenjangs->sum('pemadatan');
        $totalJam = $jenjangs->sum(fn ($j) => array_sum($j['totals']));
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('title') Struktur Kurikulum (per Jenjang) @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger small">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Stat Cards (pola GTK) --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-layout-grid-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Jenjang Ditampilkan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $totalJenjang }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-filter-3-line me-1"></i>{{ $filter ?: 'Semua jenjang' }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-book-open-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mapel Pondok</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $totalMapel }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-lock-line me-1"></i>Jam terkunci untuk satuan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-add-circle-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Mapel Pemadatan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $totalPemadatan }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-user-settings-line me-1"></i>Diatur tiap satuan pendidikan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-time-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Alokasi Jam</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $totalJam }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-stack-line me-1"></i>Jam per pekan (seluruh kolom)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter (pola GTK) --}}
    <div class="card mb-3">
        <div class="card-header py-2 bg-light border-bottom">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2">
            <form method="GET" class="row g-2 align-items-end flex-grow-1">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Tampilkan Jenjang</label>
                    <select name="jenjang" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" {{ $filter === '' ? 'selected' : '' }}>Semua Jenjang</option>
                        @foreach(['SD', 'SMP', 'SMA'] as $j)
                            <option value="{{ $j }}" {{ $filter === $j ? 'selected' : '' }}>{{ $j }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9 text-md-end small text-muted pb-1">
                    <i class="ri-information-line me-1"></i>
                    Perubahan berlaku untuk <strong>seluruh satuan pendidikan</strong> jenjang terkait. Satuan pendidikan hanya
                    dapat mengubah <strong>nama</strong>; jam terkunci. <strong>Mapel Pemadatan</strong> diatur satuan masing-masing.
                </div>
            </form>
            <form method="POST" action="{{ route('user.sa.curriculum-structure.apply', ['userId' => $userId]) }}"
                  onsubmit="return confirm('Terapkan ulang struktur kurikulum pondok ke seluruh satuan pendidikan?');">
                @csrf
                <button class="btn btn-success btn-sm text-nowrap">
                    <i class="ri-refresh-line me-1"></i>Terapkan Struktur (semua satuan)
                </button>
            </form>
            </div>
        </div>
    </div>

    @forelse($jenjangs as $jenjang)
        <div class="card jenjang-card mb-3">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-table-line text-primary me-1"></i>Jenjang {{ $jenjang['nama'] }}</h5>
                    <p class="text-muted mb-0 small">
                        {{ count($jenjang['rows']) }} mapel pondok · {{ count($jenjang['labels']) }} kolom tingkat
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning-subtle text-warning">{{ $jenjang['pemadatan'] }} mapel pemadatan</span>
                    <button type="button" class="btn btn-sm btn-success" onclick="openSubjectModal('{{ $jenjang['nama'] }}', null)">
                        <i class="ri-add-line me-1"></i>Tambah Mapel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" class="align-middle text-center" style="width:40px">No</th>
                                <th rowspan="2" class="align-middle" style="max-width:50px">Kode Mapel</th>
                                <th rowspan="2" class="align-middle" style="min-width:240px">Mata Pelajaran</th>
                                <th colspan="{{ count($jenjang['labels']) }}" class="text-center">Alokasi Waktu Per Pekan</th>
                                <th rowspan="2" class="align-middle text-center" style="width:110px">Aksi</th>
                            </tr>
                            <tr>
                                @foreach($jenjang['labels'] as $label)
                                    <th class="text-center" style="width:90px">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $prevGroup = null; $mainNo = 0; $subNo = 0; @endphp
                            @forelse($jenjang['rows'] as $row)
                                @php
                                    $gid = $row['group_id'] ?? null;
                                    $isGrouped = $gid && (($jenjang['groupCounts'][$gid] ?? 0) > 1);
                                @endphp
                                @if($isGrouped && $gid !== $prevGroup)
                                    @php $mainNo++; $subNo = 0; $prevGroup = $gid; @endphp
                                    <tr class="table-light">
                                        <td class="text-center fw-semibold">{{ $mainNo }}</td>
                                        <td colspan="{{ count($jenjang['labels']) + 3 }}" class="fw-semibold">
                                            {{ $row['group_name'] ?? 'Rumpun' }}
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="text-center text-muted">{{ $isGrouped ? '' : (++$mainNo) }}</td>
                                    <td class="fw-medium">{{ $row['code'] }}</td>
                                    <td class="fw-medium">
                                        @if($isGrouped) {{ ++$subNo }}. @endif{{ $row['name'] }}
                                    </td>
                                    @foreach($jenjang['labels'] as $col => $label)
                                        <td class="text-center hour-cell">
                                            @if(($row['cells'][$col] ?? null) !== null)
                                                <span class="badge bg-primary-subtle text-primary">{{ $row['cells'][$col] }}</span>
                                            @else
                                                <span class="text-muted">–</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center text-nowrap">
                                        <button type="button" class="btn btn-sm btn-soft-primary"
                                                data-jenjang="{{ $jenjang['nama'] }}"
                                                data-row="{{ json_encode($row) }}"
                                                onclick="openSubjectModal(this.dataset.jenjang, JSON.parse(this.dataset.row))">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form method="POST" class="d-inline"
                                              action="{{ route('user.sa.curriculum-structure.subject.destroy', ['userId' => $userId, 'code' => $row['code']]) }}"
                                              onsubmit="return confirm('Hapus mapel {{ $row['name'] }} dari struktur jenjang {{ $jenjang['nama'] }}?');">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="jenjang" value="{{ $jenjang['nama'] }}">
                                            <button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($jenjang['labels']) + 4 }}" class="text-center text-muted py-4">
                                    Belum ada struktur — gunakan "Tambah Mapel" atau jalankan "Terapkan Struktur".
                                </td></tr>
                            @endforelse
                        </tbody>
                        @if($jenjang['rows'])
                            <tfoot>
                                <tr class="table-light fw-semibold">
                                    <td colspan="3">Jumlah Jam Pelajaran Per Pekan</td>
                                    @foreach($jenjang['labels'] as $col => $label)
                                        <td class="text-center">{{ $jenjang['totals'][$col] }}</td>
                                    @endforeach
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5">
            Belum ada satuan pendidikan berjenjang SD/SMP/SMA. Lengkapi data sekolah terlebih dahulu.
        </div></div>
    @endforelse

    {{-- Modal Tambah/Edit Mapel (dinamis per jenjang) --}}
    <div class="modal fade" id="subjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="subjectForm" action="{{ route('user.sa.curriculum-structure.subject.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="subject-method" value="POST">
                    <input type="hidden" name="jenjang" id="subject-jenjang">

                    <div class="modal-header bg-success-subtle">
                        <h5 class="modal-title" id="subject-modal-title">Tambah Mapel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="subject-code" class="form-control" required maxlength="20">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nama Mapel <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="subject-name" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rumpun</label>
                                <select name="subject_group_id" id="subject-group" class="form-select">
                                    <option value="">— tanpa rumpun —</option>
                                    @foreach($subjectGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="form-label small mb-1">Alokasi Jam per Pekan (0/kosong = tidak diajarkan)</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light"><tr id="subject-hour-head"></tr></thead>
                                <tbody><tr id="subject-hour-body"></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-success" id="subject-submit"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const JENJANG_COLUMNS = @json($jenjangColumns);

    const STORE_URL = "{{ route('user.sa.curriculum-structure.subject.store', ['userId' => $userId]) }}";
    const UPDATE_URL = "{{ route('user.sa.curriculum-structure.subject.update', ['userId' => $userId, 'code' => '__CODE__']) }}";

    function openSubjectModal(jenjang, row) {
        const cols = JENJANG_COLUMNS[jenjang] || [];
        const head = document.getElementById('subject-hour-head');
        const body = document.getElementById('subject-hour-body');
        head.innerHTML = '<th style="min-width:80px">Kolom</th>' + cols.map(c => '<th class="text-center" style="width:110px">' + c.label + '</th>').join('');
        body.innerHTML = '<td class="fw-medium">Jam</td>' + cols.map(c =>
            '<td><input type="number" min="0" max="40" class="form-control form-control-sm text-center" name="allocations[' + c.key + ']" id="alloc-' + c.key.replace(/[:]/g, '_') + '" value=""></td>'
        ).join('');

        document.getElementById('subject-jenjang').value = jenjang;
        document.getElementById('subject-modal-title').textContent = row ? ('Edit Mapel — ' + jenjang) : ('Tambah Mapel — ' + jenjang);

        const form = document.getElementById('subjectForm');
        if (row) {
            document.getElementById('subject-method').value = 'PUT';
            form.action = UPDATE_URL.replace('__CODE__', encodeURIComponent(row.code));
            document.getElementById('subject-code').value = row.code || '';
            document.getElementById('subject-name').value = row.name || '';
            document.getElementById('subject-group').value = row.group_id || '';
            cols.forEach(function (c, i) {
                const el = document.getElementById('alloc-' + c.key.replace(/[:]/g, '_'));
                if (el) { el.value = (row.cells[i] ?? ''); }
            });
        } else {
            document.getElementById('subject-method').value = 'POST';
            form.action = STORE_URL;
            form.reset();
            document.getElementById('subject-jenjang').value = jenjang;
        }

        new bootstrap.Modal(document.getElementById('subjectModal')).show();
    }
    </script>
@endsection
