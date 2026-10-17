@extends('layouts.master')
@section('title') Tempatkan Santri - Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_4') Santri @endslot
        @slot('title') Tempatkan Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2" aria-hidden="true"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2" aria-hidden="true"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2" aria-hidden="true"></i>Terjadi kesalahan pada formulir. Silakan perbaiki input Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.asrama.residents.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
          id="residentForm"
          novalidate>
        @csrf

        <div class="row">
            {{-- ============================================================
                 LEFT COLUMN — IDENTITAS & KAMAR
            ============================================================ --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ri-user-location-line me-2 text-primary"></i>Form Penempatan Santri
                        </h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Asrama: <strong>{{ $dormitory->name }}</strong>
                        </p>
                    </div>
                    <div class="card-body">

                        {{-- ============================================================
                             STUDENT SEARCH
                        ============================================================ --}}
                        <div class="mb-4">
                            <label class="form-label">
                                Pilih Santri dari Akademik <span class="text-danger">*</span>
                            </label>

                            {{-- Hidden inputs for selected student --}}
                            <input type="hidden" name="student_id" id="selectedStudentId" value="{{ old('student_id') }}">

                            <div class="position-relative">
                                <label for="studentSearch" class="visually-hidden">Cari Santri</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border">
                                        <i class="ri-search-line text-muted" aria-hidden="true"></i>
                                    </span>
                                    <input type="text"
                                           id="studentSearch"
                                           name="student_search"
                                           class="form-control"
                                           placeholder="Cari nama atau NISN untuk menempatkan santri..."
                                           value="{{ old('student_search') }}"
                                           autocomplete="off"
                                           aria-describedby="studentSearchHelp">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="display:none;" aria-label="Hapus pencarian siswa">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                                <small id="studentSearchHelp" class="form-text text-muted">Ketik minimal 2 karakter. Data ini dibaca langsung dari Modul Akademik — tidak membuat data siswa baru.</small>

                                {{-- Dropdown results --}}
                                <div id="searchResults" class="list-group position-absolute w-100 mt-1 shadow-lg rounded-3 overflow-auto" style="max-height: 260px; z-index: 1050; display: none;"></div>
                            </div>

                            {{-- Selected student badge --}}
                            <div id="selectedStudentBadge" class="d-flex align-items-center mt-2" style="display: none;">
                                <div class="alert alert-success py-2 px-3 mb-0 d-flex align-items-center gap-2 w-100">
                                    <i class="ri-user-follow-line text-success"></i>
                                    <span id="selectedStudentName" class="fw-semibold"></span>
                                    <span class="text-muted small ms-auto">
                                        NISN: <code id="selectedStudentNisn"></code>
                                    </span>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" id="clearSelectionBtn" title="Hapus pilihan">
                                        <i class="ri-close-circle-fill"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Validation error --}}
                            @error('student_id')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ============================================================
                             ROOM & BED
                        ============================================================ --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="room_id">
                                    Kamar <span class="text-danger">*</span>
                                </label>
                                <select name="room_id"
                                        id="room_id"
                                        class="form-select @error('room_id') is-invalid @enderror"
                                        required>
                                    <option value="">-- Pilih Kamar --</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}"
                                                {{ old('room_id') == $room->id ? 'selected' : '' }}
                                                data-occupancy="{{ $room->current_occupancy ?? 0 }}"
                                                data-capacity="{{ $room->capacity ?? 0 }}">
                                            {{ $room->name }}
                                            @if(($room->current_occupancy ?? 0) >= ($room->capacity ?? 0))
                                                <span class="text-danger"> (Penuh)</span>
                                            @else
                                                ({{ $room->current_occupancy ?? 0 }}/{{ $room->capacity ?? 0 }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('room_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                {{-- Room occupancy indicator --}}
                                <div id="roomOccupancyInfo" class="mt-2" style="display: none;">
                                    <div class="progress" style="height: 6px;">
                                        <div id="roomOccupancyBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">
                                        Terisi: <span id="roomOccupancyText"></span>
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="bed_number">
                                    No. Bed <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       name="bed_number"
                                       id="bed_number"
                                       class="form-control @error('bed_number') is-invalid @enderror"
                                       placeholder="1"
                                       min="1"
                                       max="20"
                                       value="{{ old('bed_number') }}"
                                       required>
                                @error('bed_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="check_in_date">
                                    Tgl. Penempatan <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       name="check_in_date"
                                       id="check_in_date"
                                       class="form-control @error('check_in_date') is-invalid @enderror"
                                       value="{{ old('check_in_date', now()->toDateString()) }}"
                                       required>
                                @error('check_in_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 RIGHT COLUMN — NOTES & HELP
            ============================================================ --}}
            <div class="col-lg-4">
                {{-- Notes --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ri-sticky-note-add-line me-2 text-primary"></i>Catatan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="notes">Catatan (opsional)</label>
                            <textarea name="notes"
                                      id="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      rows="4"
                                      placeholder="Catatan tambahan saat penempatan, misalnya kondisi kesehatan, kebutuhan khusus, dll.">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Info box --}}
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="ri-information-line text-primary me-2"></i>Petunjuk
                        </h6>
                        <ul class="ps-3 mb-0 small">
                            <li class="mb-2">Pilih santri dari Modul Akademik melalui kolom pencarian — asrama tidak membuat data siswa baru.</li>
                            <li class="mb-2">Pastikan kamar yang dipilih masih memiliki kapasitas kosong.</li>
                            <li class="mb-2">Bed number adalah nomor tempat tidur di dalam kamar (1, 2, 3, dst).</li>
                            <li class="mb-2">Tanggal penempatan akan diisi otomatis dengan hari ini.</li>
                            <li>Semua identitas (nama, NISN, foto, dll) dibaca langsung dari Modul Akademik.</li>
                            <li>Satu kamar hanya bisa dihuni oleh satu gender.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             ACTION BUTTONS
        ============================================================ --}}
        <div class="row mt-3">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <a href="{{ route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-light">
                            <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                        </a>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="ri-reset-right-line align-middle me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="ri-user-add-line align-middle me-1"></i> Tempatkan Santri
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
(function() {
    'use strict';

    var asramaUuid = {{ Js::from($dormitory->id) }};
    var userId = {{ Js::from($userId) }};
    var searchTimeout = null;

    var searchInput      = document.getElementById('studentSearch');
    var resultsDropdown  = document.getElementById('searchResults');
    var selectedBadge    = document.getElementById('selectedStudentBadge');
    var selectedIdInput  = document.getElementById('selectedStudentId');
    var selectedNameEl   = document.getElementById('selectedStudentName');
    var selectedNisnEl   = document.getElementById('selectedStudentNisn');
    var clearSearchBtn   = document.getElementById('clearSearchBtn');
    var clearSelBtn      = document.getElementById('clearSelectionBtn');
    var roomSelect       = document.getElementById('room_id');
    var occupancyInfo    = document.getElementById('roomOccupancyInfo');
    var occupancyBar     = document.getElementById('roomOccupancyBar');
    var occupancyText    = document.getElementById('roomOccupancyText');

    // --- Search student ---
    searchInput.addEventListener('input', function(e) {
        var q = e.target.value.trim();
        clearSearchBtn.style.display = q.length > 0 ? 'block' : 'none';

        if (q.length < 2) {
            resultsDropdown.style.display = 'none';
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            fetchStudent(q);
        }, 350);
    });

    async function fetchStudent(q) {
        try {
            var url = '/{userId}/asrama/' + asramaUuid + '/santri/find?q=' + encodeURIComponent(q);
            var res = await fetch(url);
            var data = await res.json();

            if (data.results && data.results.length > 0) {
                renderResults(data.results);
            } else {
                renderEmpty('Tidak ada hasil untuk "' + q + '"');
            }
        } catch (err) {
            renderEmpty('Gagal mencari data. Coba lagi.');
        }
    }

    function renderResults(students) {
        resultsDropdown.innerHTML = '';
        students.forEach(function(s) {
            var genderBadge = s.gender === 'L'
                ? '<span class="badge bg-primary-subtle text-primary ms-2">L</span>'
                : '<span class="badge bg-danger-subtle text-danger ms-2">P</span>';
            var item = document.createElement('a');
            item.href = '#';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start py-2 px-3';
            item.innerHTML = `
                <div>
                    <div class="fw-semibold">${s.name}</div>
                    <div class="small text-muted">
                        NISN: ${s.nisn ?? '-'} ${s.nis ? ' | NIS: ' + s.nis : ''}
                    </div>
                </div>
                ${genderBadge}
            `;
            item.addEventListener('click', function(e) {
                e.preventDefault();
                selectStudent(s);
            });
            resultsDropdown.appendChild(item);
        });
        resultsDropdown.style.display = 'block';
    }

    function renderEmpty(msg) {
        resultsDropdown.innerHTML = '<div class="list-group-item text-muted py-3 text-center">' + msg + '</div>';
        resultsDropdown.style.display = 'block';
    }

    function selectStudent(student) {
        searchInput.value = student.name;
        searchInput.readOnly = true;
        searchInput.classList.add('bg-light');

        selectedIdInput.value   = student.id;
        selectedNameEl.textContent = student.name;
        selectedNisnEl.textContent = student.nisn ?? '-';

        selectedBadge.style.display = 'flex';
        resultsDropdown.style.display = 'none';
        clearSearchBtn.style.display = 'block';

        // Trigger room occupancy refresh
        updateRoomOccupancy();
    }

    function clearSelection() {
        searchInput.value = '';
        searchInput.readOnly = false;
        searchInput.classList.remove('bg-light');
        selectedIdInput.value = '';
        selectedBadge.style.display = 'none';
        clearSearchBtn.style.display = 'none';
        resultsDropdown.style.display = 'none';
    }

    clearSearchBtn.addEventListener('click', clearSelection);
    clearSelBtn.addEventListener('click', clearSelection);

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
            resultsDropdown.style.display = 'none';
        }
    });

    // --- Room occupancy indicator ---
    function updateRoomOccupancy() {
        var opt = roomSelect.options[roomSelect.selectedIndex];
        if (!opt || !opt.value) {
            occupancyInfo.style.display = 'none';
            return;
        }
        var current = parseInt(opt.dataset.occupancy || 0, 10);
        var capacity = parseInt(opt.dataset.capacity || 1, 10);
        var pct = Math.min(100, Math.round((current / capacity) * 100));
        var barClass = pct >= 90 ? 'bg-danger' : pct >= 60 ? 'bg-warning' : 'bg-success';

        occupancyBar.className = 'progress-bar ' + barClass;
        occupancyBar.style.width = pct + '%';
        occupancyText.textContent = current + ' dari ' + capacity + ' bed (' + pct + '%)';
        occupancyInfo.style.display = 'block';
    }

    roomSelect.addEventListener('change', updateRoomOccupancy);

    // Re-apply on page load for old() values
    if (selectedIdInput.value) {
        // If old student_id is set, restore the badge
        // (The name is not in a hidden field, so user sees the search input filled from old input)
        selectedBadge.style.display = 'flex';
        updateRoomOccupancy();
    }
})();
</script>
@endsection