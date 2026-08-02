@extends('layouts.master')
@section('title') Ajukan Perizinan @endsection

@section('css')
<style>
.resident-combobox {
    position: relative;
}
.resident-combobox-input {
    padding-right: 2.25rem;
    cursor: pointer;
}
.resident-combobox-arrow {
    position: absolute;
    right: .85rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.1rem;
    color: #878a99;
    pointer-events: none;
}
.resident-combobox-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    border: 1px solid #ced4da;
    border-top: none;
    border-radius: 0 0 .375rem .375rem;
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
    max-height: 280px;
    overflow-y: auto;
}
.resident-combobox-menu .form-select-inner {
    padding: 0;
    margin: 0;
    list-style: none;
}
.resident-option {
    padding: .5rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f3f3f5;
    transition: background-color .12s ease;
    line-height: 1.5;
}
.resident-option:last-child { border-bottom: none; }
.resident-option:hover,
.resident-option.is-active {
    background-color: #f3f6fb;
}
.resident-option .opt-name { font-weight: 600; color: #212529; }
.resident-option .opt-meta { font-size: .8125rem; color: #878a99; }
[data-bs-theme="dark"] .resident-combobox-menu { background-color: #1e2235; border-color: #2d3045; }
[data-bs-theme="dark"] .resident-option { border-bottom-color: #2d3045; }
[data-bs-theme="dark"] .resident-option .opt-name { color: #e9ebec; }
[data-bs-theme="dark"] .resident-option:hover,
[data-bs-theme="dark"] .resident-option.is-active { background-color: #2d3045; }
</style>
@endsection

@section('content')
    @php
        // Peta izin aktif per-student (student_id => info) untuk banner warning.
        $activePermitsIndex = $activePermits->map(function ($p) {
            return [
                'permit_type' => $p->permit_type,
                'permit_type_text' => $p->permit_type_text ?? $p->permit_type,
                'status' => $p->status,
                'departure_datetime' => optional($p->departure_datetime)->toIso8601String(),
                'expected_return_datetime' => optional($p->expected_return_datetime)->toIso8601String(),
                'destination' => $p->destination,
            ];
        });

        // Bangun struktur untuk JS: array berisi semua resident dengan mahrom-nya.
        $residentIndex = $residents->map(function ($r) {
            $studyGroup = $r->student->currentClassHistory?->studyGroup;
            return [
                'id'        => (string) $r->student->id,
                'resident_id' => (string) $r->id,
                'room_id'   => (string) ($r->room_id ?? ''),
                'room_name' => $r->room?->name ?? '—',
                'name'      => $r->student->name ?? '(Tanpa Nama)',
                'nisn'      => $r->student->nisn ?? '',
                'classroom' => $studyGroup?->full_name,
                'mahroms'   => $r->student->mahroms->map(function ($m) {
                    return [
                        'id'       => (string) $m->id,
                        'name'     => $m->name,
                        'relation' => $m->relation,
                        'phone'    => $m->phone,
                    ];
                })->values(),
            ];
        })->keyBy('id')->values();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('title') Ajukan Izin @endslot
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

    <form id="permitCreateForm" method="POST"
          action="{{ route('user.asrama.permits.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
          enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id') }}">
        <input type="hidden" name="room_id" id="room_id" value="{{ old('room_id') }}">

        <div class="row">
            {{-- Left Column: Student & Permit Info --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-file-list-line me-2 text-primary"></i>Form Perizinan</h5>
                    </div>
                    <div class="card-body">

                        {{-- Pilih Santri --}}
                        <div class="mb-4 position-relative">
                            <label class="form-label fw-semibold">
                                Santri <span class="text-danger">*</span>
                            </label>
                            <div class="resident-combobox" id="resident-combobox">
                                <input type="text" id="resident-search"
                                       class="form-control resident-combobox-input @error('student_id') is-invalid @enderror"
                                       placeholder="— Pilih Santri —"
                                       autocomplete="off">
                                <i class="ri-arrow-down-s-line resident-combobox-arrow"></i>
                            </div>
                            <div id="resident-list" class="resident-combobox-menu" style="display: none;">
                                <div id="resident-list-items"></div>
                                <div id="resident-empty" class="px-3 py-2 small text-muted" style="display: none;">
                                    Tidak ada nama yang cocok.
                                </div>
                            </div>
                            @error('student_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div id="selectedStudentInfo" class="mt-2 {{ old('student_id') ? '' : 'd-none' }}">
                                <div class="alert alert-success py-2 d-flex align-items-center gap-2 mb-0">
                                    <i class="ri-user-follow-line"></i>
                                    <span id="selectedStudentName"></span>
                                    <span class="text-muted">—</span>
                                    <span id="selectedStudentRoom" class="text-muted"></span>
                                </div>
                            </div>

                            {{-- Banner peringatan: santri masih memiliki izin aktif --}}
                            <div id="activePermitBanner" class="mt-2 d-none">
                                <div class="alert alert-warning py-2 d-flex align-items-start gap-2 mb-0">
                                    <i class="ri-alarm-warning-line fs-5 mt-1"></i>
                                    <div>
                                        <strong>Santri masih memiliki izin yang belum selesai.</strong>
                                        <div id="activePermitBannerDetail" class="small mt-1"></div>
                                        <small class="text-muted">Selesaikan izin sebelumnya sebelum mengajukan izin baru.</small>
                                    </div>
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
                                    <option value="darurat" {{ old('permit_type') == 'darurat' ? 'selected' : '' }}>🚨 Darurat</option>
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

                        <div id="sakitWarning" class="alert alert-warning mb-4 {{ old('permit_type') == 'sakit' ? '' : 'd-none' }}">
                            <i class="ri-alarm-warning-line me-2"></i>
                            <strong>Perhatian:</strong> Izin sakit memerlukan keterangan dari UKS yang sudah disetujui.
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Keperluan / Keterangan</label>
                            <textarea name="purpose" id="purpose" class="form-control @error('purpose') is-invalid @enderror"
                                      rows="3" placeholder="Jelaskan alasan atau keperluan izin...">{{ old('purpose') }}</textarea>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

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
            {{-- Emergency Override (toggle between regular & emergency) --}}
            <div id="emergency-card" style="display: none;">
                <div class="card border-danger mb-3">
                    <div class="card-header bg-danger-subtle">
                        <h5 class="card-title mb-0 text-danger">
                            <i class="ri-alarm-warning-line me-2"></i>Izin Darurat
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger py-2 d-flex align-items-start gap-2 mb-3">
                            <i class="ri-alarm-warning-line fs-5 mt-1"></i>
                            <div>
                                <strong>Izin darurat memerlukan persetujuan khusus dari Kepala Asrama.</strong><br>
                                <small class="text-muted">Notifikasi WhatsApp akan dikirim otomatis ke Kepala Asrama setelah izin ini diajukan.</small>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Kontak Darurat <span class="text-danger">*</span></label>
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                       class="form-control @error('emergency_contact_name') is-invalid @enderror"
                                       placeholder="Nama orang/galang yang dihubungi" value="{{ old('emergency_contact_name') }}">
                                @error('emergency_contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. HP Kontak Darurat <span class="text-danger">*</span></label>
                                <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                                       class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                                       placeholder="08xxxxxxxxxx" value="{{ old('emergency_contact_phone') }}">
                                @error('emergency_contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
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

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Mahrom</label>
                            <select name="mahrom_id" id="mahrom_id" class="form-select">
                                <option value="">-- Tidak ada mahrom --</option>
                            </select>
                            <div class="form-text">Opsi tersedia setelah santri dipilih.</div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="companion_is_mahrom" id="companion_is_mahrom"
                                   class="form-check-input" value="1" {{ old('companion_is_mahrom', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="companion_is_mahrom">
                                Penjemput adalah Mahrom
                            </label>
                        </div>

                        <hr>

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

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan Tambahan</label>
                            <textarea name="notes" id="notes" class="form-control"
                                      rows="3" placeholder="Catatan tambahan jika ada...">{{ old('notes') }}</textarea>
                        </div>

                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="ri-send-plane-line me-1"></i> Ajukan Izin
                    </button>
                    <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="btn btn-light">Batal</a>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal Peringatan Kuota Pulang -->
    <div class="modal fade" id="quotaWarningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title">
                        <i class="ri-error-warning-line me-2"></i>Peringatan Kuota Pulang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="quotaWarningText" class="mb-2">
                        Kuota izin pulang untuk periode ini sudah terpakai penuh.
                    </p>
                    <p class="small text-muted mb-0">
                        Anda tetap dapat melanjutkan dengan mengganti jenis izin (misal “Darurat”) atau membatalkan pengajuan.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning" id="forceContinueBtn">
                        <i class="ri-arrow-right-line me-1"></i>Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
(function() {
    'use strict';

    // ── Data resident dari server (untuk lookup mahrom) ─────────
    const residentIndex = @json($residentIndex);
    const residentMap = {};
    residentIndex.forEach(function(r) { residentMap[r.id] = r; });

    // Data izin aktif per student (untuk banner warning)
    const activePermitsMap = @json($activePermitsIndex);

    const oldStudentId = document.getElementById('student_id').value;

    const residentCombo  = document.getElementById('resident-combobox');
    const residentMenu   = document.getElementById('resident-list');
    const residentItems  = document.getElementById('resident-list-items');
    const residentEmpty  = document.getElementById('resident-empty');
    const searchInput    = document.getElementById('resident-search');
    const studentIdInput = document.getElementById('student_id');
    const roomIdInput    = document.getElementById('room_id');
    const selectedInfo   = document.getElementById('selectedStudentInfo');
    const selectedName   = document.getElementById('selectedStudentName');
    const selectedRoom   = document.getElementById('selectedStudentRoom');
    const mahromSelect   = document.getElementById('mahrom_id');
    const companionName  = document.getElementById('companion_name');
    const companionRel   = document.getElementById('companion_relation');
    const companionPhone = document.getElementById('companion_phone');
    const companionIsMr  = document.getElementById('companion_is_mahrom');

    function applyResident(r) {
        studentIdInput.value = r.id;
        roomIdInput.value    = r.room_id || '';
        selectedName.textContent = r.name;
        selectedRoom.textContent = 'Kamar: ' + r.room_name;
        selectedInfo.classList.remove('d-none');

        // Banner izin aktif
        updateActivePermitBanner(r.id);

        populateMahroms(r.mahroms);
    }

    function updateActivePermitBanner(studentId) {
        const banner = document.getElementById('activePermitBanner');
        const detail = document.getElementById('activePermitBannerDetail');
        const info = activePermitsMap[studentId];
        if (!info) {
            banner.classList.add('d-none');
            detail.innerHTML = '';
            return;
        }
        const statusLabels = {
            'pending': 'Menunggu Persetujuan',
            'approved': 'Disetujui',
            'picked_up': 'Sudah Dijemput',
            'overdue': 'Terlambat Kembali',
        };
        const when = info.departure_datetime ? new Date(info.departure_datetime).toLocaleString('id-ID') : '—';
        const back = info.expected_return_datetime ? new Date(info.expected_return_datetime).toLocaleString('id-ID') : '—';
        detail.innerHTML =
            '<strong>' + (info.permit_type_text || info.permit_type) + '</strong> ' +
            '(' + (statusLabels[info.status] || info.status) + ')' +
            '<br>Berangkat: ' + when + ' &middot; Taksiran kembali: ' + back +
            (info.destination ? '<br>Tujuan: ' + info.destination : '');
        banner.classList.remove('d-none');
    }

    function populateMahroms(mahroms) {
        const oldMahromId = @json(old('mahrom_id'));
        mahromSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = mahroms.length === 0 ? '-- Santri ini belum punya mahrom --' : '-- Tidak ada mahrom --';
        mahromSelect.appendChild(placeholder);
        mahroms.forEach(function(m) {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name + ' (' + m.relation + ')';
            opt.dataset.name = m.name;
            opt.dataset.relation = m.relation;
            opt.dataset.phone = m.phone || '';
            if (m.id === oldMahromId) opt.selected = true;
            mahromSelect.appendChild(opt);
        });
        syncCompanionFromMahrom();
    }

    function syncCompanionFromMahrom() {
        if (!mahromSelect.value) return;
        const opt = mahromSelect.options[mahromSelect.selectedIndex];
        if (!opt || !opt.dataset.name) return;
        companionName.value  = opt.dataset.name;
        companionRel.value   = opt.dataset.relation || '';
        companionPhone.value = opt.dataset.phone || '';
        companionIsMr.checked = true;
    }

    function openMenu() {
        renderOptions(searchInput.value);
        residentMenu.style.display = 'block';
    }
    function closeMenu() {
        residentMenu.style.display = 'none';
    }

    function renderOptions(query) {
        const q = (query || '').trim().toLowerCase();
        residentItems.innerHTML = '';

        const matches = !q
            ? residentIndex.slice()
            : residentIndex.filter(function(r) {
                const name = (r.name || '').toLowerCase();
                const nisn = (r.nisn || '').toLowerCase();
                const cls  = (r.classroom || '').toLowerCase();
                return name.includes(q) || nisn.includes(q) || cls.includes(q);
            });

        if (matches.length === 0) {
            residentItems.style.display = 'none';
            residentEmpty.style.display = 'block';
            return;
        }
        residentItems.style.display = 'block';
        residentEmpty.style.display = 'none';

        matches.forEach(function(r) {
            const div = document.createElement('div');
            div.className = 'resident-option';
            div.dataset.id = r.id;
            const meta = [];
            if (r.nisn) meta.push('NISN: ' + r.nisn);
            if (r.classroom) meta.push('Kelas ' + r.classroom);
            if (r.room_name && r.room_name !== '—') meta.push('Kamar ' + r.room_name);
            div.innerHTML =
                '<div class="opt-name">' + (r.name || '(Tanpa Nama)') + '</div>' +
                '<div class="opt-meta">' + meta.join(' | ') + '</div>';
            residentItems.appendChild(div);
        });
    }

    searchInput.addEventListener('focus', openMenu);
    searchInput.addEventListener('click', openMenu);
    searchInput.addEventListener('input', function() {
        openMenu();
        // Bersihkan pilihan sebelumnya saat user mengetik ulang
        document.querySelectorAll('.resident-option.is-active').forEach(function(el) {
            el.classList.remove('is-active');
        });
        selectedInfo.classList.add('d-none');
        document.getElementById('activePermitBanner').classList.add('d-none');
        studentIdInput.value = '';
        roomIdInput.value = '';
        mahromSelect.innerHTML = '<option value="">-- Pilih santri lebih dulu --</option>';
    });

    // Klik item di menu
    residentMenu.addEventListener('click', function(e) {
        const opt = e.target.closest('.resident-option');
        if (!opt) return;
        const r = residentMap[opt.dataset.id];
        if (!r) return;
        applyResident(r);
        searchInput.value = r.name;
        closeMenu();
        // Tandai opsi aktif (untuk next open)
        document.querySelectorAll('.resident-option').forEach(function(el) {
            el.classList.toggle('is-active', el.dataset.id === r.id);
        });
    });

    // Tutup menu saat klik di luar
    document.addEventListener('click', function(e) {
        if (!residentCombo.contains(e.target) && !residentMenu.contains(e.target)) {
            closeMenu();
        }
    });

    // ── Pilih mahrom → auto-fill companion ──────────────────────
    mahromSelect.addEventListener('change', syncCompanionFromMahrom);

    companionIsMr.addEventListener('change', function() {
        if (this.checked) syncCompanionFromMahrom();
    });

    // ── Permit type: warning sakit + emergency toggle ───────────
    const permitTypeSel = document.getElementById('permit_type');
    const sakitWarning  = document.getElementById('sakitWarning');
    const emergencyCard = document.getElementById('emergency-card');
    permitTypeSel.addEventListener('change', function() {
        sakitWarning.classList.toggle('d-none', this.value !== 'sakit');
        // Show emergency card for darurat
        if (emergencyCard) {
            emergencyCard.style.display = this.value === 'darurat' ? '' : 'none';
        }
    });
    // Check old value on load
    if (emergencyCard && permitTypeSel.value === 'darurat') {
        emergencyCard.style.display = '';
    }

    // ── Default departure = waktu sekarang (dibulatkan ke 15 menit ke depan)
    const departureInput = document.getElementById('departure_datetime');
    if (departureInput && !departureInput.value) {
        const now = new Date();
        const remainder = now.getMinutes() % 15;
        const rounded = new Date(now.getTime() + (15 - remainder) * 60000);
        const pad = (n) => String(n).padStart(2, '0');
        departureInput.value = rounded.getFullYear() + '-' + pad(rounded.getMonth() + 1) + '-' + pad(rounded.getDate())
            + 'T' + pad(rounded.getHours()) + ':' + pad(rounded.getMinutes());
    }

    // ── Pre-load jika ada oldStudentId (mis. validasi gagal) ────
    if (oldStudentId && residentMap[oldStudentId]) {
        const r = residentMap[oldStudentId];
        applyResident(r);
        searchInput.value = r.name;
        renderOptions(r.name);
        document.querySelectorAll('.resident-option').forEach(function(el) {
            if (el.dataset.id === r.id) el.classList.add('is-active');
        });
        closeMenu();
    }

})();

// ── Tambah script penanganan peringatan kuota ────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('permitCreateForm');
    if (!form) return;

    const modal = document.getElementById('quotaWarningModal');
    const forceBtn = document.getElementById('forceContinueBtn');
    const warningText = document.getElementById('quotaWarningText');

    // URL endpoint pengecekkan kuota - di-set via Blade
    const quotaCheckUrl = "{{ route('user.asrama.permits.quota.check', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}";

    form.addEventListener('submit', async function (e) {
        // Jika form sudah dikonfirmasi melewati peringatan, lewati pengecekan
        if (form.getAttribute('data-quota-confirmed') === 'true') {
            form.removeAttribute('data-quota-confirmed');
            return;
        }

        const permitTypeSelect = document.querySelector('select[name="permit_type"]');
        const departureInput = document.querySelector('input[name="departure_datetime"]');
        const permitType = permitTypeSelect.value;
        const departureDatetime = departureInput?.value.trim();

        // Hanya lakukan cek kuota untuk izin jenis 'pulang'
        if (permitType === 'pulang' && departureDatetime) {
            const studentId = document.querySelector('input[name="student_id"]').value;
            if (!studentId) {
                alert('Belum memilih siswa.');
                e.preventDefault();
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

            try {
                const response = await fetch(quotaCheckUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        permit_type: permitType,
                        departure_datetime: departureDatetime
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.over) {
                        // Bangun pesan peringatan
                        const periodLabel = data.period_label_id || 'periode ini';
                        const used = data.used ?? 0;
                        const quota = data.quota ?? 0;
                        const remaining = data.remaining !== null ? data.remaining : 'tak terbatas';

                        let html = `Kuota izin pulang telah terpakai <strong>${used}/${quota}</strong> pada <strong>${periodLabel}</strong>.<br>`;
                        if (remaining !== 'tak terbatas' && remaining !== null && remaining !== undefined) {
                            html += `Hanya tersisa <strong>${remaining}</strong> slot.`;
                        } else {
                            html += `Sisa kuota tidak tersedia.`;
                        }
                        html += '<br><br>Apakah Anda tetap ingin melanjutkan? Anda bisa ubah jenis izin (misal “Darurat”) di dropdown di atas.';

                        warningText.innerHTML = html;
                        new bootstrap.Modal(modal).show();
                        e.preventDefault(); // blokir submit sampai user klik Lanjutkan
                        return;
                    }
                } else {
                    console.warn('Quota check returned non-OK status:', response.status);
                }
            } catch (err) {
                console.error('Quota check error:', err);
                // Lanjutkan submit normal jika terjadi error
            }
        }
        // Jika tidak over atau bukan 'pulang', submit berjalan normal
    });

    forceBtn.addEventListener('click', function () {
        bootstrap.Modal.getInstance(modal).hide();
        form.setAttribute('data-quota-confirmed', 'true');
        form.submit();
    });
});

</script>
@endsection
