@extends('layouts.master')

@section('title', 'Mapping Surah–Juz')

@section('content')
    @php
        $userId = $userId ?? request()->route('userId') ?? auth()->id();
        $avgPerJuz = $totalJuz > 0 ? round($totalSurah / $totalJuz, 1) : 0;
    @endphp

    @include('tahfidz._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.tahfidz.workspace.index', ['userId' => $userId]) }}">Tahfidz</a> @endslot
        @slot('li_2') Referensi @endslot
        @slot('title') Mapping Surah–Juz @endslot
    @endcomponent

    <div class="row tahfidz-stat">
        @foreach([
            ['Total Surah', number_format($totalSurah), 'ri-book-open-line', 'primary', 'Seluruh surah Al-Qur\'an'],
            ['Total Juz', number_format($totalJuz), 'ri-stack-line', 'info', 'Pembagian juz 1–30'],
            ['Rata-rata per Juz', $avgPerJuz, 'ri-bar-chart-line', 'success', 'Surah dalam satu juz'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-4 col-md-6">
                <div class="card card-animate">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $color }}-subtle rounded fs-2">
                                    <i class="{{ $icon }} text-{{ $color }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p>
                                <h3 class="fw-bold ff-secondary mb-0">{{ $value }}</h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 stat-label">{{ $note }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-map-2-line text-primary me-1"></i>Pemetaan Surah per Juz</h5>
                    <p class="text-muted mb-0 small">Dipakai untuk menentukan posisi setoran dan target hafalan.</p>
                </div>
                <div class="tahfidz-filter-actions" style="min-width: 240px">
                    <input type="search" id="surahSearch" class="form-control" placeholder="Cari surah atau juz…"
                        autocomplete="off">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3" id="juzGrid">
                @forelse($byJuz as $juz => $surahs)
                    @php
                        $juzMeta = $juzList->get($juz);
                        $searchText = strtolower(
                            'juz ' . $juz . ' ' . ($juzMeta->name ?? '') . ' ' .
                            $surahs->pluck('name_latin')->implode(' ')
                        );
                    @endphp
                    <div class="col-xl-6 juz-card" data-search="{{ $searchText }}">
                        <div class="card border mb-0 h-100">
                            <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">
                                        <span class="badge bg-primary-subtle text-primary me-1">Juz {{ $juz }}</span>
                                        {{ $juzMeta->name ?? '—' }}
                                    </h6>
                                    <p class="text-muted mb-0 small">
                                        {{ $surahs->count() }} surah
                                        @if($juzMeta)
                                            · hal. {{ $juzMeta->halaman_start }}–{{ $juzMeta->halaman_end }}
                                        @endif
                                    </p>
                                </div>
                                <span class="badge bg-light text-dark">{{ $surahs->sum('total_ayat') }} ayat</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0 tahfidz-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 52px">No</th>
                                            <th>Surah</th>
                                            <th class="text-center" style="width: 70px">Ayat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($surahs->sortBy('number') as $surah)
                                            <tr>
                                                <td class="text-muted">{{ $surah->number }}</td>
                                                <td>
                                                    <div class="fw-medium">{{ $surah->name_latin }}</div>
                                                    <div class="text-muted small" dir="rtl">{{ $surah->name_arabic }}</div>
                                                </td>
                                                <td class="text-center">{{ $surah->total_ayat }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <x-empty-state title="Data surah belum tersedia"
                            message="Jalankan seeder master Al-Qur'an agar pemetaan surah–juz bisa ditampilkan." />
                    </div>
                @endforelse
            </div>

            <div id="searchEmpty" class="text-center text-muted py-4 d-none">
                <i class="ri-search-eye-line fs-3 d-block mb-2"></i>
                Tidak ada surah atau juz yang cocok dengan pencarian.
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('surahSearch');
            const cards = Array.from(document.querySelectorAll('.juz-card'));
            const empty = document.getElementById('searchEmpty');
            if (!input) return;

            input.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                let visible = 0;

                cards.forEach(function (card) {
                    const match = term === '' || (card.dataset.search || '').includes(term);
                    card.classList.toggle('d-none', !match);
                    if (match) visible++;
                });

                if (empty) empty.classList.toggle('d-none', visible !== 0);
            });
        });
    </script>
@endpush
