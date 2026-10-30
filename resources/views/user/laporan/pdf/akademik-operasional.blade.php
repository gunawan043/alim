@include('user.laporan.pdf._header', [
    'title' => $title,
    'filterText' => $subtitle ?? null,
])

<table style="margin-bottom:8px;">
    <tbody>
        @foreach(array_chunk($summaryRows, 3) as $chunk)
            <tr>
                @foreach($chunk as $summary)
                    <th style="width:14%">{{ $summary[0] ?? '' }}</th>
                    <td>{{ $summary[1] ?? '' }}</td>
                @endforeach
                @for($i = count($chunk); $i < 3; $i++)
                    <th></th><td></td>
                @endfor
            </tr>
        @endforeach
    </tbody>
</table>

<table>
    <thead>
        <tr>
            @foreach($headings as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($headings) }}" class="text-center muted">Belum ada data pada filter ini.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Dicetak {{ $printedAt->format('d/m/Y H:i') }} · {{ $sourceNote ?? 'ALIM' }}
</div>
