@php
    $items = collect($data['items'] ?? []);

    // Ambil kolom dari item pertama, buang kolom teknis
    $columns = [];
    if ($items->isNotEmpty()) {
        $first = (array) $items->first();
        foreach (array_keys($first) as $key) {
            if (in_array($key, ['id', 'uuid'], true) || str_ends_with($key, '_id')) {
                continue;
            }
            $value = $first[$key];
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $columns[$key] = \Illuminate\Support\Str::headline($key);
        }
    }

    if (empty($columns)) {
        $columns = ['_empty' => 'Data'];
    }

    $badgeColor = function ($value): string {
        $v = strtolower((string) $value);
        return match (true) {
            str_contains($v, 'selesai'), str_contains($v, 'lulus'), str_contains($v, 'baik'),
            str_contains($v, 'aktif'), str_contains($v, 'approved'), str_contains($v, 'selesai'),
            str_contains($v, 'tersedia'), str_contains($v, 'hadir'), str_contains($v, 'completed'),
            str_contains($v, 'passed'), str_contains($v, 'closed'), str_contains($v, 'lunas') => 'success',
            str_contains($v, 'pending'), str_contains($v, 'proses'), str_contains($v, 'dipinjam'),
            str_contains($v, 'sedang'), str_contains($v, 'review'), str_contains($v, 'menunggu'),
            str_contains($v, 'progress'), str_contains($v, 'sakit'), str_contains($v, 'sebagian'),
            str_contains($v, 'perawatan') => 'warning',
            str_contains($v, 'rusak'), str_contains($v, 'berat'), str_contains($v, 'hilang'),
            str_contains($v, 'overdue'), str_contains($v, 'breached'), str_contains($v, 'alpa'),
            str_contains($v, 'rejected'), str_contains($v, 'gagal'), str_contains($v, 'batal'),
            str_contains($v, 'ditolak'), str_contains($v, 'belum') => 'danger',
            str_contains($v, 'dijadwalkan'), str_contains($v, 'scheduled'), str_contains($v, 'terjadwal'),
            str_contains($v, 'assigned'), str_contains($v, 'baru'), str_contains($v, 'digunakan') => 'info',
            default => 'secondary',
        };
    };

    $format = function ($key, $value) use ($badgeColor) {
        if ($value === null || $value === '') {
            return '—';
        }

        $k = strtolower($key);

        if (str_contains($k, 'status') || str_contains($k, 'kondisi') || str_contains($k, 'condition')
            || str_contains($k, 'level') || str_contains($k, 'hasil') || str_contains($k, 'prioritas')
            || str_contains($k, 'priority') || str_contains($k, 'is_passed') || str_contains($k, 'jenis_kelamin')) {
            $label = is_bool($value) ? ($value ? 'Ya' : 'Tidak') : ucwords(str_replace(['_', '-'], ' ', (string) $value));
            $color = $badgeColor((string) $value);

            return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . e($label) . '</span>';
        }

        if (str_contains($k, 'cost') || str_contains($k, 'nilai') || str_contains($k, 'harga')
            || str_contains($k, 'price') || str_contains($k, 'amount') || str_contains($k, 'total')) {
            if (is_numeric($value)) {
                return 'Rp ' . number_format((float) $value, 0, ',', '.');
            }
        }

        if (str_contains($k, 'tanggal') || str_contains($k, 'date') || str_ends_with($k, '_at')) {
            try {
                return \Carbon\Carbon::parse($value)->translatedFormat('d M Y');
            } catch (\Throwable) {
                // fallback ke teks
            }
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        return e(\Illuminate\Support\Str::limit((string) $value, 45));
    };
@endphp

<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Data' }}</h4>
        <span class="badge bg-primary-subtle text-primary">{{ $items->count() }} baris</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        @foreach ($columns as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php $row = (array) $item; @endphp
                        <tr>
                            @foreach (array_keys($columns) as $key)
                                <td class="fs-13">{!! $format($key, $row[$key] ?? null) !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-inbox-archive-line', 'title' => $data['empty_title'] ?? 'Belum ada data'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
