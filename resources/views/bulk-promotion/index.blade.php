@extends('layouts.master')
@section('title') Kenaikan Kelas Massal @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') Kenaikan Kelas Massal @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($studyGroup)
        {{-- MODE: dari rombel tertentu --}}
        <div class="alert alert-info">
            <i class="ri-information-line me-1"></i>
            <strong>{{ $studyGroup->full_name }}</strong> — {{ $studyGroup->school?->name ?? '' }}
        </div>

        @if($students->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="ri-user-search-line fs-1 text-muted"></i>
                    <h5 class="mt-2">Tidak ada santri di rombel ini</h5>
                    <a href="{{ route('user.bulk-promotion.index', ['userId' => $userId]) }}" class="btn btn-light mt-2">Kembali</a>
                </div>
            </div>
        @else
            <form method="POST" id="promotionForm" action="{{ route('user.bulk-promotion.store', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">
                @csrf

                {{-- Konfigurasi Kenaikan --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-settings-3-line text-primary me-1"></i>Konfigurasi Kenaikan / Mutasi Kelas</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tahun Ajaran Baru <span class="text-danger">*</span></label>
                                <select name="to_academic_year_id" id="toAcademicYear" class="form-select" required>
                                    <option value="">-- Pilih Tahun Ajaran Baru --</option>
                                    @foreach($targetAcademicYears ?? [] as $ay)
                                        <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text small">
                                    Hanya tahun ajaran <strong>setelah {{ $fromAyName ?? 'TA saat ini' }}</strong>.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rombel Tujuan <span class="text-danger">*</span></label>
                                <select name="to_study_group_id" id="toStudyGroupSelect" class="form-select" required>
                                    <option value="">-- Pilih Tahun Ajaran dulu --</option>
                                </select>
                                <div class="form-text small" id="targetHint">
                                    Hanya rombel dengan tingkat <strong>di atas {{ $studyGroup->name ?? 'kelas saat ini' }}</strong>.
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tanggal Efektif <span class="text-danger">*</span></label>
                                <input type="date" name="promotion_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="notes" class="form-control" placeholder="Opsional">
                            </div>
                        </div>

                        @error('to_academic_year_id')
                            <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>
                        @enderror
                        @error('to_study_group_id')
                            <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>
                        @enderror

                        @if(($targetAcademicYears ?? collect())->isEmpty())
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="ri-error-warning-line me-1"></i>
                                Belum ada <strong>tahun ajaran baru</strong>. Buat tahun ajaran berikutnya beserta rombelnya terlebih dahulu.
                            </div>
                        @endif

                        <div class="alert alert-info mt-3 mb-0">
                            <i class="ri-information-line me-1"></i>
                            Pindah kelas pada tahun ajaran yang <strong>sama</strong> bukan kenaikan kelas — gunakan fitur
                            <a href="{{ route('user.student-move.index', ['userId' => $userId]) }}" class="fw-semibold">Mutasi Kelas</a>.
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Daftar Santri ({{ $students->count() }} orang)</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check-all">
                                <label class="form-check-label fw-semibold" for="check-all">Pilih Semua</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light text-muted sticky-top" style="top:0">
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
                                                <input class="form-check-input student-check" type="checkbox" name="student_ids[]" value="{{ $s->id }}" checked>
                                            </td>
                                            <td class="fw-semibold">{{ $s->name }}</td>
                                            <td><code>{{ $s->nisn }}</code></td>
                                            <td>
                                                @if($s->gender === 'L')
                                                    <span class="badge bg-primary-subtle text-primary">L</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">P</span>
                                                @endif
                                            </td>
                                            <td><small>{{ $s->birth_place ?: '-' }}, {{ $s->birth_date?->format('d/m/Y') ?? '-' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Aksi Massal</h5></div>
                    <div class="card-body">
                        @if($isFinalGrade)
                            <div class="alert alert-warning mb-3">
                                <i class="ri-alert-line me-1"></i>
                                <strong>{{ $studyGroup->gradeLevel?->name }}</strong> adalah tingkat akhir jenjang.
                                Santri yang dipilih akan ditandai <strong>LULUS</strong>.
                            </div>
                        @else
                            <div class="alert alert-info mb-3">
                                <i class="ri-information-line me-1"></i>
                                Santri akan <strong>dipindahkan</strong> ke kelas yang dipilih di modal konfirmasi.
                            </div>
                        @endif
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id]) }}" class="btn btn-light">Batal</a>
                        @if($isFinalGrade)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                <i class="ri-graduation-cap-line me-1"></i>Tandai Lulus ({{ $students->count() }})
                            </button>
                        @else
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                <i class="ri-arrow-up-line me-1"></i><span id="processBtnLabel">Proses Kenaikan</span> ({{ $students->count() }})
                            </button>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Modal Konfirmasi + Pilih Kelas Tujuan --}}
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel">
                                <i class="ri-checkbox-circle-line me-1 text-warning"></i>Konfirmasi Kenaikan Kelas
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @if($isFinalGrade)
                                <strong>{{ $students->count() }}</strong> santri akan ditandai <strong>LULUS</strong>.
                                Tindakan ini tidak bisa dibatalkan.
                            @else
                                <p class="mb-2">
                                    <strong>{{ $students->count() }}</strong> santri dari <strong>{{ $studyGroup->full_name }}</strong>
                                    akan diproses ke:
                                </p>
                                <p class="mb-2"><span class="badge bg-primary-subtle text-primary" id="modalTargetLabel">— pilih rombel tujuan —</span></p>
                                <div class="alert alert-warning mb-2 d-none" id="modalLowerWarning">
                                    <i class="ri-error-warning-line me-1"></i>
                                    Rombel tujuan lebih rendah — dicatat sebagai <strong>mutasi kelas</strong> (koreksi penempatan).
                                </div>
                                <p class="mb-0 small text-muted" id="modalModeLabel">Mode: kenaikan kelas.</p>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" form="promotionForm" class="btn btn-warning" id="promotionSubmitBtn">
                                <i class="ri-checkbox-circle-line me-1"></i><span id="promotionSubmitLabel">Ya, Lanjutkan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- MODE: pilih rombel dulu --}}
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="ri-arrow-up-line fs-1 text-muted"></i>
                <h5 class="mt-2">Kenaikan Kelas Massal</h5>
                <p>Pilih rombel terlebih dahulu dari menu <strong>Rombel</strong> untuk melanjutkan.</p>
                <a href="{{ route('user.study-groups.index', ['userId' => $userId]) }}" class="btn btn-primary mt-2">
                    <i class="ri-group-line me-1"></i>Manajemen Rombel
                </a>
            </div>
        </div>
    @endif
@endsection

@section('script')
<script>
const allStudyGroups = {!! json_encode($allStudyGroups ?? []) !!};
const FROM_ROMBEL_NAME = @json($fromRombelName ?? '');

document.getElementById('check-all')?.addEventListener('change', function() {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = this.checked);
});

const aySelect = document.getElementById('toAcademicYear');
const sgSelect = document.getElementById('toStudyGroupSelect');

// Suffix paralel dari nama rombel: '2A' → 'A' (untuk memilih 3A secara otomatis).
function parallelSuffix(name) {
    return (name || '').replace(/^\d+[\s\-_.]*/u, '');
}

function populateStudyGroups() {
    const ayId = aySelect?.value;
    if (!sgSelect) return;

    if (!ayId) {
        sgSelect.innerHTML = '<option value="">-- Pilih Tahun Ajaran dulu --</option>';
        return;
    }

    const filtered = allStudyGroups.filter(sg => String(sg.academic_year_id) === String(ayId));

    if (!filtered.length) {
        sgSelect.innerHTML = '<option value="">(tidak ada rombel tingkat di atas pada TA ini)</option>';
        return;
    }

    const suffix = parallelSuffix(FROM_ROMBEL_NAME);

    sgSelect.innerHTML = '<option value="">-- Pilih Rombel Tujuan --</option>' + filtered.map(sg => {
        const isParallel = suffix && sg.name && sg.name.toUpperCase().endsWith(suffix.toUpperCase());
        return `<option value="${sg.id}"${isParallel ? ' data-parallel="1"' : ''}>${sg.name}${sg.academic_year ? ' — ' + sg.academic_year : ''}</option>`;
    }).join('');

    // Otomatis pilih kelas paralel (2A → 3A) bila tersedia.
    const parallel = sgSelect.querySelector('option[data-parallel="1"]');
    if (parallel) sgSelect.value = parallel.value;

    syncModal();
}

function syncModal() {
    const opt = sgSelect?.options[sgSelect?.selectedIndex] ?? null;
    const label = document.getElementById('modalTargetLabel');
    if (label) label.textContent = opt && opt.value ? opt.textContent.trim() : '— pilih rombel tujuan —';

    const mode = document.getElementById('modalModeLabel');
    if (mode) mode.textContent = 'Mode: kenaikan kelas ke tahun ajaran baru.';
}

aySelect?.addEventListener('change', populateStudyGroups);
sgSelect?.addEventListener('change', syncModal);
document.getElementById('confirmModal')?.addEventListener('shown.bs.modal', syncModal);
document.addEventListener('DOMContentLoaded', populateStudyGroups);
</script>
@endsection
