@extends('layouts.master')
@section('title') Tambah Mahrom — Data Mahrom @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('css')
    <link href="{{ URL::asset('build/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Santri</a> @endslot
        @slot('li_3') <a href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}">Data Mahrom</a> @endslot
        @slot('title') Tambah Mahrom @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="ri-error-warning-line me-2 fs-18"></i>
                <strong>Terjadi kesalahan:</strong>
            </div>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('user.students.mahroms.globalStore', ['userId' => $userId]) }}"
          enctype="multipart/form-data"
          id="mahromForm">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                {{-- Pilih Santri --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-user-card-line me-2 text-primary"></i>Pilih Santri</h5>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Santri terkait <span class="text-danger">*</span></label>
                        <select name="student_id" id="studentSelect" class="form-select" required>
                            <option value="">— Pilih Santri —</option>
                            @foreach($students as $s)
                                @php
                                    $classLabel = optional(optional($s->currentClassHistory)->studyGroup)->gradeLevel?->name
                                        ? optional($s->currentClassHistory->studyGroup->gradeLevel)->name . ' ' . optional($s->currentClassHistory->studyGroup)->name
                                        : null;
                                    $roomLabel = optional(optional($s->activeDormitoryResident)->room)->name
                                        ?? optional(optional($s->activeDormitoryResident)->room)->code;
                                    $searchHaystack = strtolower(implode(' ', array_filter([
                                        $s->name,
                                        $s->nisn,
                                        $s->nik,
                                        $classLabel,
                                        $roomLabel,
                                    ])));
                                @endphp
                                <option value="{{ $s->id }}"
                                        data-search="{{ $searchHaystack }}"
                                        {{ (string) old('student_id', $preselectedStudentId ?? '') === (string) $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}@if($s->nisn) — NISN: {{ $s->nisn }}@endif
                                    @if($classLabel)  · Kelas: {{ $classLabel }}@endif
                                    @if($roomLabel)  · Kamar: {{ $roomLabel }}@endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">
                            Ketik untuk mencari nama, NISN, NIK, kelas, atau nama kamar Santri. Batas maksimal 4 mahrom per Santri.
                        </div>
                        @error('student_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Data Mahrom --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-user-settings-line me-2 text-primary"></i>Data Mahrom</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                       value="{{ old('name') }}" placeholder="Nama lengkap mahrom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIK (Nomor Induk Kependudukan)</label>
                                <input type="text" name="id_number" class="form-control"
                                       value="{{ old('id_number') }}" placeholder="16 digit NIK" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hubungan dengan Santri <span class="text-danger">*</span></label>
                                <select name="relationship" class="form-control" required>
                                    <option value="">— Pilih Hubungan —</option>
                                    <option value="ayah" {{ old('relationship') === 'ayah' ? 'selected' : '' }}>Ayah</option>
                                    <option value="ibu" {{ old('relationship') === 'ibu' ? 'selected' : '' }}>Ibu</option>
                                    <option value="kakak" {{ old('relationship') === 'kakak' ? 'selected' : '' }}>Kakak</option>
                                    <option value="adik" {{ old('relationship') === 'adik' ? 'selected' : '' }}>Adik</option>
                                    <option value="paman" {{ old('relationship') === 'paman' ? 'selected' : '' }}>Paman</option>
                                    <option value="bibi" {{ old('relationship') === 'bibi' ? 'selected' : '' }}>Bibi</option>
                                    <option value="kakek" {{ old('relationship') === 'kakek' ? 'selected' : '' }}>Kakek</option>
                                    <option value="nenek" {{ old('relationship') === 'nenek' ? 'selected' : '' }}>Nenek</option>
                                    <option value="wali" {{ old('relationship') === 'wali' ? 'selected' : '' }}>Wali</option>
                                    <option value="anak" {{ old('relationship') === 'anak' ? 'selected' : '' }}>Anak</option>
                                    <option value="sepupu" {{ old('relationship') === 'sepupu' ? 'selected' : '' }}>Sepupu</option>
                                    <option value="lainnya" {{ old('relationship') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control"
                                       value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2"
                                          placeholder="Alamat lengkap mahrom">{{ old('address') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Catatan tambahan jika ada">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status Options --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-settings-line me-2 text-primary"></i>Pengaturan Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_primary"
                                           id="isPrimarySwitch" value="1" {{ old('is_primary') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isPrimarySwitch">
                                        <strong>Mahrom Utama</strong>
                                        <div class="text-muted small">Mahrom utama adalah kontak utama untuk menerima informasi seputar Santri.</div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active"
                                           id="isActiveSwitch" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActiveSwitch">
                                        <strong>Mahrom Aktif</strong>
                                        <div class="text-muted small">Mahrom nonaktif tidak dapat menjenguk Santri.</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Photo Upload --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ri-image-add-line me-2 text-primary"></i>Foto Mahrom</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-4">
                            <div class="flex-shrink-0">
                                <div id="photoPreview" class="rounded border p-1 text-center" style="width:120px;height:120px;">
                                    <i class="ri-image-add-line text-muted" style="font-size:2.5rem;line-height:110px;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-2">Unggah foto mahrom (opsional). Format: JPG, PNG. Maksimal 2MB.</p>
                                <input type="file" name="photo" class="form-control" id="photoInput" accept="image/*">
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="clearPhoto()">
                                    <i class="ri-delete-bin-line me-1"></i> Hapus Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 justify-content-end mt-3">
                    <a href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}"
                       class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="ri-save-line me-1"></i> Simpan Mahrom
                    </button>
                </div>
            </div>

            {{-- Sidebar Info --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ri-shield-star-line me-2"></i>Tentang Mahrom</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="d-flex gap-2 mb-3">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span><strong>Mahrom</strong> adalah orang yang memiliki hubungan darah atau صلاح (walimat) dengan Santri dan diperbolehkan menjenguk di dalam.</span>
                            </li>
                            <li class="d-flex gap-2 mb-3">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Batas maksimal <strong>4 mahrom</strong> per Santri.</span>
                            </li>
                            <li class="d-flex gap-2 mb-3">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>Mahrom utama menerima semua informasi terkait Santri.</span>
                            </li>
                            <li class="d-flex gap-2 mb-0">
                                <i class="ri-checkbox-circle-line text-success mt-1"></i>
                                <span>NIK bersifat unik untuk satu mahrom (tidak boleh duplikat).</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Daftar Mahrom Santri Terpilih --}}
                <div class="card mt-3" id="mahromListCard" style="display:none;">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">
                            <i class="ri-team-line me-2 text-primary"></i>Mahrom Santri Terpilih
                        </h5>
                        <span class="badge bg-primary-subtle text-primary" id="mahromCountBadge">0/4</span>
                    </div>
                    <div class="card-body" id="mahromListBody">
                        {{-- Di-render via JS --}}
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
    <script src="{{ URL::asset('build/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var studentSelect = document.getElementById('studentSelect');
        if (studentSelect && !studentSelect.dataset.choicesInit) {
            new Choices(studentSelect, {
                searchEnabled: true,
                searchChoices: true,
                searchFloor: 1,
                searchPlaceholderValue: 'Ketik untuk mencari Santri (nama, NISN, NIK)…',
                placeholder: true,
                placeholderValue: '— Pilih Santri —',
                noResultsText: 'Tidak ada Santri yang cocok',
                itemSelectText: 'Tekan Enter untuk memilih',
                shouldSort: false,
                position: 'auto',
            });
            // Hook ke listener change pada <select> asli (Choices.js tetap menulis ke sini)
            studentSelect.addEventListener('change', function (e) {
                fetchMahromList(e.target.value);
            });
            studentSelect.dataset.choicesInit = '1';
        }

        // ── Preview daftar mahrom milik Santri yang dipilih ──────────
        var listCard = document.getElementById('mahromListCard');
        var listBody = document.getElementById('mahromListBody');
        var countBadge = document.getElementById('mahromCountBadge');
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrf ? csrf.getAttribute('content') : '';
        var userId = @json($userId);
        var controller = new AbortController();

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
            });
        }

        function renderMahromList(payload) {
            var max = payload.max || 4;
            var count = payload.count || 0;
            countBadge.textContent = count + '/' + max;

            if (count >= max) {
                countBadge.classList.remove('bg-primary-subtle', 'text-primary');
                countBadge.classList.add('bg-danger', 'text-white');
            } else {
                countBadge.classList.add('bg-primary-subtle', 'text-primary');
                countBadge.classList.remove('bg-danger', 'text-white');
            }

            var html = '';
            html += '<div class="small text-muted mb-2">';
            html += '<strong>' + escapeHtml(payload.student.name) + '</strong>';
            if (payload.student.nisn) html += ' · NISN: ' + escapeHtml(payload.student.nisn);
            html += '</div>';

            if (count === 0) {
                html += '<div class="alert alert-info mb-0 py-2 small">';
                html += '<i class="ri-information-line me-1"></i> Santri ini belum memiliki mahrom. Slot tersedia: ' + max + '.';
                html += '</div>';
            } else {
                html += '<ul class="list-group list-group-flush">';
                payload.mahroms.forEach(function (m) {
                    html += '<li class="list-group-item px-0 py-2 d-flex align-items-start gap-2">';
                    html += '<i class="ri-user-3-line text-primary mt-1"></i>';
                    html += '<div class="flex-grow-1">';
                    html += '<div class="d-flex align-items-center gap-2 flex-wrap">';
                    html += '<strong>' + escapeHtml(m.name) + '</strong>';
                    html += '<span class="badge bg-light text-dark border">' + escapeHtml(m.relationship_text) + '</span>';
                    if (m.is_primary) {
                        html += '<span class="badge bg-success">Utama</span>';
                    }
                    if (!m.is_active) {
                        html += '<span class="badge bg-secondary">Nonaktif</span>';
                    }
                    html += '</div>';
                    if (m.phone) {
                        html += '<div class="small text-muted"><i class="ri-phone-line me-1"></i>' + escapeHtml(m.phone) + '</div>';
                    }
                    html += '</div>';
                    html += '</li>';
                });
                html += '</ul>';
            }

            if (count >= max) {
                html += '<div class="alert alert-warning mt-2 mb-0 py-2 small">';
                html += '<i class="ri-alert-line me-1"></i> Santri ini sudah memiliki <strong>' + max + ' mahrom</strong> (maks). Tidak bisa menambah lagi.';
                html += '</div>';
            }

            listBody.innerHTML = html;
            listCard.style.display = 'block';
        }

        function fetchMahromList(studentId, selectedName) {
            if (!studentId) {
                listCard.style.display = 'none';
                listBody.innerHTML = '';
                return;
            }
            controller.abort();
            controller = new AbortController();
            // Loading placeholder
            listBody.innerHTML = '<div class="text-muted small py-2"><i class="ri-loader-2-line me-1"></i>Memuat data mahrom…</div>';
            listCard.style.display = 'block';

            var url = @json(route('user.students.mahroms.list', ['userId' => '__UID__', 'santriUuid' => '__SID__']))
                    .replace('__UID__', encodeURIComponent(userId))
                    .replace('__SID__', encodeURIComponent(studentId));
            fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
                credentials: 'same-origin',
            })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                if (!data.ok) {
                    listBody.innerHTML = '<div class="alert alert-danger mb-0 py-2 small">' + escapeHtml(data.message || 'Gagal memuat data.') + '</div>';
                    return;
                }
                renderMahromList(data);
            })
            .catch(function (err) {
                if (err.name === 'AbortError') return;
                listBody.innerHTML = '<div class="alert alert-danger mb-0 py-2 small">Gagal memuat daftar mahrom: ' + escapeHtml(err.message) + '</div>';
            });
        }

        if (studentSelect && studentSelect.value) {
            // Auto-load kalau ada preselected (mis. datang dari URL ?student_id=)
            fetchMahromList(studentSelect.value);
        }
    });

    function clearPhoto() {
        document.getElementById('photoInput').value = '';
        document.getElementById('photoPreview').innerHTML =
            '<i class="ri-image-add-line text-muted" style="font-size:2.5rem;line-height:110px;"></i>';
    }

    document.getElementById('photoInput').addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: 'Ukuran File Terlalu Besar', text: 'Maksimal 2MB.' });
            this.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('photoPreview').innerHTML =
                '<img src="' + e.target.result + '" alt="Preview" class="img-fluid rounded" style="width:110px;height:110px;object-fit:cover;">';
        };
        reader.readAsDataURL(file);
    });

    document.getElementById('mahromForm').addEventListener('submit', function () {
        var submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line me-1 spinner-border spinner-border-sm"></i> Menyimpan...';
        }
    });
    </script>
@endsection