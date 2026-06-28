<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal KBM — {{ $studyGroup->full_name }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #222; margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 6pt; margin-bottom: 12pt; }
        .header h2 { margin: 0; font-size: 13pt; }
        .header p  { margin: 1pt 0; font-size: 10pt; }
        .info { width: 100%; margin-bottom: 8pt; font-size: 9pt; }
        .info td { padding: 1pt 6pt; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #444; padding: 3pt; text-align: center; vertical-align: middle; }
        table.grid th { background: #e8e8e8; font-size: 9pt; }
        table.grid td.slot { font-size: 8pt; color: #555; }
        table.grid td.mapel { font-weight: 600; }
        table.grid td.guru { font-size: 8pt; }
        .footer { margin-top: 10pt; font-size: 8pt; text-align: right; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Jadwal Kegiatan Belajar Mengajar (KBM)</h2>
        <p><strong>{{ $studyGroup->school?->name ?? 'Sekolah' }}</strong></p>
        <p>Tahun Ajaran: {{ $activeAy?->name ?? '-' }}</p>
    </div>

    <table class="info">
        <tr>
            <td><strong>Rombel</strong></td>
            <td>: {{ $studyGroup->full_name }}</td>
            <td><strong>Wali Kelas</strong></td>
            <td>: {{ $studyGroup->homeroomTeacher?->name ?? '-' }}</td>
        </tr>
    </table>

    @php
        $dayLabels = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $allSlots = range(1, 10);
    @endphp

    <table class="grid">
        <thead>
            <tr>
                <th style="width: 50pt;">Slot</th>
                <th style="width: 50pt;">Jam</th>
                @foreach($dayLabels as $d => $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($allSlots as $slot)
                @php
                    $sample = $jadwals->flatten(1)->firstWhere('slot_index', $slot);
                    $start = $sample ? substr($sample->start_time, 0, 5) : '-';
                    $end   = $sample ? substr($sample->end_time, 0, 5) : '-';
                @endphp
                <tr>
                    <td class="slot">{{ $slot }}</td>
                    <td class="slot">{{ $start }}–{{ $end }}</td>
                    @foreach($dayLabels as $d => $label)
                        @php
                            $entry = isset($jadwals[$d]) ? $jadwals[$d]->firstWhere('slot_index', $slot) : null;
                        @endphp
                        <td>
                            @if($entry)
                                <div class="mapel">{{ $entry->subject?->name ?? '-' }}</div>
                                <div class="guru">{{ $entry->teacher?->name ?? '-' }}</div>
                                @if($entry->room)<div class="guru">R: {{ $entry->room }}</div>@endif
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>
</html>
