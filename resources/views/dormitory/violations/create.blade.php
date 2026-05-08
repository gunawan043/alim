@extends('layouts.master')
@section('title') Catat Pelanggaran @endsection
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
        @slot('li_3') <a href="{{ route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Pelanggaran</a> @endslot
        @slot('title') Tambah @endslot
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
          action="{{ route('user.asrama.violations.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
        @csrf
        <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id') }}">
        <input type="hidden" name="room_id"    id="room_id"    value="{{ old('room_id') }}">

        <div class="row">
            {{-- Left Column --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-error-warning-line me-2 text-danger"></i>Form Pelanggaran</h5>
                    </div>
                    <div class="card-body">

                        {{-- Student Search --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Santri <span class="text-danger">*</span>
                            </label>
                            <div class="student-search-wrapper" id="studentSearchWrapper">
                                <input type="text" id="student_search" class="form-control"
                                       placeholder="Ketik nama lengkap sanksi untuk mencari..."
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

                        {{-- Violation Date + Category --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Tanggal Pelanggaran <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="violation_date" id="violation_date"
                                       class="form-control @error('violation_date') is-invalid @enderror"
                                       value="{{ old('violation_date', now()->format('Y-m-d')) }}" required>
                                @error('violation_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Kategori <span class="text-danger">*</span>
                                </label>
                                <select name="violation_category" id="violation_category"
                                        class="form-select @error('violation_category') is-invalid @enderror" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="ringan" {{ old('violation_category') == 'ringan' ? 'selected' : '' }}>Ringan</option>
                                    <option value="sedang" {{ old('violation_category') == 'sedang' ? 'selected' : '' }}>Sedang</option>
                                    <option value="berat"  {{ old('violation_category') == 'berat' ? 'selected' : '' }}>Berat</option>
                                </select>
                                @error('violation_category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Violation Type --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Jenis Pelanggaran <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="violation_type" id="violation_type"
                                   class="form-control @error('violation_type') is-invalid @enderror"
                                   placeholder="Contoh: Terlambat架, Tidak mengikuti apel pagi, Membawa barang terlarang"
                                   value="{{ old('violation_type') }}" required>
                            @error('violation_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Deskripsi Kejadian</label>
                            <textarea name="description" id="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3" placeholder="Uraikan kronologi pelanggaran secara lengkap...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Points --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Poin Pelanggaran <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="points" id="points"
                                       class="form-control @error('points') is-invalid @enderror"
                                       placeholder="Contoh: 5"
                                       value="{{ old('points') }}" min="1" max="100" required>
                                <div class="form-text">1–100 poin</div>
                                @error('points')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Action Taken --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tindakan yang Diberikan</label>
                            <textarea name="action_taken" id="action_taken"
                                      class="form-control @error('action_taken') is-invalid @enderror"
                                      rows="2" placeholder="Contoh: Teguran lisan, Pencatatan di buku违反, Surat peringatan">{{ old('action_taken') }}</textarea>
                            @error('action_taken')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Follow Up --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tindak Lanjut</label>
                            <textarea name="follow_up" id="follow_up"
                                      class="form-control @error('follow_up') is-invalid @enderror"
                                      rows="2" placeholder="Tindak lanjut yang akan dilakukan...">{{ old('follow_up') }}</textarea>
                            @error('follow_up')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Witness --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Saksi</label>
                                <input type="text" name="witness_name" id="witness_name"
                                       class="form-control @error('witness_name') is-invalid @enderror"
                                       placeholder="Nama saksi (jika ada)"
                                       value="{{ old('witness_name') }}">
                                @error('witness_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Catatan Tambahan</label>
                                <input type="text" name="notes" id="notes"
                                       class="form-control @error('notes') is-invalid @enderror"
                                       placeholder="Catatan tambahan..."
                                       value="{{ old('notes') }}">
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Right Column: Summary --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-information-line me-2 text-primary"></i>Ringkasan</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small">Asrama</div>
                            <div class="fw-semibold">{{ $dormitory->name }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tahun Ajaran</div>
                            <div class="fw-semibold">{{ $activeYear->name ?? '-' }}</div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <div class="text-muted small">Santri</div>
                            <div class="fw-semibold" id="summaryStudent">Belum dipilih</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Kamar</div>
                            <div class="fw-semibold" id="summaryRoom">—</div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <div class="text-muted small">Kategori</div>
                            <div id="summaryCategory" class="fw-semibold">—</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Poin</div>
                            <div id="summaryPoints" class="fw-semibold">—</div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-danger flex-grow-1">
                        <i class="ri-save-line me-1"></i> Simpan Pelanggaran
                    </button>
                    <a href="{{ route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
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
    const oldStudentId = '{{ old('student_id') }}';

    // ── Student Autocomplete ───────────────────────────────────
    const searchInput   = document.getElementById('student_search');
    const resultsBox    = document.getElementById('studentSearchResults');
    const selectedInfo  = document.getElementById('selectedStudentInfo');
    const selectedName  = document.getElementById('selectedStudentName');
    const selectedRoom  = document.getElementById('selectedStudentRoom');
    const studentIdInput = document.getElementById('student_id');
    const roomIdInput   = document.getElementById('room_id');

    const summaryStudent   = document.getElementById('summaryStudent');
    const summaryRoom      = document.getElementById('summaryRoom');

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
        if (student.room_id) roomIdInput.value = student.room_id;
        resultsBox.classList.add('d-none');

        selectedName.textContent = student.name;
        selectedRoom.textContent = 'Kamar: ' + (student.room_name || '—');
        selectedInfo.classList.remove('d-none');

        summaryStudent.textContent = student.name;
        summaryRoom.textContent = student.room_name || '—';
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

    // ── Category + Points auto summary ─────────────────────────
    document.getElementById('violation_category').addEventListener('change', function() {
        const map = { ringan: 'Ringan', sedang: 'Sedang', berat: 'Berat' };
        document.getElementById('summaryCategory').textContent = map[this.value] || '—';
    });

    document.getElementById('points').addEventListener('input', function() {
        document.getElementById('summaryPoints').textContent = this.value ? this.value + ' poin' : '—';
    });

    // ── Pre-load if editing (old value) ─────────────────────────
    if (oldStudentId) {
        fetch('/api/dormitory/' + dormitoryId + '/residents/find-student?q=' + encodeURIComponent(oldStudentId))
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