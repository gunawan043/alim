@extends('layouts.master')
@section('title') Asesmen @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Evaluasi @endslot
        @slot('title') Asesmen @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100"><div class="card-body"><p class="text-muted mb-1">Total Asesmen</p><h3 class="mb-0">{{ $stats['total'] }}</h3></div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100"><div class="card-body"><p class="text-muted mb-1">Perlu Soal</p><h3 class="mb-0">{{ $stats['no_bank'] }}</h3></div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100"><div class="card-body"><p class="text-muted mb-1">Dalam Paket</p><h3 class="mb-0">{{ $stats['paket'] }}</h3></div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100"><div class="card-body"><p class="text-muted mb-1">Selesai</p><h3 class="mb-0">{{ $stats['final'] }}</h3></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">Daftar Asesmen</h5>
                <p class="text-muted mb-0">Satu tempat untuk kisi-kisi, soal, paket, cetak, dan LJK.</p>
            </div>
            <a href="{{ route('user.kisi-kisi-soal.create', ['userId' => $userId]) }}" class="btn btn-success">
                <i class="ri-add-line me-1"></i> Buat Asesmen
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Cari</label>
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Judul asesmen...">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select">
                        <option value="">Semua tahun ajaran</option>
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ request('academic_year_id') === $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">Semua</option>
                        <option value="ganjil" {{ request('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ request('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button class="btn btn-primary w-100" type="submit"><i class="ri-search-line me-1"></i> Filter</button>
                </div>
                <div class="col-lg-2 col-md-6">
                    <a href="{{ route('user.asesmen.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Asesmen</th>
                            <th>Periode</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th class="text-end">Langkah Berikutnya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assessments as $assessment)
                            @php $state = $states[$assessment->id]; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('user.asesmen.show', ['userId' => $userId, 'kisiUuid' => $assessment->id]) }}" class="fw-semibold link-primary">
                                        {{ $assessment->judul }}
                                    </a>
                                    <div class="small text-muted">
                                        {{ $assessment->subject?->name ?? '-' }} · {{ $assessment->gradeLevel?->name ?? 'Semua tingkat' }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $assessment->academicYear?->name ?? '-' }}</div>
                                    <small class="text-muted">{{ ucfirst($assessment->semester) }}</small>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ str_replace('_', ' ', ucfirst($assessment->jenis_ujian)) }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $state['color'] }}-subtle text-{{ $state['color'] }}">{{ $state['label'] }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route($state['route'], $state['params']) }}" class="btn btn-sm btn-soft-primary">
                                        {{ $state['label'] }} <i class="ri-arrow-right-line ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">Belum ada asesmen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $assessments->links() }}</div>
        </div>
    </div>
@endsection
