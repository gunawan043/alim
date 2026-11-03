@extends('layouts.master')

@section('title', 'Standar Penilaian Tahfidz')

@section('content')
    @php
        $userId = $userId ?? request()->route('userId') ?? auth()->id();

        $predikatColor = [
            'mumtaz' => 'success',
            'jayyid_jiddan' => 'info',
            'jayyid' => 'primary',
            'maqbul' => 'warning',
            'rasib' => 'danger',
        ];

        $exampleRow = $scale->firstWhere('key', $example['predikat']);
        $exampleLabel = $exampleRow['label'] ?? '—';
        $exampleColor = $predikatColor[$example['predikat']] ?? 'secondary';
        $komponen = ['Tahfizh', 'Tajwid', 'Fashohah'];
    @endphp

    @include('tahfidz._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.tahfidz.workspace.index', ['userId' => $userId]) }}">Tahfidz</a> @endslot
        @slot('li_2') Referensi @endslot
        @slot('title') Standar Penilaian @endslot
    @endcomponent

    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="ri-information-line fs-4"></i>
        <div>
            <strong>Ambang predikat di bawah dibaca langsung dari mesin penilaian.</strong>
            <div class="small mb-0">
                Halaman ini hanya referensi baca. Angka yang dipakai rapor dan syahadah berasal dari
                <code>TahfidzTasmiService</code>, jadi daftar ini tidak pernah menyimpang dari perhitungan nyata.
            </div>
        </div>
    </div>

    <div class="row tahfidz-stat">
        @foreach([
            ['Jumlah Predikat', $scale->count(), 'ri-award-line', 'primary', 'Tingkat capaian yang diakui'],
            ['Komponen Nilai', count($komponen), 'ri-list-check-2', 'info', 'Tahfizh, tajwid, fashohah'],
            ['Bobot per Komponen', 'Rata-rata', 'ri-scales-3-line', 'warning', 'Nilai akhir = jumlah ÷ 3'],
            ['Nilai Minimum Lulus', '60', 'ri-checkbox-circle-line', 'success', 'Di bawah 60 = Rasib'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6">
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

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-award-line text-primary me-1"></i>Skala Predikat</h5>
                    <p class="text-muted mb-0 small">Rentang nilai akhir beserta padanan istilah Arabnya.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 tahfidz-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px">#</th>
                                <th>Predikat</th>
                                <th>Istilah Arab</th>
                                <th class="text-center">Rentang Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scale as $row)
                                @php $color = $predikatColor[$row['key']] ?? 'secondary'; @endphp
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $row['label'] }}</span>
                                    </td>
                                    <td class="fs-5" dir="rtl">{{ $row['arabic'] }}</td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $row['min'] }}</span>
                                        <span class="text-muted">–</span>
                                        <span class="fw-semibold">{{ $row['max'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state colspan="4" title="Skala predikat belum tersedia"
                                    message="Mesin penilaian tidak mengembalikan predikat apa pun." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light-subtle">
                    <p class="text-muted mb-0 small">
                        <i class="ri-shield-check-line me-1"></i>
                        Nilai di atas 100 atau di bawah 0 otomatis dipangkas ke rentang 0–100 sebelum dihitung.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-calculator-line text-info me-1"></i>Contoh Perhitungan</h5>
                    <p class="text-muted mb-0 small">Tiga komponen dengan bobot setara.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-3">
                            <tbody>
                                @foreach([
                                    ['Tahfizh', $example['tahfizh']],
                                    ['Tajwid', $example['tajwid']],
                                    ['Fashohah', $example['fashohah']],
                                ] as [$nama, $nilai])
                                    <tr>
                                        <td class="text-muted">{{ $nama }}</td>
                                        <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-top">
                                    <td class="fw-semibold">Nilai Akhir</td>
                                    <td class="text-end">
                                        <span class="fs-4 fw-bold text-{{ $exampleColor }}">
                                            {{ rtrim(rtrim(number_format($example['nilai_akhir'], 2, ',', '.'), '0'), ',') }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 rounded border border-{{ $exampleColor }}-subtle bg-{{ $exampleColor }}-subtle">
                        <div>
                            <p class="text-uppercase fw-semibold text-muted mb-1 stat-label">Predikat</p>
                            <h5 class="mb-0 text-{{ $exampleColor }}">{{ $exampleLabel }}</h5>
                        </div>
                        <span class="fs-2 text-{{ $exampleColor }}" dir="rtl">
                            {{ $exampleRow['arabic'] ?? '—' }}
                        </span>
                    </div>

                    <p class="text-muted small mb-0 mt-3">
                        <i class="ri-information-line me-1"></i>
                        Nilai akhir dibulatkan dua desimal sebelum dibandingkan dengan ambang predikat.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
