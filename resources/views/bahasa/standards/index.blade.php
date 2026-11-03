@extends('layouts.master')

@section('title', 'Standar Penilaian Bahasa')

@section('content')
    @php
        $userId = $userId ?? request()->route('userId') ?? auth()->id();

        $withKkm = $books->filter(fn ($book) => $book->kktp !== null)->count();
        $withoutKkm = $books->count() - $withKkm;
        $avgKkm = $books->filter(fn ($book) => $book->kktp !== null)->avg(fn ($book) => $book->kktp->kkm_score);
    @endphp

    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Referensi @endslot
        @slot('title') Standar Penilaian @endslot
    @endcomponent

    <div class="lang-hero mb-3">
        <h3 class="mb-1">Standar Penilaian Bahasa</h3>
        <p>
            KKM/KKTP tiap buku ajar Bahasa Arab dan Bahasa Inggris.
            Angka di halaman ini adalah angka yang sama dengan yang dipakai rapor —
            sumbernya <code class="text-white">subject_kktp</code>, bukan salinan terpisah.
        </p>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Buku Ajar', $books->count(), 'Total buku aktif semester ini'],
            ['Sudah Ada KKM', $withKkm, 'Siap dipakai perhitungan nilai'],
            ['Belum Ada KKM', $withoutKkm, 'Perlu dilengkapi guru mapel'],
            ['Rata-rata KKM', $avgKkm !== null ? rtrim(rtrim(number_format($avgKkm, 2, ',', '.'), '0'), ',') : '—', 'KKM seluruh buku'],
        ] as [$label, $value, $hint])
            <div class="col-xl-3 col-md-6">
                <div class="lang-stat">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                    <div class="hint">{{ $hint }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-ruler-line text-success me-1"></i>KKM / KKTP per Buku Ajar</h5>
                    <p class="text-muted mb-0 small">
                        {{ $academicYear->name ?? 'Tahun ajaran aktif' }} · Semester {{ ucfirst($semester) }}
                    </p>
                </div>
                <form method="GET" action="{{ route('user.bahasa.standards.index', ['userId' => $userId]) }}"
                    class="d-flex flex-wrap gap-2">
                    <select name="semester" class="form-select form-select-sm" style="width: 130px" onchange="this.form.submit()">
                        <option value="ganjil" @selected($semester === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected($semester === 'genap')>Genap</option>
                    </select>
                    <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}" class="btn btn-sm btn-soft-danger">
                        <i class="ri-error-warning-line me-1"></i>Pelanggaran
                    </a>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th class="text-center" style="width: 130px">KKM / KKTP</th>
                        <th>Catatan</th>
                        <th class="text-center" style="width: 130px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $book)
                        @php
                            $kkm = $book->kktp?->kkm_score;
                            $notes = $book->kktp?->notes;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $book->subject?->name ?? '—' }}</div>
                                <div class="lang-muted">{{ $book->subject?->code }}</div>
                            </td>
                            <td>{{ $book->studyGroup?->name ?? '—' }}</td>
                            <td class="text-center">
                                @if($kkm !== null)
                                    <span class="fw-bold text-success">{{ rtrim(rtrim(number_format($kkm, 2, ',', '.'), '0'), ',') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="lang-muted">{{ $notes ?: '—' }}</td>
                            <td class="text-center">
                                @if($kkm !== null)
                                    <span class="badge lang-badge bg-success-subtle text-success">Terisi</span>
                                @else
                                    <span class="badge lang-badge bg-warning-subtle text-warning">Belum diisi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="5" title="Belum ada buku ajar bahasa"
                            message="Buku ajar Bahasa Arab atau Bahasa Inggris untuk semester ini belum dibuat di modul akademik." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-light-subtle">
            <p class="text-muted mb-0 small">
                <i class="ri-information-line me-1"></i>
                KKM/KKTP diubah dari modul Akademik → Buku Ajar. Halaman ini hanya menampilkan, agar tidak ada dua sumber angka.
            </p>
        </div>
    </div>
@endsection
