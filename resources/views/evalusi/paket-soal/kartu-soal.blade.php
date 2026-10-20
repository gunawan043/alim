@extends('layouts.master')
@section('title') Kartu Soal @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}">Paket Soal</a> @endslot
        @slot('li_2') <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}">{{ $paket->nama ?? 'Paket' }}</a> @endslot
        @slot('title') Kartu Soal @endslot
    @endcomponent

    <x-soal-flow current="cetak" />

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-1">Kartu Soal — {{ $paket->nama ?? 'Paket' }}</h5>
                <p class="text-muted small mb-0">
                    <i class="ri-book-line me-1"></i>{{ $paket->kisiKisi?->subject?->name ?? '-' }}
                    &bull; Kelas {{ $paket->kisiKisi?->gradeLevel?->name ?? '-' }}
                    &bull; TA {{ $paket->kisiKisi?->academicYear?->name ?? '-' }}
                    &bull; Semester {{ ucfirst($paket->kisiKisi?->semester ?? '-') }}
                    &bull; {{ strtoupper($paket->kisiKisi?->jenis_ujian ?? '-') }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali ke Paket
                </a>
                <a href="{{ route('user.paket-soal.kartu-soal.cetak', ['userId' => $userId, 'paketUuid' => $paket->id]) }}"
                   target="_blank" class="btn btn-primary btn-sm">
                    <i class="ri-printer-line me-1"></i>Cetak Kartu Soal
                </a>
            </div>
        </div>
    </div>

    @if(! $canSeeKeys)
        <div class="alert alert-warning py-2 px-3 small" role="alert">
            <i class="ri-lock-line me-1"></i>
            Kunci jawaban disembunyikan untuk peran Anda — hanya penyusun serumpun dan tim kurikulum yang dapat melihat kunci.
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-file-list-3-line text-primary me-1"></i> Butir Soal ({{ $rows->count() }})</h5>
            <span class="badge bg-primary-subtle text-primary">Total Skor: {{ number_format($totalSkor, 2) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width:80px">Nomor Soal</th>
                            <th style="min-width:200px">Indikator</th>
                            <th>Soal</th>
                            <th style="width:190px">Kunci Jawaban</th>
                            <th style="width:140px">Level Kognitif</th>
                            <th style="width:110px">Skor Penilaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="text-center fw-semibold">{{ $row['nomor'] }}</td>
                                <td class="small">
                                    {{ $row['indikator'] ?: '—' }}
                                    @if($row['jenis'])
                                        <div class="text-muted" style="font-size:.7rem;">{{ ucfirst(str_replace('_', ' ', $row['jenis'])) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div>{!! nl2br(e($row['pertanyaan'] ?? '—')) !!}</div>
                                    @if(! empty($row['opsi']))
                                        <ol type="A" class="mb-0 mt-1 small text-muted ps-3">
                                            @foreach($row['opsi'] as $opsi)
                                                <li>{{ $opsi['teks'] ?? '' }}</li>
                                            @endforeach
                                        </ol>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($row['kunci_terlindungi'])
                                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-lock-line me-1"></i>Disembunyikan</span>
                                    @elseif($row['kunci'])
                                        <span class="badge bg-success-subtle text-success">{{ $row['kunci'] }}</span>
                                        @if($row['pembahasan'])
                                            <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($row['pembahasan'], 120) }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center small">{{ $row['level_label'] }}</td>
                                <td class="text-center fw-semibold">{{ number_format($row['skor'], 2) }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6"
                                title="Belum ada butir soal"
                                message="Isi paket dari komposisi kisi-kisi terlebih dahulu." />
                        @endforelse
                    </tbody>
                    @if($rows->count())
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Total Skor</th>
                                <th class="text-center">{{ number_format($totalSkor, 2) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
