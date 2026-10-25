@extends('cetak.layout')

@section('title', 'PROGRAM SEMESTER')
@section('doc_title', 'PROGRAM SEMESTER')
@section('doc_subtitle', '')

@section('content')
    @php
        $weekCollection = collect($weeks ?? [])->sortBy('minggu_ke')->values();
        $monthGroups = $weekCollection
            ->groupBy(fn ($week) => $week->tanggal_mulai?->locale('id')->translatedFormat('F Y') ?? 'Bulan')
            ->map(fn ($group, $label) => ['label' => $label, 'weeks' => $group->values()]);
        $totalColumns = 3 + $weekCollection->count();
    @endphp

    <table class="info">
        <tr>
            <td class="label">Mata Pelajaran</td><td class="colon">:</td><td>{{ $prosem->subject?->name ?? '-' }}</td>
            <td class="label">Semester</td><td class="colon">:</td><td>{{ ucfirst($prosem->semester) }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td><td class="colon">:</td><td>{{ $prosem->gradeLevel?->name ?? 'Semua Jenjang' }}</td>
            <td class="label">Tahun Ajaran</td><td class="colon">:</td><td>{{ $prosem->academicYear?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Satuan Pendidikan</td><td class="colon">:</td><td colspan="3">{{ $school?->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th rowspan="2" style="width:30px">No</th>
                <th rowspan="2">CP / TP / Materi</th>
                <th rowspan="2" style="width:70px">Alokasi Waktu</th>
                @foreach($monthGroups as $month)
                    <th colspan="{{ $month['weeks']->count() }}">{{ $month['label'] }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($monthGroups as $month)
                    @foreach($month['weeks'] as $week)
                        <th style="width:20px;text-align:center">{{ $week->minggu_ke }}</th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($prosem->items as $index => $item)
                @php
                    $jpByWeek = ($item->weeks ?? collect())->pluck('jp', 'pekan_ke')->all();
                @endphp
                <tr>
                    <td class="center">{{ $item->urutan ?: $index + 1 }}</td>
                    <td>
                        @if($item->tujuanPembelajaran)
                            <strong>{{ $item->tujuanPembelajaran->kode_tp }}</strong> —
                        @endif
                        {{ $item->tujuanPembelajaran?->deskripsi ?? $item->protaItem?->materi ?? '—' }}
                    </td>
                    <td class="center">{{ $item->jp }} JP</td>
                    @foreach($monthGroups as $month)
                        @foreach($month['weeks'] as $week)
                            <td class="center">{{ $jpByWeek[$week->minggu_ke] ?? '' }}</td>
                        @endforeach
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(1, $totalColumns) }}" class="center muted">Belum ada distribusi PROSEM.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">JUMLAH</td>
                <td class="center">{{ $totalJp }} JP</td>
                <td colspan="{{ max(0, $weekCollection->count()) }}"></td>
            </tr>
        </tfoot>
    </table>

    <p class="small">Catatan: pekan efektif mengikuti perhitungan Kalender Pendidikan.</p>

    <table class="sign">
        <tr>
            <td style="width:50%">
                Mengetahui,<br>Kepala Satuan Pendidikan
                <div class="space"></div>
                <div class="name">{{ $school?->principal_name ?? '................................' }}</div>
                @if($school?->principal_nip)<div class="nip">NIP. {{ $school->principal_nip }}</div>@endif
            </td>
            <td style="width:50%">
                {{ $school?->city?->name ?? 'Mataram' }}, {{ now()->locale('id')->translatedFormat('d F Y') }}<br>
                Guru Mata Pelajaran
                <div class="space"></div>
                <div class="name">{{ $prosem->teacher?->name ?? '................................' }}</div>
            </td>
        </tr>
    </table>
@endsection
