@extends('cetak.layout')

@section('title', 'Kalender Pendidikan')
@section('doc_title', 'Kalender Pendidikan')
@section('doc_subtitle')
    Tahun Ajaran {{ $academicYear?->name ?? '-' }}
@endsection

@section('content')
    @php
        $categoryLabels = \App\Models\Kaldik::CATEGORY_OPTIONS;
        $typeLabels = \App\Models\Kaldik::TYPE_OPTIONS;
        $semesterLabels = \App\Models\Kaldik::SEMESTER_OPTIONS;
    @endphp

    <table class="data">
        <thead>
            <tr>
                <th style="width:26px">No</th>
                <th style="width:130px">Tanggal</th>
                <th>Nama Kegiatan</th>
                <th style="width:90px">Kategori</th>
                <th style="width:90px">Tipe</th>
                <th style="width:60px">Semester</th>
                <th style="width:110px">Satuan Kerja</th>
                <th style="width:120px">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $index => $event)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">
                        {{ $event->start_date?->format('d/m/Y') }}
                        @if($event->end_date && $event->end_date->ne($event->start_date))
                            – {{ $event->end_date->format('d/m/Y') }}
                        @endif
                    </td>
                    <td>{{ $event->name }}</td>
                    <td class="center">{{ $categoryLabels[$event->category] ?? $event->category }}</td>
                    <td class="center">{{ $event->type ? ($typeLabels[$event->type] ?? $event->type) : '—' }}</td>
                    <td class="center">{{ $event->semester ? ($semesterLabels[$event->semester] ?? $event->semester) : '—' }}</td>
                    <td class="center">{{ $event->workUnit?->name ?? 'Pondok (Semua)' }}</td>
                    <td class="small">{{ $event->description ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center muted">Belum ada data kalender pada tahun ajaran ini.</td></tr>
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
                {{ $school?->address ? $school->address : '' }}<br>
                {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Penyusun Kurikulum
                <div class="space"></div>
                <div class="name">................................</div>
            </td>
        </tr>
    </table>
@endsection
