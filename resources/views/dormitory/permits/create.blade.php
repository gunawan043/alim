@extends('layouts.master')
@section('title') Ajukan Perizinan @endsection
@section('css')
<style>
.student-search-wrapper { position: relative; }
.student-search-results {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 1050;
    background: #fff; border: 1px solid #dee2e6; border-radius: .375rem;
    max-height: 260px; overflow-y: auto; box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
}
.student-search-results .list-group-item { cursor: pointer; }
.student-search-results .list-group-item:hover { background-color: #f8f9fa; }
[data-bs-theme="dark"] .student-search-results { background: #1c1e2e; border-color: #2d3045; }
[data-bs-theme="dark"] .student-search-results .list-group-item:hover { background-color: #2d3045; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('title') Ajukan Izin @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.permits.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
          enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id') }}">

        <div class="row">
            {{-- Left Column: Student & Permit Info --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-file-list-line me-2 text-primary"></i>Form Perizinan</h5>
                    </div>
                    <div class="card-body">

                        {{-- Student Search --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Santri <span class="text-danger">*</span>
                            </label>
                            <div class="student-search-wrapper" id="studentSearchWrapper">
                                <input type="text" id="student_search" class="form-control"
                                       placeholder="Ketik nama lengkap santri untuk mencari..."
                                       autocomplete="off" value="{{ old('student_search') }}">
                                <div id="studentSearchResults" class="student-search-results d-none"></div>
                            </div>
                            @error('student_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div id="selectedStudentInfo" class="mt-2 {{ old('student_id') ? '' : 'd-none' }}">
                                <div class="alert alert-success py-2 d-flex align-items-center gap-2">
                                    <i class="ri-user-follow-line"></i>
                                    <span id="selectedStudentName"></span>
                                    <span class="text-muted">—</span>
                                    <span id="selectedStudentRoom" class="text-muted"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Permit Type --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Jenis Izin <span class="text-danger">*</span>
                                </label>
                                <select name="permit_type" id="permit_type" class="form-select @error('permit_type') is-invalid @enderror" required>
                                    <option value="">-- Pilih Jenis Izin --</option>
                                    <option value="pulang" {{ old('permit_type') == 'pulang' ? 'selected' : '' }}>Pulang</option>
                                    <option value="keluar_kota" {{ old('permit_type') == 'keluar_kota' ? 'selected' : '' }}>Keluar Kota</option>
                                    <option value="berobat" {{ old('permit_type') == 'berobat' ? 'selected' : '' }}>Berobat</option>
                                    <option value="sakit" {{ old('permit_type') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                                    <option value="keperluan_keluarga" {{ old('permit_type') == 'keperluan_keluarga' ? 'selected' : '' }}>Keperluan Keluarga</option>
                                    <option value="lainnya" {{ old('permit_type') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('permit_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Tujuan <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="destination" id="destination"
                                       class="form-control @error('destination') is-invalid @enderror"
                                       placeholder="Contoh: Rumah orang tua, Kota Solo"
                                       value="{{ old('destination') }}" required>
                                @error('destination')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Sakit Warning --}}
                        <div id="sakitWarning" class="alert alert-warning mb-4 {{ old('permit_type') == 'sakit' ? '' : 'd-none' }}">
                            <i class="ri-alarm-warning-line me-2"></i>
                            <strong>Perhatian:</strong> Izin sakit memerlukan keterangan dari UKS yang sudah disetujui.
                        </div>

                        {{-- Purpose --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Keperluan / Keterangan</label>
                            <textarea name="purpose" id="purpose" class="form-control @error('purpose') is-invalid @enderror"
                                      rows="3" placeholder="Jelaskan alasan atau keperluan izin...">{{ old('purpose') }}</textarea>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Datetime --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Tanggal & Jam Berangkat <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" name="departure_datetime" id="departure_datetime"
                                       class="form-control @error('departure_datetime') is-invalid @enderror"
                                       value="{{ old('departure_datetime') }}" required>
                                @error('departure_datetime')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Taksiran Tanggal & Jam Kembali <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" name="expected_return_datetime" id="expected_return_datetime"
                                       class="form-control @error('expected_return_datetime') is-invalid @enderror"
                                       value="{{ old('expected_return_datetime') }}" required>
                                @error('expected_return_datetime')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Document --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Lampiran (Opsional)</label>
                            <input type="file" name="document" id="document"
                                   class="form-control @error('document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="form-text">Format: PDF, JPG, PNG. Maksimal 2 MB.</div>
                            @error('document')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Right Column: Penjemput / Mahrom --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-user-heart-line me-2 text-primary"></i>Data Penjemput / Mahrom</h5>
                    </div>
                    <div class="card-body">

                        {{-- Mahrom dropdown --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Mahrom</label>
                            <select name="mahrom_id" id="mahrom_id" class="form-select">
                                <option value="">-- Tidak ada mahrom --</option>
                            </select>
                            <div class="form-text">Dipilih otomatis saat santri dipilih.</div>
                        </div>

                        {{-- Companion is Mahrom --}}
                        <div class="form-check mb-3">
                            <input type="checkbox" name="companion_is_mahrom" id="companion_is_mahrom"
                                   class="form-check-input" value="1" {{ old('companion_is_mahrom') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="companion_is_mahrom">
                                Penjemput adalah Mahrom
                            </label>
                        </div>

                        <hr>

                        {{-- Companion Fields --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Penjemput</label>
                            <input type="text" name="companion_name" id="companion_name"
                                   class="form-control @error('companion_name') is-invalid @enderror"
                                   placeholder="Nama lengkap penjemput"
                                   value="{{ old('companion_name') }}">
                            @error('companion_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Hubungan</label>
                            <input type="text" name="companion_relation" id="companion_relation"
                                   class="form-control @error('companion_relation') is-invalid @enderror"
                                   placeholder="Contoh: Ayah, Ibu, Kakak, Paman"
                                   value="{{ old('companion_relation') }}">
                            @error('companion_relation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">No. Telepon Penjemput</label>
                            <input type="tel" name="companion_phone" id="companion_phone"
                                   class="form-control @error('companion_phone') is-invalid @enderror"
                                   placeholder="08xxxxxxxxxx"
                                   value="{{ old('companion_phone') }}">
                            @error('companion_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        {{-- Notes --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan Tambahan</label>
                            <textarea name="notes" id="notes" class="form-control"
                                      rows="3" placeholder="Catatan tambahan jika ada...">{{ old('notes') }}</textarea>
                        </div>

                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success flex-grow-1">
                        <i class="ri-send-plane-line me-1"></i> Ajukan Izin
                    </button>
                    <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-light">Batal</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
(function() {
    'use strict';

    const dormitoryId = '{{ $dormitory->id }}';
    const userId = '{{ $userId }}';
    const oldStudentId = '{{ old('student_id') }}';

    // ── Student Autocomplete ───────────────────────────────────
    const searchInput    = document.getElementById('student_search');
    const resultsBox     = document.getElementById('studentSearchResults');
    const selectedInfo   = document.getElementById('selectedStudentInfo');
    const selectedName   = document.getElementById('selectedStudentName');
    const selectedRoom   = document.getElementById('selectedStudentRoom');
    const studentIdInput = document.getElementById('student_id');
    const mahromSelect   = document.getElementById('mahrom_id');

    let searchTimeout;

    function renderStudentResults(students) {
        resultsBox.innerHTML = '';
        if (!students || students.length === 0) {
            resultsBox.innerHTML = '<div class="list-group-item text-muted py-2">Tidak ditemukan.</div>';
            resultsBox.classList.remove('d-none');
            return;
        }
        const ul = document.createElement('ul');
        ul.className = 'list-group list-group-flush';
        students.forEach(function(s) {
            const li = document.createElement('li');
            li.className = 'list-group-item py-2';
            li.innerHTML = '<div class="fw-semibold">' + s.name + '</div>' +
                '<div class="text-muted small">Kamar: ' + (s.room_name || '—') + '</div>';
            li.addEventListener('click', function() {
                selectStudent(s);
            });
            ul.appendChild(li);
        });
        resultsBox.appendChild(ul);
        resultsBox.classList.remove('d-none');
    }

    function selectStudent(student) {
        searchInput.value = student.name;
        studentIdInput.value = student.id;
        resultsBox.classList.add('d-none');

        selectedName.textContent = student.name;
        selectedRoom.textContent = 'Kamar: ' + (student.room_name || '—');
        selectedInfo.classList.remove('d-none');

        // Load mahroms
        loadMahroms(student.id);
    }

    function loadMahroms(studentId) {
        // Fetch mahroms via AJAX
        const url = '/api/mahroms/' + studentId;
        fetch(url)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                mahromSelect.innerHTML = '<option value="">-- Tidak ada mahrom --</option>';
                if (data.data && data.data.length > 0) {
                    data.data.forEach(function(m) {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.name + ' (' + m.relation + ')';
                        mahromSelect.appendChild(opt);
                    });
                }
            })
            .catch(function() {
                mahromSelect.innerHTML = '<option value="">-- Gagal load mahrom --</option>';
            });
    }

    searchInput.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(searchTimeout);

        if (q.length < 2) {
            resultsBox.classList.add('d-none');
            return;
        }

        searchTimeout = setTimeout(function() {
            const url = '/api/dormitory/' + dormitoryId + '/residents/find-student?q=' + encodeURIComponent(q);
            fetch(url)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    renderStudentResults(data.data || data);
                })
                .catch(function() {
                    resultsBox.innerHTML = '<div class="list-group-item text-muted py-2">Gagal mencari.</div>';
                    resultsBox.classList.remove('d-none');
                });
        }, 350);
    });

    document.addEventListener('click', function(e) {
        if (!document.getElementById('studentSearchWrapper').contains(e.target)) {
            resultsBox.classList.add('d-none');
        }
    });

    // ── Permit Type: show/hide sakit warning ──────────────────
    document.getElementById('permit_type').addEventListener('change', function() {
        const warning = document.getElementById('sakitWarning');
        if (this.value === 'sakit') {
            warning.classList.remove('d-none');
        } else {
            warning.classList.add('d-none');
        }
    });

    // ── Mahrom auto-fill companion fields ──────────────────────
    mahromSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (!this.value) return;
        const text = selected.textContent;
        const match = text.match(/^(.+?)\s\((.+?)\)$/);
        if (match) {
            document.getElementById('companion_name').value = match[1];
            document.getElementById('companion_relation').value = match[2];
            document.getElementById('companion_is_mahrom').checked = true;
        }
    });

    // ── Companion is Mahrom: auto-populate from mahrom ─────────
    document.getElementById('companion_is_mahrom').addEventListener('change', function() {
        if (this.checked && mahromSelect.value) {
            const selected = mahromSelect.options[mahromSelect.selectedIndex];
            const text = selected.textContent;
            const match = text.match(/^(.+?)\s\((.+?)\)$/);
            if (match) {
                document.getElementById('companion_name').value = match[1];
                document.getElementById('companion_relation').value = match[2];
            }
        }
    });

    // ── Pre-load student if editing (old value) ─────────────────
    if (oldStudentId) {
        fetch('/api/dormitory/' + dormitoryId + '/residents/find-student?q=' + oldStudentId)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                const students = data.data || data;
                const student = students.find(function(s) { return s.id === oldStudentId; });
                if (student) selectStudent(student);
            })
            .catch(function() {});
    }

})();
</script>
@endsection