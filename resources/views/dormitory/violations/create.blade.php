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
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.violations.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
        @csrf
        <input type="hidden" name="room_id"    id="room_id"    value="{{ old('room_id') }}">

        <div class="row">
            {{-- Left Column --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-error-warning-line me-2 text-danger"></i>Form Pelanggaran</h5>
                    </div>
                    <div class="card-body">

                        {{-- Point Catalog --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Jenis Pelanggaran (Katalog) <span class="text-danger">*</span>
                            </label>
                            <select name="point_catalog_id" id="point_catalog_id"
                                    class="form-select @error('point_catalog_id') is-invalid @enderror" required onchange="window.syncViolationCatalog && window.syncViolationCatalog()">
                                <option value="">-- Pilih katalog pelanggaran --</option>
                                @foreach($catalogs as $catalog)
                                    <option value="{{ $catalog->id }}"
                                            data-name="{{ $catalog->name }}"
                                            data-category="{{ $catalog->severityCategory() }}"
                                            data-points="{{ $catalog->points_min }}"
                                            data-permanent="{{ $catalog->is_permanent ? '1' : '0' }}"
                                            @selected(old('point_catalog_id') === $catalog->id)>
                                        {{ $catalog->code }} — {{ $catalog->name }} ({{ $catalog->points_min }} poin)
                                    </option>
                                @endforeach
                            </select>
                            @error('point_catalog_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($catalogs->isEmpty())
                                <div class="alert alert-warning py-2 mt-2 mb-0 small">
                                    <i class="ri-alert-line me-1"></i>Belum ada katalog pelanggaran aktif. Hubungi Superadmin untuk menambahkan katalog.
                                </div>
                            @endif
                            <div class="form-text">Poin, kategori, dan tindakan otomatis mengikuti katalog resmi.</div>
                        </div>

                        {{-- Student Dropdown --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Santri <span class="text-danger">*</span></label>
                            <select name="student_id" id="studentSelect" class="form-select @error('student_id') is-invalid @enderror" required onchange="window.syncViolationStudent && window.syncViolationStudent()">
                                <option value="">-- Cari dan pilih santri --</option>
                                @foreach($residents as $resident)
                                    @if($resident->student)
                                        <option value="{{ $resident->student->id }}"
                                                data-room-id="{{ $resident->room_id }}"
                                                data-room-name="{{ $resident->room?->name ?? 'Tanpa Kamar' }}"
                                                @selected(old('student_id') === $resident->student->id)>
                                            {{ $resident->student->name }} — {{ $resident->student->nisn ?? 'N/A' }} — {{ $resident->room?->name ?? 'Tanpa Kamar' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('student_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <div id="selectedStudentInfo" class="mt-2 {{ old('student_id') ? '' : 'd-none' }}">
                                <div class="alert alert-success py-2 mb-0">
                                    <i class="ri-user-follow-line me-1"></i>
                                    <span id="selectedStudentName"></span>
                                    <span class="text-muted ms-1">—</span>
                                    <span id="selectedStudentRoom" class="text-muted ms-1"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Violation Date + Category --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Poin Pelanggara
                                </label>
                                <input type="number" name="points" id="points"
                                       class="form-control @error('points') is-invalid @enderror"
                                       placeholder="Mengikuti katalog resmi."
                                       value="{{ old('points') }}" readonly>
                                @error('points')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Violation Type --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Nama Pelanggaran <span class="text-muted fw-normal">(otomatis dari katalog)</span>
                            </label>
                            <input type="text" name="violation_type" id="violation_type"
                                   class="form-control @error('violation_type') is-invalid @enderror"
                                   placeholder="Pilih katalog pelanggaran di atas"
                                   value="{{ old('violation_type') }}" readonly>
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


                        {{-- Action Taken --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tindakan yang Diberikan</label>
                            <textarea name="action_taken" id="action_taken"
                                      class="form-control @error('action_taken') is-invalid @enderror"
                                      rows="2" placeholder="Contoh: Teguran lisan, Pencatatan di buku pelanggaran, Surat peringatan">{{ old('action_taken') }}</textarea>
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

                {{-- Notification option --}}
                <div class="card mt-3">
                    <div class="card-body py-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="send_notification" value="0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="send_notification" name="send_notification" value="1"
                                   {{ old('send_notification', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="send_notification">
                                Kirim notifikasi ke wali santri
                            </label>
                        </div>
                        <div class="form-text mt-1">
                            Kirim WhatsApp ke wali/terpusat. Matikan jika pencatatan ini tidak perlu diberitahukan.
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
(function () {
    'use strict';

    const catalogSelect = document.getElementById('point_catalog_id');
    const categorySelect = document.getElementById('violation_category');
    const typeInput = document.getElementById('violation_type');
    const pointsInput = document.getElementById('points');
    const studentSelect = document.getElementById('studentSelect');
    const roomInput = document.getElementById('room_id');

    function syncCatalogFields() {
        if (!catalogSelect) return;
        const option = catalogSelect.options[catalogSelect.selectedIndex];
        if (!option || !option.value) return;

        const category = option.getAttribute('data-category') || '';
        const points = option.getAttribute('data-points') || '';

        if (typeInput) typeInput.value = option.getAttribute('data-name') || '';
        if (pointsInput) pointsInput.value = points;
        if (categorySelect) categorySelect.value = category;

        const map = { ringan: 'Ringan', sedang: 'Sedang', berat: 'Berat' };
        const summaryCategory = document.getElementById('summaryCategory');
        const summaryPoints = document.getElementById('summaryPoints');
        if (summaryCategory) summaryCategory.textContent = map[category] || '—';
        if (summaryPoints) summaryPoints.textContent = points ? points + ' poin' : '—';
    }

    window.syncViolationCatalog = function () { syncCatalogFields(); };
    if (catalogSelect) {
        catalogSelect.addEventListener('change', syncCatalogFields);
        if (window.jQuery && jQuery.fn.select2) {
            jQuery(catalogSelect).select2({ width: '100%', placeholder: 'Cari kode atau nama pelanggaran...', allowClear: true });
            jQuery(catalogSelect).on('select2:select select2:clear', syncCatalogFields);
        }
        syncCatalogFields();
    }

    function syncStudent() {
        if (!studentSelect) return;
        const option = studentSelect.options[studentSelect.selectedIndex];
        const has = option && option.value;
        if (roomInput) roomInput.value = has ? (option.getAttribute('data-room-id') || '') : '';
        const info = document.getElementById('selectedStudentInfo');
        const name = document.getElementById('selectedStudentName');
        const room = document.getElementById('selectedStudentRoom');
        const summaryStudent = document.getElementById('summaryStudent');
        const summaryRoom = document.getElementById('summaryRoom');
        if (info) info.classList.toggle('d-none', !has);
        if (has) {
            if (name) name.textContent = option.textContent.split(' — ')[0];
            if (room) room.textContent = 'Kamar: ' + (option.getAttribute('data-room-name') || '—');
            if (summaryStudent) summaryStudent.textContent = option.textContent.split(' — ')[0];
            if (summaryRoom) summaryRoom.textContent = option.getAttribute('data-room-name') || '—';
        }
    }

    window.syncViolationStudent = syncStudent;
    if (studentSelect) {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery(studentSelect).select2({ width: '100%', placeholder: 'Cari nama santri...', allowClear: true });
            jQuery(studentSelect).on('select2:select select2:clear', syncStudent);
        }
        studentSelect.addEventListener('change', syncStudent);
        syncStudent();
    }
})();
</script>
@endsection