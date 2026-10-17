@extends('layouts.master')
@section('title', 'Bank Soal Terpusat')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') Evaluasi @endslot
        @slot('title') Bank Soal Terpusat @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK REPOSITORY --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-user-line text-primary"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Soal Saya</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['saya']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Dibuat oleh Anda</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-team-line text-info"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Soal Serumpun</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['serumpun']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-share-forward-line me-1"></i>Guru lain, mapel serumpun</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-verified-badge-line text-success"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Terverifikasi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['terverifikasi']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Approved oleh validator</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-history-line text-warning"></i></span></div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Tahun Sebelumnya</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['historis']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-archive-2-line me-1"></i>Siap dipakai ulang sebagai dasar</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="bankSoalTerpusatList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 d-flex justify-content-between">
                        <div class="col-lg-8">
                            <h5 class="card-title mb-0">Repository Soal Lintas Satuan Pendidikan</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $soalList->total() }} soal</span>
                                <span class="text-muted small ms-2">Serumpun = mapel, jenjang/kelas, tahun ajaran, semester — bukan sekolah.</span>
                            </p>
                        </div>
                        <div class="col-lg-4 d-flex justify-content-end">
                            <a href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" class="btn btn-success" style="margin-left:.5rem;">
                                <i class="ri-add-line align-bottom me-1"></i> Buat Soal
                            </a>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" action="{{ route('user.bank-soal-terpusat.index', ['userId' => $userId]) }}" class="d-flex flex-wrap gap-2">
                                <input type="text" name="q" class="form-control" style="width:200px" placeholder="Cari soal..." value="{{ request('q') }}">
                                <input type="text" name="materi" class="form-control" style="width:150px" placeholder="Materi/topik" value="{{ request('materi') }}">
                                <select name="subject_id" class="form-select" style="width:150px" onchange="this.form.submit()">
                                    <option value="">Mapel</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ request('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                                <select name="grade_level_id" class="form-select" style="width:130px" onchange="this.form.submit()">
                                    <option value="">Kelas</option>
                                    @foreach($gradeLevels as $grade)
                                        <option value="{{ $grade->id }}" {{ request('grade_level_id') === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                                <select name="academic_year_id" class="form-select" style="width:130px" onchange="this.form.submit()">
                                    <option value="">T.A.</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ request('academic_year_id') === $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                                <select name="semester" class="form-select" style="width:105px" onchange="this.form.submit()">
                                    <option value="">Semester</option>
                                    <option value="ganjil" {{ request('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ request('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                                @php
                                    $jenisAsesmenOptions = [
                                        'pilihan_ganda' => 'Pilihan Ganda',
                                        'multiple_choice_complex' => 'PG Kompleks',
                                        'benar_salah' => 'Benar/Salah',
                                        'menjodohkan' => 'Menjodohkan',
                                        'isian_singkat' => 'Isian Singkat',
                                        'uraian' => 'Uraian',
                                        'campuran' => 'Campuran',
                                    ];
                                    $bentukSoalOptions = ['pg' => 'PG', 'bs' => 'B/S', 'jodoh' => 'Jodoh', 'isian' => 'Isian', 'uraian' => 'Uraian'];
                                @endphp
                                <select name="jenis_asesmen" class="form-select" style="width:140px" onchange="this.form.submit()">
                                    <option value="">Jenis Asesmen</option>
                                    @foreach($jenisAsesmenOptions as $value => $label)
                                        <option value="{{ $value }}" {{ request('jenis_asesmen') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="tipe_soal" class="form-select" style="width:110px" onchange="this.form.submit()">
                                    <option value="">Bentuk</option>
                                    @foreach($bentukSoalOptions as $value => $label)
                                        <option value="{{ $value }}" {{ request('tipe_soal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="tingkat_kesulitan_estimasi" class="form-select" style="width:110px" onchange="this.form.submit()">
                                    <option value="">Kesulitan</option>
                                    <option value="mudah" {{ request('tingkat_kesulitan_estimasi') === 'mudah' ? 'selected' : '' }}>Mudah</option>
                                    <option value="sedang" {{ request('tingkat_kesulitan_estimasi') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                                    <option value="sulit" {{ request('tingkat_kesulitan_estimasi') === 'sulit' ? 'selected' : '' }}>Sulit</option>
                                </select>
                                <select name="workflow_status" class="form-select" style="width:130px" onchange="this.form.submit()">
                                    <option value="">Status</option>
                                    @foreach(\App\Models\Soal::WORKFLOW_OPTIONS as $value => $label)
                                        <option value="{{ $value }}" {{ request('workflow_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if($canViewAll ?? false)
                                <select name="school_id" class="form-select" style="width:150px" onchange="this.form.submit()">
                                    <option value="">Satuan Pendidikan</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ request('school_id') === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                                    @endforeach
                                </select>
                                @endif
                                <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                                <a href="{{ route('user.bank-soal-terpusat.index', ['userId' => $userId]) }}" class="btn btn-light"><i class="ri-refresh-line"></i></a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['scope' => null]) }}" class="filter-badge {{ ! $scope ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['scope' => 'saya']) }}" class="filter-badge {{ $scope === 'saya' ? 'active' : '' }}">Soal Saya</a>
                        <a href="{{ request()->fullUrlWithQuery(['scope' => 'serumpun']) }}" class="filter-badge {{ $scope === 'serumpun' ? 'active' : '' }}">Soal Serumpun</a>
                        <a href="{{ request()->fullUrlWithQuery(['scope' => 'terverifikasi']) }}" class="filter-badge {{ $scope === 'terverifikasi' ? 'active' : '' }}">Terverifikasi</a>
                        <a href="{{ request()->fullUrlWithQuery(['scope' => 'historis']) }}" class="filter-badge {{ $scope === 'historis' ? 'active' : '' }}">Tahun Sebelumnya</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mapel / Konteks</th>
                                <th>Pertanyaan</th>
                                <th class="text-center">Bentuk</th>
                                <th class="text-center">Kesulitan</th>
                                <th class="text-center">Status</th>
                                <th>Pembuat</th>
                                <th class="text-end" style="width:170px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($soalList as $soal)
                                @php
                                    $wMeta = [
                                        'draft' => ['Draft', 'bg-secondary-subtle text-secondary'],
                                        'review' => ['Review', 'bg-info-subtle text-info'],
                                        'revisi' => ['Perlu Perbaikan', 'bg-danger-subtle text-danger'],
                                        'approved' => ['Approved', 'bg-success-subtle text-success'],
                                    ][$soal->workflow_status] ?? [ucfirst($soal->workflow_status), 'bg-secondary-subtle text-secondary'];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $soal->bankSoal?->subject?->name ?? '—' }}</div>
                                        <div class="small text-muted">
                                            {{ $soal->bankSoal?->jenjang ? strtoupper($soal->bankSoal->jenjang).' · ' : '' }}{{ $soal->bankSoal?->gradeLevel?->name ?? 'Semua Kelas' }}
                                            @if($soal->bankSoal?->academicYear) · {{ $soal->bankSoal->academicYear->name }} @endif
                                            @if($soal->bankSoal?->semester) · {{ ucfirst($soal->bankSoal->semester) }} @endif
                                        </div>
                                        @if($soal->materi)<div class="small text-muted"><i class="ri-price-tag-3-line me-1"></i>{{ $soal->materi }}</div>@endif
                                    </td>
                                    <td class="small" style="max-width:420px;">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 160) }}
                                        @if($soal->tujuanPembelajaran)
                                            <div class="small text-muted mt-1"><span class="badge bg-primary-subtle text-primary">{{ $soal->tujuanPembelajaran->kode_tp }}</span></div>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="badge bg-light text-dark">{{ strtoupper($soal->tipe_soal) }}</span></td>
                                    <td class="text-center">
                                        @php $dColor = ['mudah' => 'success', 'sedang' => 'warning', 'sulit' => 'danger'][$soal->tingkat_kesulitan_estimasi] ?? 'secondary'; @endphp
                                        <span class="badge bg-{{ $dColor }}-subtle text-{{ $dColor }}">{{ ucfirst($soal->tingkat_kesulitan_estimasi) }}</span>
                                    </td>
                                    <td class="text-center"><span class="badge {{ $wMeta[1] }}">{{ $wMeta[0] }}</span></td>
                                    <td class="small">
                                        {{ $soal->creator?->name ?? '—' }}
                                        <div class="text-muted">{{ $soal->bankSoal?->school?->name ?? 'Institusi' }}</div>
                                    </td>
                                    <td class="text-end">
                                        @if(($soal->similarity_summary['total'] ?? 0) > 0)
                                            <span class="badge bg-warning-subtle text-warning" title="Ditemukan kemiripan — klik Detail untuk memeriksa">
                                                <i class="ri-scan-2-line"></i> {{ $soal->similarity_summary['total'] }}
                                            </span>
                                        @endif
                                        @if($soal->derived_from_soal_id)
                                            <span class="badge bg-info-subtle text-info" title="Turunan dari soal lain">Turunan</span>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-soft-secondary" title="Lihat detail soal"
                                                onclick="showDetail('{{ $soal->id }}')">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <form method="POST" action="{{ route('user.bank-soal-terpusat.reuse', ['userId' => $userId, 'soalId' => $soal->id]) }}" class="d-inline"
                                              onsubmit="return confirm('Buat salinan turunan dari soal ini? Soal asli tidak berubah dan wajib review ulang.');">
                                            @csrf
                                            <button class="btn btn-sm btn-soft-primary" title="Gunakan sebagai Dasar">
                                                <i class="ri-file-copy-2-line"></i> Gunakan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-archive-2-line fs-1 d-block mb-2"></i>
                                            Tidak ada soal pada filter ini.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body pt-0">{{ $soalList->links() }}</div>
            </div>
        </div>
    </div>

    {{-- Modal detail soal historis (kunci/pembahasan mengikuti hak akses) --}}
    <div class="modal fade zoomIn" id="detail-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle p-3">
                    <h5 class="modal-title"><i class="ri-eye-line me-1"></i>Detail Soal Historis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detail-body">Memuat…</div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
function renderDetail(data) {
    var s = data.soal;
    var html = '<div class="mb-2">' + (s.pertanyaan || '-') + '</div>';

    if (s.options && s.options.length) {
        html += '<ul class="list-unstyled small mb-2">';
        s.options.forEach(function (o) {
            html += '<li><strong>' + (o.label || '') + '.</strong> ' + (o.teks || '') + (o.correct ? ' <span class="badge bg-success-subtle text-success">Kunci</span>' : '') + '</li>';
        });
        html += '</ul>';
    }

    html += '<table class="table table-sm small mb-0">';
    html += '<tr><th style="width:150px">Mapel / Kelas</th><td>' + (s.subject || '—') + ' · ' + (s.grade_level || 'Semua Kelas') + '</td></tr>';
    html += '<tr><th>Tahun / Semester</th><td>' + (s.academic_year || '—') + ' · ' + (s.semester ? s.semester.charAt(0).toUpperCase() + s.semester.slice(1) : '—') + '</td></tr>';
    html += '<tr><th>Jenis Asesmen</th><td>' + (s.jenis_asesmen ? s.jenis_asesmen.replace(/_/g, ' ') : '—') + ' · ' + (s.tipe_soal || '').toUpperCase() + '</td></tr>';
    html += '<tr><th>Materi</th><td>' + (s.materi || '—') + '</td></tr>';
    if (s.tp) { html += '<tr><th>TP</th><td>' + s.tp + '</td></tr>'; }
    html += '<tr><th>Kesulitan</th><td>' + (s.kesulitan || '—') + '</td></tr>';
    html += '<tr><th>Pembuat / Satuan</th><td>' + (s.pembuat || '—') + ' · ' + (s.school || 'Institusi') + '</td></tr>';
    html += '<tr><th>Status Validasi</th><td>' + (s.status || '—') + '</td></tr>';
    html += '</table>';

    if (s.pembahasan) {
        html += '<div class="border rounded p-2 bg-light-subtle small mt-2"><strong>Pembahasan:</strong> ' + s.pembahasan + '</div>';
    }
    if (! data.solution_visible) {
        html += '<p class="text-muted small mt-2 mb-0"><i class="ri-lock-line me-1"></i>Kunci jawaban & pembahasan disembunyikan sesuai hak akses.</p>';
    }
    return html;
}

function showDetail(soalId) {
    var url = {{ route('user.bank-soal-terpusat.detail', ['userId' => $userId, 'soalId' => '__SOAL__']) }};
    url = url.replace('__SOAL__', soalId);

    var body = document.getElementById('detail-body');
    body.innerHTML = 'Memuat…';

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            body.innerHTML = renderDetail(data);
            new bootstrap.Modal(document.getElementById('detail-modal')).show();
        })
        .catch(function () {
            body.innerHTML = '<div class="text-danger small">Gagal memuat detail soal.</div>';
        });
}
</script>
@endsection
