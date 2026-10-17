@extends('cetak.layout')

@section('title', 'Pekan Efektif')
@section('doc_title', 'Pekan Efektif')
@section('doc_subtitle')
    Tahun Ajaran {{ $academicYear?->name ?? '-' }} — Semester {{ $semester === 1 ? 'Ganjil' : 'Genap' }}
@endsection

@section('content')
    @php
        $jenisLabels = \App\Models\PekanEfektif::JENIS_OPTIONS;
    @endphp

    <div class="summary-box">
        Minggu Efektif: <strong>{{ $ringkasan['minggu_efektif'] ?? 0 }}</strong> &nbsp;|&nbsp;
        Hari Efektif: <strong>{{ $ringkasan['total_hari_efektif'] ?? 0 }}</strong> &nbsp;|&nbsp;
        Minggu Libur: <strong>{{ $ringkasan['minggu_libur'] ?? 0 }}</strong> &nbsp;|&nbsp;
        Minggu Ujian: <strong>{{ $ringkasan['minggu_ujian'] ?? 0 }}</strong>
        <span class="muted small">(sumber: {{ $ringkasan['sumber'] ?? 'Kalender Pendidikan' }})</span>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:40px">Minggu</th>
                <th style="width:180px">Periode</th>
                <th style="width:120px">Jenis</th>
                <th style="width:90px">Hari Efektif</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($weeks as $week)
                <tr>
                    <td class="center">{{ $week->minggu_ke }}</td>
                    <td class="center">{{ $week->tanggal_mulai?->format('d/m/Y') }} – {{ $week->tanggal_selesai?->format('d/m/Y') }}</td>
                    <td class="center">{{ $jenisLabels[$week->jenis] ?? $week->jenis }}</td>
                    <td class="center">{{ $week->hari_efektif }}</td>
                    <td class="small">{{ $week->keterangan ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center muted">Pekan efektif belum digenerate untuk semester ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td style="width:50%">
                Mengetahui,<br>Kepala Satuan Pendidikan
                <div class="space"></div>
                <div class="name">{{ $school?->principal_name ?? '................................' }}</div>
                @if($school?->principal_nip)<div class="nip">NIP. {{ $school->principal_nip }}</div>@endif
            </td>
            <td style="width:50%">
                {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Wakil Kepala Bidang Kurikulum
                <div class="space"></div>
                <div class="name">................................</div>
            </td>
        </tr>
    </table>
@endsection
