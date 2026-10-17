@include('user.laporan.pdf._header', ['title' => 'Laporan Asrama'])

<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th>Nama Asrama</th>
            <th class="text-center">Kamar</th>
            <th class="text-center">Penghuni</th>
            <th class="text-center">Kapasitas</th>
            <th class="text-center">Sisa</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $dorm)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $dorm->name }}</td>
                <td class="text-center">{{ $dorm->rooms_count }}</td>
                <td class="text-center">{{ $dorm->active_residents_count }}</td>
                <td class="text-center">{{ $dorm->capacity }}</td>
                <td class="text-center">{{ max(0, (int) $dorm->capacity - (int) $dorm->active_residents_count) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center muted">Tidak ada data asrama.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Dicetak {{ $printedAt->format('d/m/Y H:i') }} · ALIM</div>
