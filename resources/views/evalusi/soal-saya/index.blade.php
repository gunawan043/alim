@extends('layouts.master')
@section('title', 'Soal Saya')

@section('css')
    @include('kurikulum._styles')
    <style>
        .table-container { position: relative; width: 100%; overflow-x: auto; }
        .table-freeze { table-layout: auto; min-width: max-content; width: 100%; }
        .table-freeze th, .table-freeze td { white-space: normal; vertical-align: middle; padding: 12px 16px; word-break: break-word; }
        .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; border-bottom: 2px solid #dee2e6; }
    </style>
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">Asesmen</a> @endslot
        @slot('title') Soal Saya @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('import_warnings') && count(session('import_warnings')))
        <div class="alert alert-info alert-dismissible fade show">
            <strong>Catatan import:</strong>
            <ul class="mb-0 mt-1">@foreach(session('import_warnings') as $w)<li>{{ $w }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach([
            'total' => ['Total Soal', 'primary', 'ri-file-list-3-line', 'Semua soal yang Anda buat'],
            'draft' => ['Draft', 'secondary', 'ri-draft-line', 'Belum diajukan validasi'],
            'review' => ['Review/Revisi', 'warning', 'ri-time-line', 'Menunggu guru serumpun'],
            'approved' => ['Tervalidasi', 'success', 'ri-shield-check-line', 'Siap dicetak sebagai STS/SAS'],
            'formal' => ['STS/SAS', 'info', 'ri-award-line', 'Soal asesmen formal'],
        ] as $key => [$label, $color, $icon, $desc])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $color }}-subtle rounded fs-2">
                                    <i class="{{ $icon }} text-{{ $color }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ (int) ($stats[$key] ?? 0) }}</h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 stat-label">{{ $desc }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0">Soal Saya</h5>
                <p class="text-muted mb-0 small">Soal yang Anda buat. STS/SAS wajib validasi mayoritas guru serumpun sebelum dicetak.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('user.soal-saya.search', ['userId' => $userId]) }}" class="btn btn-soft-info"><i class="ri-search-line me-1"></i>Cari Soal</a>
                <a href="{{ route('user.pencarian.index', ['userId' => $userId]) }}" class="btn btn-soft-primary"><i class="ri-search-eye-line me-1"></i>Pencarian</a>
                <a href="{{ route('user.soal-saya.import-form', ['userId' => $userId]) }}" class="btn btn-soft-secondary"><i class="ri-file-word-line me-1"></i>Import Word</a>
                <a href="{{ route('user.soal-saya.create', ['userId' => $userId]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Buat Soal</a>
            </div>
        </div>
        <div class="card-header py-2 bg-light border-bottom">
            <div class="d-flex flex-wrap align-items-center">
                <span class="text-muted small me-2">Filter Cepat:</span>
                <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}" class="filter-badge {{ ! request('jenis_asesmen') ? 'active' : '' }}"><i class="ri-apps-line"></i>Semua</a>
                @foreach(['harian' => 'Harian', 'formatif' => 'Formatif', 'sts' => 'STS', 'sas' => 'SAS'] as $value => $label)
                    <a href="{{ route('user.soal-saya.index', ['userId' => $userId, 'jenis_asesmen' => $value]) }}" class="filter-badge {{ request('jenis_asesmen') === $value ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
                <a href="{{ route('user.soal-saya.index', ['userId' => $userId, 'jenis_asesmen' => 'sts']) }}" class="filter-badge {{ request('jenis_asesmen') === 'sts' ? 'active' : '' }}"><i class="ri-shield-check-line"></i>Perlu Validasi</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><input type="search" name="q" class="form-control" placeholder="Cari pertanyaan..." value="{{ request('q') }}"></div>
                <div class="col-md-3">
                    <select name="subject_id" class="form-select">
                        <option value="">Semua Mapel</option>
                        @foreach($subjects as $s)<option value="{{ $s->id }}" {{ request('subject_id') === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="jenis_asesmen" class="form-select">
                        <option value="">Semua Jenis</option>
                        @foreach(\App\Models\Soal::JENIS_ASESMEN_OPTIONS as $value => $label)<option value="{{ $value }}" {{ request('jenis_asesmen') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="tipe_soal" class="form-select">
                        <option value="">Semua Tipe</option>
                        @foreach(['pg' => 'Pilihan Ganda', 'bs' => 'Benar/Salah', 'jodoh' => 'Menjodohkan', 'isian' => 'Isian', 'uraian' => 'Uraian'] as $value => $label)<option value="{{ $value }}" {{ request('tipe_soal') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-search-line me-1"></i>Filter</button></div>
            </form>

            <form method="POST" action="{{ route('user.soal-saya.print', ['userId' => $userId]) }}" target="_blank">
                @csrf
                <div class="d-flex flex-wrap gap-3 align-items-center mb-2">
                    <strong class="small text-muted">Cetak terpilih:</strong>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="with_key" value="1" id="withKey"><label class="form-check-label small" for="withKey">Sertakan kunci</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="kop" value="1" id="withKop" checked><label class="form-check-label small" for="withKop">Kop sekolah</label></div>
                    <input type="text" name="judul" class="form-control form-control-sm" style="max-width:260px" placeholder="Judul cetak (opsional)">
                    <button type="submit" class="btn btn-sm btn-soft-secondary"><i class="ri-printer-line me-1"></i>Cetak PDF</button>
                </div>

                <div class="table-container">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px;"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.soal-check').forEach(c=>c.checked=this.checked)"></th>
                                <th>Pertanyaan</th>
                                <th>Mapel</th>
                                <th>Kelas</th>
                                <th>Jenis</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($questions as $soal)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input soal-check" name="ids[]" value="{{ $soal->id }}"></td>
                                    <td style="min-width:260px;">
                                        <div class="fw-semibold">{{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 140) }}</div>
                                        @if($soal->options->isNotEmpty())
                                            <div class="small text-muted">{{ $soal->options->count() }} opsi</div>
                                        @endif
                                    </td>
                                    <td>{{ $soal->bankSoal?->subject?->name ?? '-' }}</td>
                                    <td>{{ $soal->bankSoal?->gradeLevel?->name ?? '-' }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ \App\Models\Soal::JENIS_ASESMEN_OPTIONS[$soal->jenis_asesmen] ?? $soal->jenis_asesmen }}</span></td>
                                    <td>{{ strtoupper($soal->tipe_soal) }}</td>
                                    <td>
                                        @if($soal->requiresValidation())
                                            @if($soal->isValidated())
                                                <span class="badge bg-success-subtle text-success">Tervalidasi</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">{{ \App\Models\Soal::WORKFLOW_OPTIONS[$soal->workflow_status] ?? $soal->workflow_status }}</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Tanpa validasi</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('user.soal.edit', ['userId' => $userId, 'bankId' => $soal->bank_soal_id, 'id' => $soal->id]) }}" class="btn btn-sm btn-soft-primary" title="Edit"><i class="ri-pencil-line"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada soal. Mulai dengan <strong>Buat Soal</strong> atau <strong>Import Word</strong>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="mt-3">{{ $questions->links() }}</div>
        </div>
    </div>
@endsection
