@extends('layouts.master')
@section('title') Cetak Rapor — {{ $studyGroup->name ?? '' }} @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? Auth::id();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}">Nilai Kelas</a> @endslot
        @slot('title') Cetak Rapor @endslot
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

    @php
        $stats = $stats ?? ['total_santri' => 0, 'catatan_terisi' => 0, 'nilai_final' => 0];
    @endphp

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-group-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Santri</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total_santri']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Santri aktif di {{ $studyGroup->name }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-chat-1-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Catatan Wali Terisi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['catatan_terisi']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Dari {{ number_format($stats['total_santri']) }} santri</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-save-3-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Nilai Final Tersimpan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['nilai_final']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Tersimpan saat cetak rapor SAS</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-calendar-check-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Periode</p>
                            <h3 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ ucfirst($selectedSem) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-calendar-2-line me-1"></i>{{ $selectedAy?->name ?? 'Belum ada TA' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Publikasi Rapor ke Wali Santri --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0"><i class="ri-send-plane-line text-success me-1"></i>Publikasi Rapor</h5>
                <p class="text-muted mb-0 small">Hanya rapor yang dipublikasikan yang dapat dilihat oleh wali santri.</p>
            </div>
            @php
                $pipeline = [
                    'draft' => $registrations->where('status', 'draft')->where('is_published', false)->count(),
                    'in_progress' => $registrations->where('status', 'in_progress')->where('is_published', false)->count(),
                    'finalized' => $registrations->where('status', 'finalized')->where('is_published', false)->count(),
                    'published' => $stats['published'] ?? 0,
                ];
            @endphp
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-secondary-subtle text-secondary">Draft {{ $pipeline['draft'] }}</span>
                <span class="badge bg-warning-subtle text-warning">Proses {{ $pipeline['in_progress'] }}</span>
                <span class="badge bg-info-subtle text-info">Final {{ $pipeline['finalized'] }}</span>
                <span class="badge bg-success-subtle text-success">Published {{ $pipeline['published'] }}</span>
            </div>
        </div>
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="small text-muted">
                <i class="ri-information-line me-1"></i>
                Pipeline: <strong>Draft → In Progress → Finalized → Published</strong>.
                Publikasi mencakup seluruh santri aktif di {{ $studyGroup->name }} untuk
                {{ $selectedAy?->name ?? 'TA aktif' }} — {{ ucfirst($selectedSem) }}.
            </div>
            @if($canPublish ?? false)
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.finalize', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Finalisasi rapor rombel ini? Status berubah menjadi Finalized.')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <button type="submit" class="btn btn-outline-info btn-sm">
                            <i class="ri-check-double-line me-1"></i>Finalisasi Rapor
                        </button>
                    </form>
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.unpublish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Batalkan publikasi rapor? Wali tidak akan dapat melihat rapor ini.')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="ri-eye-off-line me-1"></i>Batalkan Publikasi
                        </button>
                    </form>
                    <form method="POST" action="{{ route('user.schools.nilai-kelas.rapor.publish', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                          onsubmit="return confirm('Publikasikan rapor ini ke wali santri?')">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSem }}">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ri-send-plane-line me-1"></i>Publikasikan Rapor
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Pengaturan Tanggal Pembagian Rapor --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <h5 class="card-title mb-0"><i class="ri-calendar-event-line text-success me-1"></i>Tanggal Pembagian Rapor</h5>
            <p class="text-muted mb-0 small">Dipakai pada dokumen rapor (cetak &amp; arsip) untuk TA/semester ini — menggantikan tanggal cetak otomatis.</p>
        </div>
        <div class="card-body">
            <form method="POST"
                  action="{{ route('user.schools.nilai-kelas.rapor.pengaturan', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                  class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $selectedAyId }}">
                <input type="hidden" name="semester" value="{{ $selectedSem }}">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tanggal Pembagian</label>
                    <input type="date" name="distribution_date" class="form-control"
                           value="{{ $distributionSetting?->distribution_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Kota</label>
                    <input type="text" name="city" class="form-control" maxlength="100"
                           value="{{ $distributionSetting?->city ?? $studyGroup->school?->city }}"
                           placeholder="Mataram">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Catatan</label>
                    <input type="text" name="notes" class="form-control" maxlength="500"
                           value="{{ $distributionSetting?->notes }}" placeholder="Opsional">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-success">
                        <i class="ri-save-line me-1"></i>Simpan
                    </button>
                </div>
                @if($distributionSetting?->distribution_date)
                    <div class="col-12">
                        <span class="badge bg-info-subtle text-info">
                            Aktif: {{ $distributionSetting->distribution_date->format('d M Y') }}
                            @if($distributionSetting->city) — {{ $distributionSetting->city }} @endif
                        </span>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">
                                <i class="ri-file-paper-2-line text-primary me-1"></i>
                                Cetak Rapor Santri
                            </h5>
                            <p class="text-muted mb-0" style="font-size:12px;">
                                <span class="badge bg-primary-subtle text-primary">{{ $stats['total_santri'] }} santri</span>
                                {{ $studyGroup->school?->name ?? '' }} — {{ $studyGroup->name }} — TA {{ $selectedAy?->name ?? '' }} Semester {{ ucfirst($selectedSem) }}
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            @if(($stats['total_santri'] ?? 0) > 0)
                                <div class="btn-group me-1">
                                    <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-printer-line me-1"></i> Cetak Rapor Massal (1 PDF)
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><h6 class="dropdown-header">SAS — Nilai Akhir</h6></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header">STS — Tengah Semester</h6></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak-massal', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'academic_year_id' => $selectedAy?->id, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                    </ul>
                                </div>
                            @else
                                <span class="badge bg-warning-subtle text-warning me-1">
                                    <i class="ri-information-line me-1"></i>Belum ada santri pada TA/semester ini
                                </span>
                            @endif
                            <a href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" style="font-size:12px;">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Semester</label>
                            <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="ganjil" {{ $selectedSem == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="genap" {{ $selectedSem == 'genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Cari Nama</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Nama Santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                    </form>

                    {{-- Student List --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>NIS</th>
                                    <th>Nama Santri</th>
                                    <th style="width:120px;text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNum = 0; @endphp
                                @forelse($students as $history)
                                    @php
                                        $student = $history->student;
                                        if (request('search') && stripos($student->name, request('search')) === false)
                                            continue;
                                    @endphp
                                    @php $rowNum++; @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $rowNum }}</td>
                                        <td class="text-center">{{ $student->nis ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary text-white" style="font-size:10px;">
                                                        {{ strtoupper(mb_substr(($__w = preg_split('/\s+/', trim((string) $student->name)))[0] ?? '', 0, 1) . (count($__w) > 1 ? mb_substr($__w[count($__w) - 1], 0, 1) : mb_substr($__w[0] ?? '', 1, 1))) }}
                                                    </span>
                                                </div>
                                                <span class="fw-semibold">{{ $student->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-printer-line me-1"></i> Rapor
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><h6 class="dropdown-header">STS — Tengah Semester</h6></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sts', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><h6 class="dropdown-header">SAS — Nilai Akhir</h6></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'id']) }}">Indonesia (LTR)</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="{{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => $userId, 'studyGroupId' => $studyGroup->id, 'studentId' => $student->id, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSem, 'jenis' => 'sas', 'bahasa' => 'ar']) }}">Arab (RTL)</a></li>
                                                </ul>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm ms-1 js-note"
                                                    data-name="{{ $student->name }}"
                                                    data-note="{{ $registrations[$student->id]->homeroom_note ?? '' }}"
                                                    data-action="{{ route('user.schools.nilai-kelas.rapor.note', [
                                                        'userId' => $userId,
                                                        'studyGroupId' => $studyGroup->id,
                                                        'studentId' => $student->id,
                                                        'academic_year_id' => $selectedAyId,
                                                        'semester' => $selectedSem,
                                                    ]) }}">
                                                <i class="ri-chat-1-line me-1"></i> Catatan
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="ri-group-line me-1"></i>Tidak ada Santri.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Catatan Wali Kelas --}}
    <div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="noteForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-chat-1-line me-1"></i> Catatan Wali Kelas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-2">Santri: <strong id="noteStudent">—</strong></p>
                        <textarea name="homeroom_note" id="noteText" class="form-control" rows="4" maxlength="500"
                                  placeholder="Catatan untuk rapor (maks. 500 karakter)"></textarea>
                        <small class="text-muted">Catatan ini tampil pada bagian "Catatan Wali Kelas" di Rapor.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-note').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('noteForm').action = btn.dataset.action;
        document.getElementById('noteStudent').textContent = btn.dataset.name || '—';
        document.getElementById('noteText').value = btn.dataset.note || '';
        new bootstrap.Modal(document.getElementById('noteModal')).show();
    });
});
</script>
@endpush
