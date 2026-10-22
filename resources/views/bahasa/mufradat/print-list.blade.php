<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Mufradat — {{ $record->title }}</title>
    @include('bahasa._styles')
</head>
<body class="lang-print p-4">
    <div class="text-center mb-3">
        <h4 class="mb-1">DAFTAR MUFRADAT</h4>
        <div>Departemen Bahasa · PAH Mataram</div>
        <div class="muted">Periode: {{ $record->title }} · {{ $record->academicYear?->name ?? '-' }}</div>
    </div>

    <div class="row mb-3" style="font-size:12px">
        <div class="col-6">Mading Sekolah: {{ $record->location_school ?: '__________________________' }}</div>
        <div class="col-6">Mading Asrama: {{ $record->location_dorm ?: '__________________________' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:42px">No</th>
                <th style="width:24%">Kata (Arab)</th>
                <th style="width:24%">Arti (Indonesia)</th>
                <th>Contoh Kalimat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($record->vocabularies as $index => $word)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="direction:rtl;font-weight:700">{{ $word->arabic }}</td>
                    <td>{{ $word->meaning }}</td>
                    <td>{{ $word->example_sentence }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Belum ada mufradat.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="row mt-4" style="font-size:12px">
        <div class="col-6 text-center">
            <div>Mengetahui,</div>
            <div>Kepala Departemen Bahasa</div>
            <div style="height:70px"></div>
            <div>__________________________</div>
        </div>
        <div class="col-6 text-center">
            <div>Mataram, {{ now()->format('d/m/Y') }}</div>
            <div>Qismul Lughah</div>
            <div style="height:70px"></div>
            <div>__________________________</div>
        </div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Cetak</button>
    </div>
</body>
</html>
