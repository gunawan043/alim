@extends('layouts.print')
@section('title', 'Waraqat al-Mutaba’ah — '.$student->name)

@section('content')
<style>
    @page { size: A4 landscape; margin: 1cm; }
    body { font-family: 'Times New Roman', serif; font-size: 9px; color: #000; }
    h2 { text-align: center; font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
    h3 { text-align: center; font-size: 11px; margin: 0 0 8px; font-weight: normal; }
    .info { margin-bottom: 6px; font-size: 9.5px; }
    .info span { display: inline-block; margin-right: 18px; }
    table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.grid th, table.grid td { border: .5px solid #000; padding: 1.5px 1px; text-align: center; vertical-align: middle; }
    table.grid th { background: #eee; font-size: 7.5px; }
    table.grid td.label { text-align: left; padding-left: 3px; font-size: 8px; white-space: nowrap; }
    table.grid td.day { font-size: 7.5px; height: 15px; }
    .b { font-weight: bold; }
    .muted { color: #777; }
    .legend { margin-top: 4px; font-size: 7.5px; }
    .summary { margin-top: 6px; width: 100%; border-collapse: collapse; }
    .summary td { border: .5px solid #000; padding: 2px 4px; font-size: 8px; }
    .summary .head { background: #eee; font-weight: bold; text-align: center; }
    .note { margin-top: 5px; font-size: 8.5px; }
    .sign { margin-top: 16px; width: 100%; }
    .sign td { border: 0; text-align: center; font-size: 9px; }
</style>

<h2>Waraqat al-Mutaba’ah</h2>
<h3>{{ $monthLabel }}</h3>

<div class="info">
    <span><strong>Nama:</strong> {{ $student->name }}</span>
    <span><strong>NIS:</strong> {{ $student->nis ?? '-' }}</span>
    <span><strong>Halaqah:</strong> {{ $group->name }}</span>
    <span><strong>Kelas:</strong> {{ $group->studyGroup?->name ?? '-' }}</span>
    <span><strong>Guru/Musyrif:</strong> {{ $group->teacher?->name ?? '-' }}</span>
</div>

@php
    $symbol = fn ($value) => match ($value) {
        'berjamaah' => 'B', 'sendiri' => 'S', 'qadha' => 'Q', 'tidak' => 'T', 'uzur' => 'U', default => '·',
    };
    $check = fn ($value) => $value ? '✓' : '·';
    $sholat = [
        'sholat_subuh' => 'Subuh', 'sholat_dzuhur' => 'Dzuhur', 'sholat_ashar' => 'Ashar',
        'sholat_maghrib' => 'Maghrib', 'sholat_isya' => 'Isya',
    ];
@endphp

<table class="grid">
    <thead>
        <tr>
            <th style="width:105px">Indikator</th>
            @for($day = 1; $day <= $days; $day++)
                <th>{{ $day }}</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($sholat as $field => $label)
            <tr>
                <td class="label">Sholat {{ $label }}</td>
                @for($day = 1; $day <= $days; $day++)
                    @php $record = $records[$day] ?? null; @endphp
                    <td class="day {{ ($record?->{$field} ?? null) === 'berjamaah' ? 'b' : '' }}">{{ $symbol($record?->{$field} ?? null) }}</td>
                @endfor
            </tr>
        @endforeach
        @foreach([
            'sholat_tahajud' => 'Tahajud', 'sholat_dhuha' => 'Dhuha', 'puasa_sunnah' => 'Puasa Sunnah',
            'sedekah' => 'Sedekah', 'wirid_pagi' => 'Wirid Pagi', 'wirid_sore' => 'Wirid Sore',
        ] as $field => $label)
            <tr>
                <td class="label">{{ $label }}</td>
                @for($day = 1; $day <= $days; $day++)
                    @php $record = $records[$day] ?? null; @endphp
                    <td class="day">{{ $check($record?->{$field} ?? 0) }}</td>
                @endfor
            </tr>
        @endforeach
        <tr>
            <td class="label">Tilawah (halaman)</td>
            @for($day = 1; $day <= $days; $day++)
                @php $record = $records[$day] ?? null; @endphp
                <td class="day">{{ $record?->tilawah_halaman ?: '·' }}</td>
            @endfor
        </tr>
        <tr>
            <td class="label">Tikror (halaman)</td>
            @for($day = 1; $day <= $days; $day++)
                @php $record = $records[$day] ?? null; @endphp
                <td class="day">{{ $record?->tikror_mandiri_halaman ?: '·' }}</td>
            @endfor
        </tr>
    </tbody>
</table>

<div class="legend">
    Keterangan sholat: <strong>B</strong> = Berjamaah, <strong>S</strong> = Sendiri, <strong>Q</strong> = Qadha, <strong>T</strong> = Tidak, <strong>U</strong> = Uzur, <strong>·</strong> = belum diisi.
</div>

@php
    $all = $records->values();
    $jamaahCount = fn ($field) => $all->where($field, 'berjamaah')->count();
    $hadirCount = fn ($field) => $all->where($field, 'berjamaah')->count() + $all->where($field, 'sendiri')->count();
@endphp
<table class="summary">
    <tr class="head"><td>Ringkasan</td><td>Subuh</td><td>Dzuhur</td><td>Ashar</td><td>Maghrib</td><td>Isya</td><td>Tahajud</td><td>Dhuha</td><td>Puasa</td><td>Sedekah</td><td>Tilawah</td><td>Tikror</td></tr>
    <tr>
        <td class="head">Jamaah</td>
        @foreach(array_keys($sholat) as $field)<td>{{ $jamaahCount($field) }}</td>@endforeach
        <td>{{ $all->where('sholat_tahajud', 1)->count() }}</td>
        <td>{{ $all->where('sholat_dhuha', 1)->count() }}</td>
        <td>{{ $all->where('puasa_sunnah', 1)->count() }}</td>
        <td>{{ $all->where('sedekah', 1)->count() }}</td>
        <td>{{ $all->sum('tilawah_halaman') }}</td>
        <td>{{ $all->sum('tikror_mandiri_halaman') }}</td>
    </tr>
    <tr>
        <td class="head">Terisi / Hadir</td>
        @foreach(array_keys($sholat) as $field)<td>{{ $hadirCount($field) }}</td>@endforeach
        <td colspan="7" class="muted">Jumlah hari terisi: {{ $all->count() }} dari {{ $days }} hari</td>
    </tr>
</table>

@php $lastRecord = $all->sortByDesc('record_date')->first(); @endphp
@if($lastRecord?->catatan_musyrif)
    <div class="note"><strong>Catatan musyrif terakhir:</strong> {{ $lastRecord->catatan_musyrif }}</div>
@endif

<table class="sign">
    <tr>
        <td style="width:50%">Mengetahui,<br>Koordinator Tahfidz<br><br><br><br>________________________</td>
        <td style="width:50%">Musyrif / Guru Halaqah<br><br><br><br>________________________</td>
    </tr>
</table>
@endsection
