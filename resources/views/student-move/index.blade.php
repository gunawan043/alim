@extends('layouts.master')
@section('title') Pindahkan Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') Pindahkan Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-line me-1"></i> <strong>Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- MODE: belum pilih rombel --}}
    @if(!$sourceStudyGroup)
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="avatar-lg mx-auto mb-3">
                    <div class="avatar-title bg-warning-subtle rounded-circle">
                        <i class="ri-arrow-left-right-line fs-1 text-warning"></i>
                    </div>
                </div>
                <h5 class="text-muted">Pindahkan Santri</h5>
                <p class="text-muted">Fitur ini digunakan untuk memindahkan Santri antar rombel dengan <strong>tingkat yang sama</strong>.<br>
                Pilih rombel asal terlebih dahulu dari halaman <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">Rombel</a>.</p>
                <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="btn btn-primary mt-2">
                    <i class="ri-group-line me-1"></i>Manajemen Rombel
                </a>
            </div>
        </div>

    {{-- MODE: rombel tidak memiliki tujuan yang tersedia --}}
    @elseif($availableDestinations->isEmpty())
        <div class="alert alert-warning mb-3">
            <i class="ri-alert-line me-1"></i>
            <strong>{{ $sourceStudyGroup->full_name }}</strong> —
            Tidak ada rombel tujuan tersedia dengan tingkat yang sama di tahun ajaran ini.
            Pastikan sudah ada rombel lain di tingkat {{ $sourceStudyGroup->gradeLevel?->name ?? '-' }} pada sekolah
            {{ $sourceStudyGroup->school?->name ?? '' }}.
        </div>
        <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="btn btn-outline-primary">
            <i class="ri-arrow-left-line me-1"></i>Kembali
        </a>
        <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $sourceStudyGroup->id]) }}" class="btn btn-outline-secondary">
            <i class="ri-eye-line me-1"></i>Lihat Rombel
        </a>

    @else
        {{-- Info Banner --}}
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
            <i class="ri-information-fill fs-4"></i>
            <div>
                Memindahkan Santri dari <strong>{{ $sourceStudyGroup->full_name }}</strong>
                ({{ $sourceStudyGroup->school?->name ?? '-' }})
                ke rombel lain dengan tingkat <strong>SAMA</strong>.
                <br><span class="small text-muted">Tahun ajaran: {{ $sourceStudyGroup->academicYear?->name ?? '-' }}.
                Ini BUKAN kenaikan kelas — gunakan menu Kenaikan Kelas untuk perpindahan tingkat.</span>
            </div>
        </div>

        @if($students->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="ri-user-search-line fs-1 text-muted"></i>
                    <h5 class="mt-2">Tidak ada santri aktif di rombel ini</h5>
                    <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $sourceStudyGroup->id]) }}" class="btn btn-light mt-2">
                        Kembali ke Rombel
                    </a>
                </div>
            </div>
        @else
            <form method="POST" id="moveForm"
                  action="{{ route('user.student-move.store', ['userId' => $userId]) }}">
                @csrf
                <input type="hidden" name="source_study_group_id" value="{{ $sourceStudyGroup->id }}">

                {{-- Konfigurasi Perpindahan --}}
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="ri-settings-3-line me-1"></i>Konfigurasi Perpindahan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Rombel Tujuan <span class="text-danger">*</span></label>
                                <select name="destination_study_group_id" id="destinationStudyGroup"
                                        class="form-select" required
                                        onchange="updateCapacityWarning()">
                                    <option value="">-- Pilih Rombel Tujuan --</option>
                                    @foreach($availableDestinations as $sg)
                                        <option value="{{ $sg->id }}"
                                                data-capacity="{{ $sg->capacity }}"
                                                data-current="{{ $sg->studentCount }}"
                                                {{ old('destination_study_group_id') == $sg->id ? 'selected' : '' }}>
                                            {{ $sg->full_name }}
                                            ({{ $sg->studentCount }}/{{ $sg->capacity }})
                                            @if($sg->studentCount >= $sg->capacity)
                                                — PENUH
                                            @else
                                                — {{ $sg->capacity - $sg->studentCount }} slot
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('destination_study_group_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Efektif <span class="text-danger">*</span></label>
                                <input type="date" name="move_date" class="form-control"
                                       value="{{ old('move_date', date('Y-m-d')) }}" required>
                                @error('move_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Keterangan / Alasan</label>
                                <input type="text" name="notes" class="form-control"
                                       value="{{ old('notes') }}"
                                       placeholder="Contoh: Penyesuaian kapasitas rombel"
                                       maxlength="500">
                            </div>
                        </div>

                        {{-- Capacity warning --}}
                        <div id="capacityAlert" class="alert alert-danger mt-3 mb-0" style="display:none">
                            <i class="ri-error-warning-fill me-1"></i>
                            <span id="capacityAlertText"></span>
                        </div>
                    </div>
                </div>

                {{-- Daftar Santri --}}
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">
                                Santri di {{ $sourceStudyGroup->full_name }}
                                <span class="badge bg-primary ms-1">{{ $students->count() }} orang</span>
                            </h5>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check-all"
                                       onchange="toggleCheckAll(this)">
                                <label class="form-check-label fw-semibold" for="check-all">Pilih Semua</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light text-muted sticky-top" style="top:0;z-index:1">
                                    <tr>
                                        <th style="width:40px"></th>
                                        <th>Nama</th>
                                        <th>NISN</th>
                                        <th>JK</th>
                                        <th>Tempat, Tgl Lahir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $s)
                                        <tr>
                                            <td class="text-center">
                                                <input class="form-check-input student-check"
                                                       type="checkbox" name="student_ids[]"
                                                       value="{{ $s->id }}"
                                                       onchange="updateSelectedCount()">
                                            </td>
                                            <td class="fw-semibold">{{ $s->name }}</td>
                                            <td><code>{{ $s->nisn ?: '-' }}</code></td>
                                            <td>
                                                @if($s->gender === 'L')
                                                    <span class="badge bg-primary-subtle text-primary">L</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">P</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $s->birth_place ?: '-' }},
                                                    {{ $s->birth_date?->format('d M Y') ?? '-' }}
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span id="selectedCountText" class="text-muted small">
                            {{ $students->count() }} santri dipilih
                        </span>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="card">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="text-muted small">
                            <i class="ri-information-line me-1"></i>
                            Santri akan dipindahkan ke rombel tujuan pada tanggal efektif.
                            Status histori rombel lama akan dinonaktifkan.
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $sourceStudyGroup->id]) }}"
                               class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i>Batal
                            </a>
                            <button type="button" id="btnProses" class="btn btn-primary"
                                    onclick="showConfirmModal()"
                                    {{ $students->isEmpty() ? 'disabled' : '' }}>
                                <i class="ri-arrow-left-right-line me-1"></i>
                                Pindahkan <span id="submitCount"></span> Santri
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Modal Konfirmasi --}}
            <div class="modal fade" id="confirmMoveModal" tabindex="-1" aria-labelledby="confirmMoveModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmMoveModalLabel">
                                <i class="ri-checkbox-circle-line me-1 text-primary"></i>
                                Konfirmasi Pindahkan Santri
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="confirmStudentInfo"></div>
                            <hr>
                            <div class="d-flex align-items-start gap-2">
                                <i class="ri-alert-line text-warning mt-1"></i>
                                <p class="mb-0 small text-muted">
                                    Pastikan rombel tujuan sudah benar. Tindakan ini akan
                                    <strong>mencatat histori rombel lama</strong> sebagai nonaktif
                                    dan membuat <strong>histori baru</strong> di rombel tujuan.
                                    Tidak ada perubahan tingkat atau tahun ajaran.
                                </p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" form="moveForm" class="btn btn-primary"
                                    id="confirmSubmitBtn">
                                <i class="ri-checkbox-circle-line me-1"></i>Ya, Pindahkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection

@section('script')
<script>
(function() {
    // ── Capacity Warning ──────────────────────────────────────────
    window.updateCapacityWarning = function() {
        const sel = document.getElementById('destinationStudyGroup');
        const alertBox = document.getElementById('capacityAlert');
        const alertText = document.getElementById('capacityAlertText');
        if (!sel || !alertBox) return;

        const selected = sel.options[sel.selectedIndex];
        if (!selected || !selected.value) {
            alertBox.style.display = 'none';
            return;
        }

        const capacity = parseInt(selected.dataset.capacity) || 0;
        const current = parseInt(selected.dataset.current) || 0;
        const checkedCount = document.querySelectorAll('.student-check:checked').length;
        const slots = capacity - current;
        const movingTotal = current + checkedCount;

        if (checkedCount > slots) {
            alertBox.className = 'alert alert-danger mt-3 mb-0';
            alertText.textContent = '⚠️ Rombel tujuan hanya memiliki ' + slots + ' slot tersisa. ' +
                'Anda memilih ' + checkedCount + ' Santri. Kurangi jumlah atau pilih rombel lain.';
            alertBox.style.display = '';
        } else if (slots === 0) {
            alertBox.className = 'alert alert-danger mt-3 mb-0';
            alertText.textContent = '⚠️ Rombel tujuan sudah PENUH. Tidak bisa memindahkan Santri ke rombel ini.';
            alertBox.style.display = '';
        } else {
            alertBox.style.display = 'none';
        }
    };

    // ── Check All ─────────────────────────────────────────────────
    window.toggleCheckAll = function(source) {
        document.querySelectorAll('.student-check').forEach(function(cb) {
            cb.checked = source.checked;
        });
        updateSelectedCount();
    };

    // ── Selected Count ────────────────────────────────────────────
    window.updateSelectedCount = function() {
        const checked = document.querySelectorAll('.student-check:checked');
        const count = checked.length;
        const total = document.querySelectorAll('.student-check').length;

        const textEl = document.getElementById('selectedCountText');
        const submitCountEl = document.getElementById('submitCount');
        const btn = document.getElementById('btnProses');
        const modalInfoEl = document.getElementById('confirmStudentInfo');

        if (textEl) {
            textEl.textContent = count + ' dari ' + total + ' Santri dipilih';
        }
        if (submitCountEl) {
            submitCountEl.textContent = count > 0 ? '(' + count + ')' : '';
        }
        if (btn) {
            btn.disabled = count === 0;
        }

        // Update modal info
        if (modalInfoEl) {
            const destName = document.getElementById('destinationStudyGroup');
            const destText = destName && destName.selectedIndex > 0
                ? destName.options[destName.selectedIndex].text : '[belum dipilih]';
            modalInfoEl.innerHTML =
                '<p class="mb-1"><strong>' + count + '</strong> Santri akan dipindahkan.</p>' +
                '<p class="mb-0 text-muted small">Rombel tujuan: <strong>' + destText + '</strong></p>';
        }

        updateCapacityWarning();
    };

    // ── Show Confirm Modal ────────────────────────────────────────
    window.showConfirmModal = function() {
        const checked = document.querySelectorAll('.student-check:checked');
        if (checked.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Pilih Santri', text: 'Pilih minimal 1 Santri untuk dipindahkan.' });
            }
            return;
        }
        const destSelect = document.getElementById('destinationStudyGroup');
        if (!destSelect || !destSelect.value) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Pilih Rombel Tujuan', text: 'Pilih rombel tujuan terlebih dahulu.' });
            }
            return;
        }

        updateSelectedCount();
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmMoveModal'));
        modal.show();
    };

    // Init on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateSelectedCount();

        // Wire up change events on checkboxes
        document.querySelectorAll('.student-check').forEach(function(cb) {
            cb.addEventListener('change', updateSelectedCount);
        });

        // Destination select change
        document.getElementById('destinationStudyGroup')?.addEventListener('change', updateCapacityWarning);
    });
})();
</script>
@endsection
