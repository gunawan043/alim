@include('user.laporan.pdf._header', ['title' => 'Laporan GTK'])

<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th>Nama</th>
            <th>Jabatan</th>
            <th>Jenis GTK</th>
            <th>Status Kepegawaian</th>
            <th>Status Akun</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $person)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $person->name }}</td>
                <td>{{ $person->employment?->jabatan ?: '-' }}</td>
                <td>{{ $person->employment?->jenis_gtk ? ucwords(str_replace('_', ' ', $person->employment->jenis_gtk)) : '-' }}</td>
                <td>{{ $person->employment?->status_kepegawaian ? ucwords(str_replace('_', ' ', $person->employment->status_kepegawaian)) : '-' }}</td>
                <td class="text-center">{{ $person->is_active ? 'Aktif' : 'Nonaktif' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center muted">Tidak ada data GTK.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Dicetak {{ $printedAt->format('d/m/Y H:i') }} · ALIM</div>
