@extends('cetak.layout')

@section('title', 'PROSEM')
@section('doc_title', 'Program Semester (PROSEM)')
@section('doc_subtitle')
    {{ $prosem->subject?->name ?? '-' }} — {{ $prosem->gradeLevel?->name ?? 'Semua Jenjang' }} — {{ $prosem->academicYear?->name ?? '-' }} — Semester {{ ucfirst($prosem->semester) }}
@endsection

@section('content')
    @php
        $monthLabel = function ($date) {
            return $date ? \Illuminate\Support\Carbon::parse($date)->locale('id')->translatedFormat('F Y') : null;
        };
    @endphp

    <table class="info">
        <tr>
            <td class="label">Satuan Pendidikan</td><td class="colon">:</td><td>{{ $school?->name ?? '-' }}</td>
            <td class="label">Mata Pelajaran</td><td class="colon">:</td><td>{{ $prosem->subject?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas / Fase</td><td class="colon">:</td><td>{{ $prosem->gradeLevel?->name ?? 'Semua Jenjang' }}{{ $prosem->gradeLevel?->fase ? ' / '.$prosem->gradeLevel->fase : '' }}</td>
            <td class="label">Guru Pengampu</td><td class="colon">:</td><td>{{ $prosem->teacher?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran</td><td class="colon">:</td><td>{{ $prosem->academicYear?->name ?? '-' }}</td>
            <td class="label">Total JP</td><td class="colon">:</td><td>{{ $totalJp }} JP</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:28px">No</th>
                <th>Tujuan Pembelajaran / Materi</th>
                <th style="width:55px">JP</th>
                <th style="width:170px">Bulan</th>
                <th style="width:110px">Pekan Ke</th>
                <th style="width:170px">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prosem->items as $index => $item)
                @php
                    $startWeek = $weeks[$item->mulai_minggu_ke] ?? null;
                    $endWeek = $weeks[$item->selesai_minggu_ke] ?? null;
                    $startMonth = $monthLabel($startWeek?->tanggal_mulai);
                    $endMonth = $monthLabel($endWeek?->tanggal_selesai);
                    $bulan = $startMonth ? ($endMonth && $endMonth !== $startMonth ? $startMonth.' – '.$endMonth : $startMonth) : '—';
                    $pekan = $item->mulai_minggu_ke > 0
                        ? 'Pekan '.$item->mulai_minggu_ke.($item->selesai_minggu_ke > $item->mulai_minggu_ke ? '–'.$item->selesai_minggu_ke : '')
                        : '—';
                @endphp
                <tr>
                    <td class="center">{{ $item->urutan ?: $index + 1 }}</td>
                    <td>
                        @if($item->tujuanPembelajaran)
                            <strong>{{ $item->tujuanPembelajaran->kode_tp }}</strong> —
                        @endif
                        {{ $item->tujuanPembelajaran?->deskripsi ?? $item->protaItem?->materi ?? '—' }}
                    </td>
                    <td class="center">{{ $item->jp }}</td>
                    <td class="center">{{ $bulan }}</td>
                    <td class="center">{{ $pekan }}</td>
                    <td class="small">{{ $item->keterangan ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center muted">Belum ada distribusi pekan. Sinkronkan PROSEM dari PROTA/Pekan Efektif.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">Total JP Terdistribusi</td>
                <td class="center">{{ $totalJp }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
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
                {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Guru Mata Pelajaran
                <div class="space"></div>
                <div class="name">{{ $prosem->teacher?->name ?? '................................' }}</div>
            </td>
        </tr>
    </table>
@endsection
