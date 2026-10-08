@extends('layouts.master')
@section('title') Tujuan Pembelajaran @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kurikulum @endslot
        @slot('li_2') TP @endslot
        @slot('title') Tujuan Pembelajaran @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Tujuan Pembelajaran (TP)</h4>
                <p class="text-muted small mb-0">TP diturunkan dari CP; buat, ubah, dan urutkan sebagai dasar ATP.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tp-modal" onclick="openCreate()">
                <i class="ri-add-line me-1"></i> Tambah TP
            </button>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.kurikulum.tp.index', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $academicYearId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="ganjil" {{ $semester === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semester === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Mapel</label>
                    <select name="subject_id" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $subjectId === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Jenjang</label>
                    <select name="grade_level_id" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}" {{ request('grade_level_id') === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100"><i class="ri-filter-3-line me-1"></i> Filter</button>
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
                            <th>Kode</th>
                            <th>Tujuan Pembelajaran</th>
                            <th>CP / Elemen</th>
                            <th>Fase</th>
                            <th class="text-center">JP</th>
                            <th class="text-center">Urutan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tpList as $tp)
                            @php
                                $canManage = $isKurikulumTeam || in_array($tp->subject_id, $taughtSubjectIds, true);
                                $inAtp = in_array($tp->id, $usedTpIds, true);
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">{{ $tp->kode_tp }}</span>
                                    <div class="small text-muted">{{ $tp->subject?->name }}</div>
                                    <div class="small text-muted">{{ $tp->gradeLevel?->name }}</div>
                                </td>
                                <td class="small">{{ $tp->deskripsi }}</td>
                                <td class="small text-muted">
                                    @if($tp->capaianPembelajaran)
                                        <div>{{ \Illuminate\Support\Str::limit($tp->capaianPembelajaran->deskripsi, 60) }}</div>
                                    @endif
                                    {{ $tp->elemen ?: '—' }}
                                </td>
                                <td>{{ $tp->fase ?: '—' }}</td>
                                <td class="text-center">{{ $tp->alokasi_waktu }}</td>
                                <td class="text-center">
                                    @if($canManage)
                                        <form action="{{ route('user.kurikulum.tp.move', ['userId' => $userId, 'id' => $tp->id]) }}" method="POST" class="d-inline">
                                            @csrf <input type="hidden" name="direction" value="up">
                                            <button class="btn btn-sm btn-link p-0"><i class="ri-arrow-up-line"></i></button>
                                        </form>
                                        <form action="{{ route('user.kurikulum.tp.move', ['userId' => $userId, 'id' => $tp->id]) }}" method="POST" class="d-inline">
                                            @csrf <input type="hidden" name="direction" value="down">
                                            <button class="btn btn-sm btn-link p-0"><i class="ri-arrow-down-line"></i></button>
                                        </form>
                                    @else
                                        {{ $tp->urutan }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($canManage)
                                        <button class="btn btn-sm btn-outline-warning"
                                            data-tp="{{ json_encode([
                                                'id' => $tp->id,
                                                'subject_id' => $tp->subject_id,
                                                'grade_level_id' => $tp->grade_level_id,
                                                'academic_year_id' => $tp->academic_year_id,
                                                'semester' => $tp->semester,
                                                'capaian_pembelajaran_id' => $tp->capaian_pembelajaran_id,
                                                'kode_tp' => $tp->kode_tp,
                                                'deskripsi' => $tp->deskripsi,
                                                'elemen' => $tp->elemen,
                                                'fase' => $tp->fase,
                                                'alokasi_waktu' => $tp->alokasi_waktu,
                                                'urutan' => $tp->urutan,
                                                'is_active' => (bool) $tp->is_active,
                                            ]) }}"
                                            onclick="openEdit(this)">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        @if(! $inAtp)
                                            <form action="{{ route('user.kurikulum.tp.destroy', ['userId' => $userId, 'id' => $tp->id]) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Hapus TP ini?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary" title="Dipakai di ATP">ATP</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada TP pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="tp-modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="tp-form" action="{{ route('user.kurikulum.tp.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="_method" id="tp-method" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tp-modal-title">Tambah TP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" id="tp-subject" class="form-select" required onchange="filterCpOptions()">
                                    <option value="">-- Pilih Mapel --</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenjang</label>
                                <select name="grade_level_id" id="tp-grade" class="form-select" onchange="applyGradeFase()">
                                    <option value="">— Semua Jenjang —</option>
                                    @foreach($gradeLevels as $grade)
                                        <option value="{{ $grade->id }}" data-fase="{{ $grade->fase }}">{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" id="tp-ay" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" id="tp-semester" class="form-select" required>
                                    <option value="ganjil">Ganjil</option>
                                    <option value="genap">Genap</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode TP <span class="text-danger">*</span></label>
                                <input type="text" name="kode_tp" id="tp-kode" class="form-control" maxlength="20" required placeholder="TP.01">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Diturunkan dari CP</label>
                                <select name="capaian_pembelajaran_id" id="tp-cp" class="form-select" onchange="applyCpDefaults()">
                                    <option value="">— Tanpa CP —</option>
                                </select>
                                <small class="text-muted">TP sebaiknya diturunkan dari CP mapel &amp; fase yang sesuai.</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi TP <span class="text-danger">*</span></label>
                                <textarea name="deskripsi" id="tp-deskripsi" rows="3" class="form-control" required
                                    placeholder="Contoh: Peserta didik mampu menganalisis ..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Elemen</label>
                                <input type="text" name="elemen" id="tp-elemen" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Fase</label>
                                <input type="text" name="fase" id="tp-fase" class="form-control" maxlength="5">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Alokasi JP</label>
                                <input type="number" name="alokasi_waktu" id="tp-jp" class="form-control" min="1" max="100" value="2">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Urutan</label>
                                <input type="number" name="urutan" id="tp-urutan" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" id="tp-active" class="form-check-input" value="1" checked>
                                    <label class="form-check-label" for="tp-active">Aktif</label>
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
        var tpStoreUrl = @json(route('user.kurikulum.tp.store', ['userId' => $userId]));
        var tpUpdateUrl = @json(route('user.kurikulum.tp.update', ['userId' => $userId, 'id' => '__ID__']));
        var cpOptions = @json($cpOptionsJson);

        function filterCpOptions(selectedId) {
            var subjectId = document.getElementById('tp-subject').value;
            var select = document.getElementById('tp-cp');
            select.innerHTML = '<option value="">— Tanpa CP —</option>';
            cpOptions.filter(function (cp) { return ! subjectId || cp.subject_id === subjectId; })
                .forEach(function (cp) {
                    var opt = document.createElement('option');
                    opt.value = cp.id;
                    opt.textContent = '[' + (cp.fase || '-') + '] ' + (cp.elemen ? cp.elemen + ' — ' : '') + cp.deskripsi;
                    opt.dataset.fase = cp.fase || '';
                    opt.dataset.elemen = cp.elemen || '';
                    if (selectedId && selectedId === cp.id) { opt.selected = true; }
                    select.appendChild(opt);
                });
        }

        function applyGradeFase() {
            var grade = document.getElementById('tp-grade');
            var fase = grade.options[grade.selectedIndex] ? grade.options[grade.selectedIndex].dataset.fase : '';
            if (fase) { document.getElementById('tp-fase').value = fase; }
        }

        function applyCpDefaults() {
            var cp = document.getElementById('tp-cp');
            var opt = cp.options[cp.selectedIndex];
            if (! opt || ! opt.value) { return; }
            if (opt.dataset.fase) { document.getElementById('tp-fase').value = opt.dataset.fase; }
            if (opt.dataset.elemen) { document.getElementById('tp-elemen').value = opt.dataset.elemen; }
        }

        function openCreate() {
            document.getElementById('tp-modal-title').textContent = 'Tambah TP';
            document.getElementById('tp-form').action = tpStoreUrl;
            document.getElementById('tp-method').value = 'POST';
            document.getElementById('tp-subject').value = @json($subjectId ?? '');
            document.getElementById('tp-grade').value = '';
            document.getElementById('tp-ay').value = @json($academicYearId);
            document.getElementById('tp-semester').value = @json($semester);
            document.getElementById('tp-kode').value = '';
            document.getElementById('tp-deskripsi').value = '';
            document.getElementById('tp-elemen').value = '';
            document.getElementById('tp-fase').value = '';
            document.getElementById('tp-jp').value = 2;
            document.getElementById('tp-urutan').value = 0;
            document.getElementById('tp-active').checked = true;
            filterCpOptions();
        }

        function openEdit(btn) {
            var tp = JSON.parse(btn.dataset.tp);
            document.getElementById('tp-modal-title').textContent = 'Edit TP';
            document.getElementById('tp-form').action = tpUpdateUrl.replace('__ID__', tp.id);
            document.getElementById('tp-method').value = 'PUT';
            document.getElementById('tp-subject').value = tp.subject_id;
            document.getElementById('tp-grade').value = tp.grade_level_id || '';
            document.getElementById('tp-ay').value = tp.academic_year_id;
            document.getElementById('tp-semester').value = tp.semester;
            document.getElementById('tp-kode').value = tp.kode_tp;
            document.getElementById('tp-deskripsi').value = tp.deskripsi || '';
            document.getElementById('tp-elemen').value = tp.elemen || '';
            document.getElementById('tp-fase').value = tp.fase || '';
            document.getElementById('tp-jp').value = tp.alokasi_waktu || 2;
            document.getElementById('tp-urutan').value = tp.urutan || 0;
            document.getElementById('tp-active').checked = tp.is_active !== false;
            filterCpOptions(tp.capaian_pembelajaran_id);
            new bootstrap.Modal(document.getElementById('tp-modal')).show();
        }
    </script>
@endsection
