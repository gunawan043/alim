@include('user.laporan.pdf._header', [
    'title' => 'Laporan Presensi GTK',
    'filterText' => \Illuminate\Support\Carbon::parse($dari)->format('d/m/Y').' — '.\Illuminate\Support\Carbon::parse($sampai)->format('d/m/Y'),
])

<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th style="width:65px">Tanggal</th>
            <th>GTK</th>
            <th style="width:60px">Status</th>
            <th style="width:55px">Masuk</th>
            <th style="width:55px">Pulang</th>
            <th style="width:55px">Terlambat</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $row->tanggal?->format('d/m/Y') }}</td>
                <td>{{ $row->gtk?->user?->name ?? '-' }}</td>
                <td class="text-center">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                <td class="text-center">{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-' }}</td>
                <td class="text-center">{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-' }}</td>
                <td class="text-center">{{ $row->terlambat_menit > 0 ? $row->terlambat_menit.' mnt' : '-' }}</td>
                <td>{{ \Illuminate\Support\Str::limit((string) $row->keterangan, 80) ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center muted">Tidak ada data pada periode ini.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Dicetak {{ $printedAt->format('d/m/Y H:i') }} · ALIM</div>
