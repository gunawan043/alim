@extends('layouts.master')
@section('title') Validasi Soal @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}">Paket Soal</a> @endslot
        @slot('li_2') <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}">{{ $paket->judul ?? $paket->nama ?? 'Paket' }}</a> @endslot
        @slot('title') Validasi Soal @endslot
    @endcomponent

    <x-soal-flow current="cetak" />

    @php
        $jenisLabel = strtoupper(str_replace('_', ' ', (string) ($jenisUjian ?? '-')));
        $semesterLabel = strtoupper((string) ($semester ?? '-'));
        $docTitle = 'VALIDASI SOAL '.$jenisLabel.' '.$semesterLabel.' TA. '.($academicYear?->name ?? '-');
    @endphp

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-1">{{ $docTitle }}</h5>
                <p class="text-muted small mb-0">
                    Dokumen validasi mengikuti penugasan review yang sah — tidak ada status valid tanpa proses.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali ke Paket
                </a>
                <a href="{{ route('user.paket-soal.validasi-soal.cetak', ['userId' => $userId, 'paketUuid' => $paket->id]) }}"
                   target="_blank" class="btn btn-primary btn-sm">
                    <i class="ri-printer-line me-1"></i>Cetak Validasi
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-2 small">
                <div class="col-md-6">
                    <span class="text-muted">MATA PELAJARAN:</span>
                    <span class="fw-semibold">{{ $subject?->name ?? '-' }}</span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted">KELAS:</span>
                    <span class="fw-semibold">{{ $gradeLevel?->name ?? '-' }}</span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <tbody>
                        <tr>
                            <th class="table-light" style="width:230px; vertical-align:top;">Pembuat soal</th>
                            <td class="small" style="min-height:70px;">
                                @forelse($pembuat as $nama)
                                    <div>{{ $loop->iteration }}. {{ $nama }}</div>
                                @empty
                                    <span class="text-muted">Belum tercatat.</span>
                                @endforelse
                            </td>
                        </tr>
                        <tr>
                            <th class="table-light" style="vertical-align:top;">Validator</th>
                            <td class="small">
                                @forelse($perValidator as $v)
                                    <div class="mb-1">
                                        <strong>{{ $v['nama'] }}</strong>
                                        <span class="text-muted">
                                            — {{ $v['amount'] }} penugasan
                                            ({{ $v['approved'] }} setuju, {{ $v['revision'] }} revisi, {{ $v['pending'] }} menunggu)
                                        </span>
                                        @foreach($v['catatan'] as $note)
                                            <div class="text-muted ps-3">&bull; {{ $note }}</div>
                                        @endforeach
                                    </div>
                                @empty
                                    <span class="text-muted">Belum ada penugasan validator (review serumpun).</span>
                                @endforelse
                            </td>
                        </tr>
                        <tr>
                            <th class="table-light" style="vertical-align:top;">Soal yang perlu diperbaiki</th>
                            <td style="min-height:150px;">
                                @forelse($perluDiperbaiki as $row)
                                    <div class="d-flex align-items-start gap-2 mb-2">
                                        <span class="badge bg-warning-subtle text-warning">No. {{ $row['nomor'] }}</span>
                                        <div class="small">
                                            <span class="badge {{ $row['status'] === 'approved' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row['status_label'] }}</span>
                                            <span class="text-muted">oleh {{ $row['validator'] }}</span>
                                            <div>{{ $row['catatan'] ?: '—' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-muted">Tidak ada soal yang ditandai perlu perbaikan.</span>
                                @endforelse
                            </td>
                        </tr>
                        <tr>
                            <th class="table-light" style="vertical-align:top;">Rekomendasi</th>
                            <td style="min-height:180px;">
                                @forelse($rekomendasi as $catatan)
                                    <div class="d-flex gap-2 mb-2">
                                        <i class="ri-checkbox-circle-line text-primary mt-1"></i>
                                        <div class="small">{{ $catatan }}</div>
                                    </div>
                                @empty
                                    <span class="text-muted">Belum ada rekomendasi dari validator.</span>
                                @endforelse
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer border-top-dashed small text-muted">
            Paket: <strong>{{ $paket->judul ?? $paket->nama ?? '-' }}</strong>
            &bull; Jenis Asesmen: {{ ucfirst(str_replace('_', ' ', (string) ($jenisUjian ?? '-'))) }}
            &bull; Semester: {{ ucfirst((string) ($semester ?? '-')) }}
            &bull; TA: {{ $academicYear?->name ?? '-' }}
        </div>
    </div>
@endsection
