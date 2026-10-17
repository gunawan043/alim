@props(['bank' => null, 'userId' => null])

@if($bank && !$bank->soal->isEmpty())
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead class="table-light text-muted">
                <tr>
                    <th style="width:60px">#</th>
                    <th>Jenis Soal</th>
                    <th>Pertanyaan (singkat)</th>
                    <th>Bobot</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bank->soal as $i => $soal)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $soal->tipe_soal ?? '-' }}</span></td>
                        <td style="max-width:300px">{{ Str::limit(strip_tags($soal->pertanyaan ?? ''), 60) }}</td>
                        <td>{{ $soal->bobot_default ?? '-' }}</td>
                        <td>
                            @if($soal->status === 'approved')
                                <span class="badge bg-success">Disetujui</span>
                            @elseif($soal->status === 'draft')
                                <span class="badge bg-warning">Draft</span>
                            @else
                                <span class="badge bg-info">{{ $soal->status ?? '-' }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-4">
        <p class="text-muted">Belum ada soal dalam bank ini.</p>
    </div>
@endif
