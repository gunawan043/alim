@include('user.laporan.pdf._header', ['title' => 'Laporan Santri'])

<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th>NISN</th>
            <th>NIS</th>
            <th>Nama</th>
            <th style="width:35px">JK</th>
            <th>Rombel</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $student)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $student->nisn ?: '-' }}</td>
                <td>{{ $student->nis ?: '-' }}</td>
                <td>{{ $student->name }}</td>
                <td class="text-center">{{ $student->gender }}</td>
                <td>{{ $student->currentClassHistory?->studyGroup?->name ?? '-' }}</td>
                <td class="text-center">{{ ucfirst($student->status) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center muted">Tidak ada data santri.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Dicetak {{ $printedAt->format('d/m/Y H:i') }} · ALIM</div>
