@extends('cetak.layout')

@php
    $jenisLabel = strtoupper(str_replace('_', ' ', (string) ($jenisUjian ?? '-')));
    $semesterLabel = strtoupper((string) ($semester ?? '-'));
@endphp

@section('title', 'Validasi Soal')
@section('doc_title', 'VALIDASI SOAL '.$jenisLabel.' '.$semesterLabel.' TA. '.($academicYear?->name ?? '-'))
@section('doc_subtitle')
    Paket: {{ $paket->judul ?? $paket->nama ?? '-' }}
@endsection

@section('content')
    <div class="summary-box">
        MATA PELAJARAN: <strong>{{ $subject?->name ?? '-' }}</strong>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        KELAS: <strong>{{ $gradeLevel?->name ?? '-' }}</strong>
    </div>

    <table class="data">
        <tbody>
            <tr>
                <th style="width:28%; text-align:left;">Pembuat soal</th>
                <td style="height:70px;">
                    @forelse($pembuat as $nama)
                        <div>{{ $loop->iteration }}. {{ $nama }}</div>
                    @empty
                        <span class="muted">Belum tercatat.</span>
                    @endforelse
                </td>
            </tr>
            <tr>
                <th style="text-align:left;">Validator</th>
                <td style="height:90px;">
                    @forelse($perValidator as $v)
                        <div>
                            {{ $v['nama'] }} —
                            {{ $v['amount'] }} penugasan
                            ({{ $v['approved'] }} setuju, {{ $v['revision'] }} revisi, {{ $v['pending'] }} menunggu)
                        </div>
                        @foreach($v['catatan'] as $note)
                            <div class="muted">&bull; {{ $note }}</div>
                        @endforeach
                    @empty
                        <span class="muted">Belum ada penugasan validator.</span>
                    @endforelse
                </td>
            </tr>
            <tr>
                <th style="text-align:left;">Soal yang perlu diperbaiki</th>
                <td style="height:150px;">
                    @forelse($perluDiperbaiki as $row)
                        <div class="mb-1">
                            <strong>No. {{ $row['nomor'] }}</strong>
                            [{{ $row['status_label'] }} oleh {{ $row['validator'] }}]
                            — {{ $row['catatan'] ?: '—' }}
                        </div>
                    @empty
                        <span class="muted">Tidak ada soal yang ditandai perlu perbaikan.</span>
                    @endforelse
                </td>
            </tr>
            <tr>
                <th style="text-align:left;">Rekomendasi</th>
                <td style="height:180px;">
                    @forelse($rekomendasi as $catatan)
                        <div>&bull; {{ $catatan }}</div>
                    @empty
                        <span class="muted">Belum ada rekomendasi dari validator.</span>
                    @endforelse
                </td>
            </tr>
        </tbody>
    </table>

    <table style="width:100%; margin-top:28px;" class="small">
        <tr>
            <td style="width:33%; text-align:center;">
                Pembuat Soal
                <br><br><br><br>
                <strong>{{ $pembuat->first() ?? '................................' }}</strong>
            </td>
            <td style="width:33%; text-align:center;">
                Validator
                <br><br><br><br>
                <strong>{{ $perValidator->first()['nama'] ?? '................................' }}</strong>
            </td>
            <td style="width:33%; text-align:center;">
                Mengetahui,<br>Tim Kurikulum
                <br><br><br><br>
                <strong>................................</strong>
            </td>
        </tr>
    </table>
@endsection
