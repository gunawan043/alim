@extends('layouts.master')
@section('title', 'Pencarian')

@section('css')
    @include('kurikulum._styles')
    <style>
        .search-hero {
            background: linear-gradient(135deg, #eef6ff 0%, #f8fbff 55%, #ffffff 100%);
            border: 1px solid #dbeafe;
            border-radius: 18px;
            padding: 26px 22px;
        }
        .search-hero h2 { font-weight: 700; letter-spacing: -.02em; }
        .search-input { height: 54px; font-size: 16px; border-radius: 12px; }
        .source-pill {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: 999px;
            border: 1px solid #dbe3ee; background: #fff; color: #475569;
            text-decoration: none; font-size: 13px; font-weight: 500;
        }
        .source-pill:hover { border-color: #0a5f9e; color: #0a5f9e; }
        .source-pill.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
        .result-card { border: 1px solid #e5eaf1; border-radius: 12px; transition: all .2s ease; }
        .result-card:hover { border-color: #b9d4ec; box-shadow: 0 8px 24px rgba(15, 23, 42, .07); }
        .result-title { font-weight: 650; color: #16324f; text-decoration: none; }
        .result-title:hover { color: #0a5f9e; }
        .result-snippet { color: #64748b; font-size: 13px; line-height: 1.55; }
        .search-meta { color: #94a3b8; font-size: 12px; }
        .ai-answer { white-space: pre-wrap; line-height: 1.7; font-size: 14px; }
    </style>
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">Asesmen</a> @endslot
        @slot('title') Pencarian @endslot
    @endcomponent

    <div class="search-hero mb-3">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="text-center mb-3">
                    <h2 class="mb-1">Cari Soal, RPM, dan Sumber Belajar</h2>
                    <p class="text-muted mb-0">Telusuri database sekolah, minta rekomendasi AI, atau cari referensi di internet.</p>
                </div>

                <form method="GET" action="{{ route('user.pencarian.index', ['userId' => $userId]) }}">
                    <input type="hidden" name="source" value="{{ $source }}">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="ri-search-line text-muted"></i></span>
                        <input type="search" name="q" class="form-control search-input border-start-0" value="{{ $query }}" placeholder="Contoh: bilangan bulat kelas 7, RPM teks eksposisi, soal tahfidz..." autofocus>
                        <button class="btn btn-primary px-4" type="submit">Cari</button>
                    </div>
                </form>

                <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    @foreach(['db' => ['Database', 'ri-database-2-line'], 'ai' => ['Rekomendasi AI', 'ri-sparkling-2-line'], 'internet' => ['Internet', 'ri-global-line']] as $value => [$label, $icon])
                        <a class="source-pill {{ $source === $value ? 'active' : '' }}"
                           href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => $value, 'type' => $type]) }}">
                            <i class="{{ $icon }}"></i>{{ $label }}
                        </a>
                    @endforeach
                </div>

                @if($source === 'db')
                    <div class="text-center">
                        @foreach(['all' => 'Semua', 'soal' => 'Soal', 'rpm' => 'RPM', 'bank' => 'Bank Soal', 'paket' => 'Paket Soal'] as $value => $label)
                            <a class="filter-badge {{ $type === $value ? 'active' : '' }}"
                               href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'db', 'type' => $value]) }}">{{ $label }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($query === '')
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ri-search-eye-line fs-1 text-muted d-block mb-2"></i>
                <h5>Mulai pencarian</h5>
                <p class="text-muted mb-3">Ketik kata kunci, lalu pilih sumber: Database, AI, atau Internet.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => 'bilangan bulat', 'source' => 'db']) }}" class="filter-badge">bilangan bulat</a>
                    <a href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => 'teks eksposisi', 'source' => 'db']) }}" class="filter-badge">teks eksposisi</a>
                    <a href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => 'tahfidz', 'source' => 'db']) }}" class="filter-badge">tahfidz</a>
                    <a href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => 'asesmen formatif', 'source' => 'ai']) }}" class="filter-badge">rekomendasi AI</a>
                </div>
            </div>
        </div>
    @elseif($source === 'db')
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div class="text-muted small">{{ $total }} hasil untuk <strong>{{ $query }}</strong> di {{ $school->name }}</div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-success"><i class="ri-file-edit-line me-1"></i>Soal Saya</a>
                <a href="{{ route('user.soal-saya.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success"><i class="ri-add-line me-1"></i>Buat Soal</a>
            </div>
        </div>

        @if(in_array($type, ['all', 'soal'], true) && $soalResults->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ri-question-line text-primary me-1"></i>Soal <span class="badge bg-primary-subtle text-primary ms-1">{{ $soalResults->count() }}</span></h6>
                    <a href="{{ route('user.pencarian.index', ['userId' => $userId, 'q' => $query, 'source' => 'db', 'type' => 'soal']) }}" class="small text-decoration-none">Cari khusus soal <i class="ri-arrow-right-line"></i></a>
                </div>
                <div class="card-body">
                    @foreach($soalResults as $soal)
                        <div class="result-card p-3 mb-2">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div class="flex-grow-1">
                                    <a class="result-title" href="{{ route('user.soal.edit', ['userId' => $userId, 'bankId' => $soal->bank_soal_id, 'id' => $soal->id]) }}">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 170) }}
                                    </a>
                                    <div class="result-snippet mt-1">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($soal->materi ?: $soal->pembahasan)), 180) }}</div>
                                    <div class="search-meta mt-2">
                                        {{ $soal->bankSoal?->subject?->name ?? '-' }}
                                        @if($soal->bankSoal?->gradeLevel?->name) · {{ $soal->bankSoal->gradeLevel->name }}@endif
                                        · {{ \App\Models\Soal::JENIS_ASESMEN_OPTIONS[$soal->jenis_asesmen] ?? $soal->jenis_asesmen }}
                                        · {{ strtoupper($soal->tipe_soal) }}
                                        · {{ $soal->creator?->name ?? '-' }}
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-2">
                                    @if($soal->requiresValidation() && ! $soal->isValidated())
                                        <span class="badge bg-warning-subtle text-warning align-self-center">Belum valid</span>
                                    @endif
                                    <form method="POST" action="{{ route('user.soal-saya.clone', ['userId' => $userId, 'id' => $soal->id]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-soft-success" title="Klon ke Soal Saya"><i class="ri-file-copy-line"></i></button>
                                    </form>
                                    <a href="{{ route('user.soal.edit', ['userId' => $userId, 'bankId' => $soal->bank_soal_id, 'id' => $soal->id]) }}" class="btn btn-sm btn-soft-primary" title="Buka soal"><i class="ri-arrow-right-line"></i></a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(in_array($type, ['all', 'rpm'], true) && $rpmResults->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed"><h6 class="mb-0"><i class="ri-file-paper-2-line text-success me-1"></i>RPM / Perangkat <span class="badge bg-success-subtle text-success ms-1">{{ $rpmResults->count() }}</span></h6></div>
                <div class="card-body">
                    @foreach($rpmResults as $rpm)
                        <div class="result-card p-3 mb-2">
                            <a class="result-title" href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $rpm->id]) }}">{{ $rpm->judul }}</a>
                            <div class="result-snippet mt-1">{{ \Illuminate\Support\Str::limit(strip_tags((string) $rpm->catatan), 180) }}</div>
                            <div class="search-meta mt-2">{{ $rpm->subject?->name ?? '-' }} · {{ $rpm->gradeLevel?->name ?? '-' }} · {{ $rpm->teacher?->name ?? '-' }} · {{ \App\Models\PerangkatPembelajaran::STATUS_OPTIONS[$rpm->status] ?? $rpm->status }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(in_array($type, ['all', 'bank'], true) && $bankResults->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed"><h6 class="mb-0"><i class="ri-archive-2-line text-info me-1"></i>Bank Soal <span class="badge bg-info-subtle text-info ms-1">{{ $bankResults->count() }}</span></h6></div>
                <div class="card-body">
                    @foreach($bankResults as $bank)
                        <div class="result-card p-3 mb-2">
                            <a class="result-title" href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id]) }}">{{ $bank->nama }}</a>
                            <div class="result-snippet mt-1">{{ \Illuminate\Support\Str::limit(strip_tags((string) $bank->deskripsi), 180) }}</div>
                            <div class="search-meta mt-2">{{ $bank->subject?->name ?? '-' }} · {{ $bank->gradeLevel?->name ?? '-' }} · {{ $bank->owner?->name ?? '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(in_array($type, ['all', 'paket'], true) && $paketResults->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed"><h6 class="mb-0"><i class="ri-stack-line text-warning me-1"></i>Paket Soal <span class="badge bg-warning-subtle text-warning ms-1">{{ $paketResults->count() }}</span></h6></div>
                <div class="card-body">
                    @foreach($paketResults as $paket)
                        <div class="result-card p-3 mb-2">
                            <a class="result-title" href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}">{{ $paket->judul }}</a>
                            <div class="search-meta mt-2">{{ $paket->kode_paket }} · {{ $paket->kisiKisi?->subject?->name ?? '-' }} · {{ $paket->kisiKisi?->gradeLevel?->name ?? '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($total === 0)
            <div class="card"><div class="card-body text-center py-5 text-muted"><i class="ri-search-line fs-1 d-block mb-2"></i>Tidak ada hasil di database untuk <strong>{{ $query }}</strong>. Coba sumber AI atau Internet.</div></div>
        @endif
    @elseif($source === 'ai')
        <div class="card">
            <div class="card-header border-bottom-dashed"><h6 class="mb-0"><i class="ri-sparkling-2-line text-primary me-1"></i>Rekomendasi AI untuk "{{ $query }}"</h6></div>
            <div class="card-body">
                @if($aiError)
                    <div class="alert alert-warning mb-0">{{ $aiError }}</div>
                @else
                    <div class="ai-answer">{{ $aiAnswer ?: 'AI belum memberikan jawaban.' }}</div>
                @endif
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="ri-global-line text-info me-1"></i>Hasil Internet untuk "{{ $query }}"</h6>
                <span class="small text-muted">Buka tautan di tab baru</span>
            </div>
            <div class="card-body">
                @if($webError)
                    <div class="alert alert-warning mb-0">{{ $webError }}</div>
                @elseif(empty($webResults))
                    <div class="text-center text-muted py-4">Tidak ada hasil internet.</div>
                @else
                    @foreach($webResults as $result)
                        <div class="result-card p-3 mb-2">
                            <a class="result-title" href="{{ $result['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">{{ $result['title'] ?? 'Sumber' }}</a>
                            <div class="result-snippet mt-1">{{ $result['snippet'] ?? '' }}</div>
                            <div class="search-meta mt-1 text-truncate">{{ $result['url'] ?? '' }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
@endsection
