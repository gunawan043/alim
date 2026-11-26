@extends('layouts.master')
@section('title') Nilai Saya @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Nilai Saya @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @php
        $allBooks = $adminBooks->flatten();

        $filteredBooks = $allBooks
            ->filter(fn($b) => !request('subject_id') || $b->subject_id == request('subject_id'))
            ->filter(fn($b) => !request('study_group_id') || $b->study_group_id == request('study_group_id'))
            ->values();

        $totalBooks     = $allBooks->count();
        $totalSubjects  = $allBooks->pluck('subject_id')->unique()->filter()->count();
        $totalGroups    = $allBooks->pluck('study_group_id')->unique()->filter()->count();

        $stats = [
            'books'    => ['label' => 'Total Buku Admin', 'value' => $totalBooks,    'icon' => 'ri-book-2-line',      'color' => 'primary', 'note' => 'Buku administrasi tersedia'],
            'subjects' => ['label' => 'Mata Pelajaran',   'value' => $totalSubjects, 'icon' => 'ri-book-open-line',   'color' => 'info',    'note' => 'Mapel yang diampu'],
            'groups'   => ['label' => 'Kelas',            'value' => $totalGroups,   'icon' => 'ri-group-line',       'color' => 'success', 'note' => 'Kelas yang diampu'],
            'shown'    => ['label' => 'Hasil Filter',     'value' => $filteredBooks->count(), 'icon' => 'ri-filter-3-line', 'color' => 'warning', 'note' => 'Buku sesuai filter'],
        ];
    @endphp

    {{-- ============================================================
         BUKU HALAQAH — khusus guru tahfidz
         Berbeda dari buku mapel: dinilai per halaqah dengan kolom
         Ziyadah, Rabt, STS, SAS. Tidak dicampur dengan daftar mapel.
    ============================================================ --}}
    @if(($tahfidzRows ?? collect())->isNotEmpty())
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-book-3-line me-2 text-primary"></i>Buku Nilai Halaqah (Tahfidz)</h5>
                        <p class="text-muted mb-0 small">
                            Buku nilai tahfidz diikat ke halaqah Anda — bukan rombel. Nilai diisi dengan kolom
                            Ziyadah, Rabt, STS, dan SAS dan tersimpan otomatis.
                        </p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Halaqah</th>
                                    <th>Rombel / Tingkat Santri</th>
                                    <th>Mata Pelajaran</th>
                                    <th class="text-center">Santri</th>
                                    <th class="text-center">Semester</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tahfidzRows as $i => $row)
                                    <tr>
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $row['group']->name }}</td>
                                        <td class="small text-muted">Halaqah · {{ $row['group']->studyGroup?->name ?? 'lintas rombel' }}</td>
                                        <td>{{ $row['subject']?->name ?? '—' }}</td>
                                        <td class="text-center">{{ $row['member_count'] }} santri</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $row['book']->semester == 'ganjil' ? 'info' : 'warning' }}-subtle text-{{ $row['book']->semester == 'ganjil' ? 'info' : 'warning' }}">
                                                {{ ucfirst($row['book']->semester) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.tahfidz.penilaian.index', ['userId' => $userId, 'group_id' => $row['group']->id, 'subject_id' => $row['subject']?->id, 'semester' => $row['book']->semester]) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="ri-folder-open-line me-1"></i> Buka
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Guru tahfidz tidak mengampu mapel umum: blok ini tidak ditampilkan. --}}
    @if($allBooks->isNotEmpty())
    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row">
        @foreach($stats as $key => $card)
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate stat-card h-90">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $card['color'] }}-subtle rounded">
                                    <i class="{{ $card['icon'] }} text-{{ $card['color'] }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="stat-label mb-0">{{ $card['label'] }}</p>
                                <h3 class="stat-value">{{ number_format($card['value']) }}</h3>
                            </div>
                        </div>
                        <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>{{ $card['note'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ============================================================
         CARD UTAMA
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Nilai Saya</h5>
                            <p class="text-muted mb-0 small">Daftar buku nilai yang Anda ampu. Mapel umum dibuka per rombel; tahfidz dibuka per halaqah.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- FILTER CEPAT (inline) --}}
                    <form method="GET" id="filterForm" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label fs-12 text-muted mb-1">Mata Pelajaran</label>
                                <select name="subject_id" class="form-select form-select-sm">
                                    <option value="">Semua Mapel</option>
                                    @foreach($subjects as $sub)
                                        <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label fs-12 text-muted mb-1">Kelas</label>
                                <select name="study_group_id" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    @foreach($allBooks->pluck('studyGroup')->unique('id') as $sg)
                                        <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                    <i class="ri-filter-3-line me-1"></i> Terapkan
                                </button>
                                <a href="{{ request()->url() }}"
                                   class="btn btn-light btn-sm flex-shrink-0" title="Reset filter">
                                    <i class="ri-refresh-line"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Counter --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $filteredBooks->count() }}</strong> dari
                            <strong>{{ $totalBooks }}</strong> buku admin
                        </small>
                    </div>

                    {{-- TABEL --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Kelas</th>
                                    <th class="text-center">Semester</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($filteredBooks as $i => $book)
                                    <tr>
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $book->subject->name ?? '—' }}</span>
                                            @if($book->subject->code)
                                                <span class="text-muted ms-1">{{ $book->subject->code }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ri-group-line me-1"></i>{{ $book->studyGroup->name }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $book->semester == 'ganjil' ? 'info' : 'warning' }}-subtle text-{{ $book->semester == 'ganjil' ? 'info' : 'warning' }}">
                                                <i class="ri-{{ $book->semester == 'ganjil' ? 'sun-line' : 'moon-line' }} me-1"></i>
                                                {{ ucfirst($book->semester) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.schools.guru-mapel.wizard', ['userId' => $userId, 'adminBookId' => $book->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Buka Buku Admin">
                                                    <i class="ri-folder-open-line me-1"></i> Buka
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <lord-icon
                                                src="https://cdn.lordicon.com/msoeawqm.json"
                                                trigger="loop"
                                                colors="primary:#121331,secondary:#08a88a"
                                                style="width:75px;height:75px">
                                            </lord-icon>
                                            <h6 class="text-muted mb-1 mt-3">Belum ada buku admin</h6>
                                            <p class="text-muted mb-0 small">Belum ada mapel yang diampu.</p>
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
    @endif
@endsection