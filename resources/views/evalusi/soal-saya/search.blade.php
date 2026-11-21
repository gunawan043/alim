@extends('layouts.master')
@section('title', 'Cari Soal')

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}">Soal Saya</a> @endslot
        @slot('title') Cari Soal @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0">Katalog Soal Guru</h5>
                    <p class="text-muted mb-0 small">Cari soal yang dibagikan guru lain, lalu ambil ke Soal Saya.</p>
                </div>
                <a href="{{ route('user.soal-saya.index', ['userId' => $userId]) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><input type="search" name="q" class="form-control" placeholder="Cari pertanyaan / materi..." value="{{ request('q') }}"></div>
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
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-search-line me-1"></i>Cari</button></div>
            </form>

            <div class="row g-3">
                @forelse($questions as $soal)
                    <div class="col-xl-6">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <span class="badge bg-primary-subtle text-primary">{{ $soal->bankSoal?->subject?->name ?? '-' }}</span>
                                    @if($soal->bankSoal?->gradeLevel?->name)<span class="badge bg-info-subtle text-info">{{ $soal->bankSoal->gradeLevel->name }}</span>@endif
                                    <span class="badge bg-secondary-subtle text-secondary">{{ \App\Models\Soal::JENIS_ASESMEN_OPTIONS[$soal->jenis_asesmen] ?? $soal->jenis_asesmen }}</span>
                                    <span class="badge bg-light text-muted">{{ strtoupper($soal->tipe_soal) }}</span>
                                </div>
                                <div class="fw-semibold mb-2">{{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 260) }}</div>
                                @if($soal->options->isNotEmpty())
                                    <ul class="list-unstyled small text-muted mb-2">
                                        @foreach($soal->options as $option)
                                            <li><strong>{{ $option->label }}.</strong> {{ \Illuminate\Support\Str::limit($option->teks_opsi, 120) }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                    <small class="text-muted">Dibuat: {{ $soal->creator?->name ?? '-' }} · {{ $soal->created_at?->format('d M Y') }}</small>
                                    @if((string) $soal->dibuat_oleh === (string) $userId)
                                        <span class="badge bg-success-subtle text-success">Soal Saya</span>
                                    @else
                                        <form method="POST" action="{{ route('user.soal-saya.clone', ['userId' => $userId, 'id' => $soal->id]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-soft-success"><i class="ri-download-2-line me-1"></i>Ambil ke Soal Saya</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-5">Tidak ada soal yang cocok.</div>
                @endforelse
            </div>

            <div class="mt-3">{{ $questions->links() }}</div>
        </div>
    </div>
@endsection
