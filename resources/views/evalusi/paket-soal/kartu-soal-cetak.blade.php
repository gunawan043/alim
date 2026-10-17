@extends('cetak.layout')

@section('title', 'Kartu Soal')
@section('doc_title', 'KARTU SOAL')
@section('doc_subtitle')
    {{ $paket->kisiKisi?->subject?->name ?? '-' }} &bull;
    Kelas {{ $paket->kisiKisi?->gradeLevel?->name ?? '-' }} &bull;
    TA {{ $paket->kisiKisi?->academicYear?->name ?? '-' }} &bull;
    Semester {{ ucfirst($paket->kisiKisi?->semester ?? '-') }} &bull;
    {{ strtoupper($paket->kisiKisi?->jenis_ujian ?? '-') }}
@endsection

@section('content')
    <div class="summary-box">
        Paket: <strong>{{ $paket->nama ?? '-' }}</strong> &nbsp;|&nbsp;
        Jumlah Butir: <strong>{{ $rows->count() }}</strong> &nbsp;|&nbsp;
        Total Skor: <strong>{{ number_format($totalSkor, 2) }}</strong>
        @if(! $canSeeKeys)
            &nbsp;|&nbsp; <span class="muted small">Kunci jawaban disembunyikan untuk peran ini</span>
        @endif
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:60px">Nomor</th>
                <th style="width:190px">Indikator</th>
                <th>Soal</th>
                <th style="width:150px">Kunci Jawaban</th>
                <th style="width:110px">Level Kognitif</th>
                <th style="width:70px">Skor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="center">{{ $row['nomor'] }}</td>
                    <td class="small">{{ $row['indikator'] ?: '—' }}</td>
                    <td>
                        {!! nl2br(e($row['pertanyaan'] ?? '—')) !!}
                        @if(! empty($row['opsi']))
                            <div class="small">
                                @foreach($row['opsi'] as $i => $opsi)
                                    <div>{{ chr(65 + $i) }}. {{ $opsi['teks'] ?? '' }}</div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="center small">
                        @if($row['kunci_terlindungi'])
                            <em class="muted">Disembunyikan</em>
                        @else
                            {{ $row['kunci'] ?: '—' }}
                        @endif
                    </td>
                    <td class="center small">{{ $row['level_label'] }}</td>
                    <td class="center">{{ number_format($row['skor'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center muted">Belum ada butir soal pada paket ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->count())
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align:right">Total Skor</th>
                    <th class="center">{{ number_format($totalSkor, 2) }}</th>
                </tr>
            </tfoot>
        @endif
    </table>

    <table style="width:100%; margin-top:28px;" class="small">
        <tr>
            <td style="width:50%; text-align:center;">
                Penyusun Soal
                <br><br><br><br>
                <strong>{{ $pembuatNama ?? '................................' }}</strong>
            </td>
            <td style="width:50%; text-align:center;">
                Mengetahui,<br>Tim Kurikulum
                <br><br><br><br>
                <strong>................................</strong>
            </td>
        </tr>
    </table>
@endsection
