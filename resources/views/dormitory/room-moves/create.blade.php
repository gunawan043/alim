@extends('layouts.master')
@section('title') Ajukan Mutasi Kamar — Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.index', ['userId' => $userId]) }}">Daftar Asrama</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name ?? 'Asrama' }}</a> @endslot
        @slot('li_4') Mutasi Kamar @endslot
        @slot('title') Ajukan Mutasi @endslot
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
          action="{{ route('user.asrama.room-moves.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
          id="roomMoveForm"
          novalidate>
        @csrf

        <div class="row">
            {{-- ============================================================
                 LEFT COLUMN — SANTRI & KAMAR
            ============================================================ --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ri-exchange-line me-2 text-primary"></i>Form Permohonan Mutasi Kamar
                        </h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Asrama: <strong>{{ $dormitory->name ?? 'Asrama' }}</strong>
                        </p>
                    </div>
                    <div class="card-body">

                        {{-- ============================================================
                             STUDENT SEARCH
                        ============================================================ --}}
                        <div class="mb-4">
                            <label class="form-label">
                                Cari Santri <span class="text-danger">*</span>
                            </label>

                            <input type="hidden" name="resident_id" id="selectedResidentId" value="{{ old('resident_id') }}">

                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border">
                                        <i class="ri-search-line text-muted"></i>
                                    </span>
                                    <input type="text"
                                           id="studentSearch"
                                           class="form-control"
                                           placeholder="Ketik nama atau NISN untuk mencari..."
                                           value="{{ old('student_search') }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="display:none;">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>

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

                            @error('resident_id')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ============================================================
                             ROOMS
                        ============================================================ --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Kamar Asal <span class="text-danger">*</span>
                                </label>
                                <div id="fromRoomBadge" class="alert alert-secondary py-2 px-3 mb-0 d-flex align-items-center gap-2" style="display: none;">
                                    <i class="ri-home-4-line text-secondary"></i>
                                    <span id="fromRoomName" class="fw-semibold"></span>
                                </div>
                                <input type="hidden" name="from_room_id" id="fromRoomId" value="{{ old('from_room_id') }}">
                                <div id="fromRoomPlaceholder" class="form-control bg-light text-muted">
                                    Pilih santri terlebih dahulu
                                </div>
                                @error('from_room_id')
                                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="to_room_id">
                                    Kamar Tujuan <span class="text-danger">*</span>
                                </label>
                                <select name="to_room_id"
                                        id="to_room_id"
                                        class="form-select @error('to_room_id') is-invalid @enderror"
                                        required>
                                    <option value="">-- Pilih Kamar --</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}"
                                                {{ old('to_room_id') == $room->id ? 'selected' : '' }}
                                                data-occupancy="{{ $room->current_occupancy ?? 0 }}"
                                                data-capacity="{{ $room->capacity ?? 0 }}">
                                            {{ $room->name }}
                                            ({{ $room->current_occupancy ?? 0 }}/{{ $room->capacity ?? 0 }} orang)
                                            @if(($room->current_occupancy ?? 0) >= ($room->capacity ?? 0))
                                                <span class="text-danger"> — Penuh</span>
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_room_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div id="toRoomOccupancyInfo" class="mt-2" style="display: none;">
                                    <div class="progress" style="height: 6px;">
                                        <div id="toRoomOccupancyBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">
                                        Terisi: <span id="toRoomOccupancyText"></span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================
                             MOVE DETAILS
                        ============================================================ --}}
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label" for="move_date">
                                    Tanggal Pindah <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       name="move_date"
                                       id="move_date"
                                       class="form-control @error('move_date') is-invalid @enderror"
                                       value="{{ old('move_date', now()->toDateString()) }}"
                                       required>
                                @error('move_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="move_type">
                                    Jenis Mutasi <span class="text-danger">*</span>
                                </label>
                                <select name="move_type"
                                        id="move_type"
                                        class="form-select @error('move_type') is-invalid @enderror"
                                        required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="reguler" {{ old('move_type') === 'reguler' ? 'selected' : '' }}>Reguler</option>
                                    <option value="disciplinary" {{ old('move_type') === 'disciplinary' ? 'selected' : '' }}>Disipliner</option>
                                    <option value="medical" {{ old('move_type') === 'medical' ? 'selected' : '' }}>Medis</option>
                                    <option value="upgrade" {{ old('move_type') === 'upgrade' ? 'selected' : '' }}>Upgrade Kamar</option>
                                    <option value="other" {{ old('move_type') === 'other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('move_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-12">
                                <label class="form-label" for="reason">
                                    Alasan Pindah <span class="text-danger">*</span>
                                </label>
                                <textarea name="reason"
                                          id="reason"
                                          class="form-control @error('reason') is-invalid @enderror"
                                          rows="3"
                                          placeholder="Jelaskan alasan permohonan mutasi kamar...">{{ old('reason') }}</textarea>
                                @error('reason')
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
                                      placeholder="Catatan tambahan jika ada...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="ri-information-line text-primary me-2"></i>Petunjuk
                        </h6>
                        <ul class="ps-3 mb-0 small">
                            <li class="mb-2">Cari dan pilih santri yang akan dipindahkan menggunakan kolom pencarian.</li>
                            <li class="mb-2">Kamar asal akan terisi otomatis berdasarkan kamar terakhir santri.</li>
                            <li class="mb-2">Pastikan kamar tujuan masih memiliki kapasitas kosong.</li>
                            <li class="mb-2">Pilih jenis mutasi yang sesuai: reguler, disipliner, medis, upgrade, atau lainnya.</li>
                            <li>Jelaskan alasan perpindahan secara singkat dan jelas.</li>
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
                        <a href="{{ route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                           class="btn btn-light">
                            <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                        </a>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="ri-reset-right-line align-middle me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-success" id="submitBtn">
                                <i class="ri-send-plane-line align-middle me-1"></i> Ajukan Permohonan
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

    var searchInput     = document.getElementById('studentSearch');
    var resultsDropdown = document.getElementById('searchResults');
    var selectedBadge   = document.getElementById('selectedStudentBadge');
    var selectedIdInput = document.getElementById('selectedResidentId');
    var selectedNameEl  = document.getElementById('selectedStudentName');
    var selectedNisnEl  = document.getElementById('selectedStudentNisn');
    var clearSearchBtn  = document.getElementById('clearSearchBtn');
    var clearSelBtn     = document.getElementById('clearSelectionBtn');
    var fromRoomBadge   = document.getElementById('fromRoomBadge');
    var fromRoomName    = document.getElementById('fromRoomName');
    var fromRoomIdInput = document.getElementById('fromRoomId');
    var fromRoomPh      = document.getElementById('fromRoomPlaceholder');
    var toRoomSelect    = document.getElementById('to_room_id');
    var toOccInfo       = document.getElementById('toRoomOccupancyInfo');
    var toOccBar        = document.getElementById('toRoomOccupancyBar');
    var toOccText       = document.getElementById('toRoomOccupancyText');

    // --- Student search ---
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
            var url = '/asrama/' + asramaUuid + '/penghuni/find-student?q=' + encodeURIComponent(q) + '&userId=' + userId;
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
            item.innerHTML = '<div><div class="fw-semibold">' + s.name + '</div><div class="small text-muted">NISN: ' + (s.nisn ?? '-') + '</div></div>' + genderBadge;
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

        selectedIdInput.value = student.id;
        selectedNameEl.textContent = student.name;
        selectedNisnEl.textContent = student.nisn ?? '-';

        selectedBadge.style.display = 'flex';
        resultsDropdown.style.display = 'none';
        clearSearchBtn.style.display = 'block';

        // Auto-fill from_room
        if (student.room_id) {
            fromRoomIdInput.value = student.room_id;
            fromRoomName.textContent = student.room_name || 'Kamar #' + student.room_id;
            fromRoomBadge.style.display = 'flex';
            fromRoomPh.style.display = 'none';
        } else {
            fromRoomIdInput.value = '';
            fromRoomBadge.style.display = 'none';
            fromRoomPh.style.display = 'block';
            fromRoomPh.textContent = 'Santri belum memiliki kamar';
        }
    }

    function clearSelection() {
        searchInput.value = '';
        searchInput.readOnly = false;
        searchInput.classList.remove('bg-light');
        selectedIdInput.value = '';
        selectedBadge.style.display = 'none';
        clearSearchBtn.style.display = 'none';
        resultsDropdown.style.display = 'none';
        fromRoomIdInput.value = '';
        fromRoomBadge.style.display = 'none';
        fromRoomPh.style.display = 'block';
        fromRoomPh.textContent = 'Pilih santri terlebih dahulu';
    }

    clearSearchBtn.addEventListener('click', clearSelection);
    clearSelBtn.addEventListener('click', clearSelection);

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
            resultsDropdown.style.display = 'none';
        }
    });

    // --- To-room occupancy indicator ---
    function updateToRoomOccupancy() {
        var opt = toRoomSelect.options[toRoomSelect.selectedIndex];
        if (!opt || !opt.value) {
            toOccInfo.style.display = 'none';
            return;
        }
        var current = parseInt(opt.dataset.occupancy || 0, 10);
        var capacity = parseInt(opt.dataset.capacity || 1, 10);
        var pct = Math.min(100, Math.round((current / capacity) * 100));
        var barClass = pct >= 90 ? 'bg-danger' : pct >= 60 ? 'bg-warning' : 'bg-success';

        toOccBar.className = 'progress-bar ' + barClass;
        toOccBar.style.width = pct + '%';
        toOccText.textContent = current + ' dari ' + capacity + ' bed (' + pct + '%)';
        toOccInfo.style.display = 'block';
    }

    toRoomSelect.addEventListener('change', updateToRoomOccupancy);

    // Restore old() state on load
    if (selectedIdInput.value) {
        selectedBadge.style.display = 'flex';
        updateToRoomOccupancy();
    }

    // --- Form validation before submit ---
    document.getElementById('roomMoveForm').addEventListener('submit', function(e) {
        if (!selectedIdInput.value) {
            e.preventDefault();
            alert('Silakan pilih santri terlebih dahulu.');
            searchInput.focus();
            return;
        }
        if (!fromRoomIdInput.value) {
            e.preventDefault();
            alert('Santri belum memiliki kamar asal. Pilih другой santuario.');
            return;
        }
    });
})();
</script>
@endsection
