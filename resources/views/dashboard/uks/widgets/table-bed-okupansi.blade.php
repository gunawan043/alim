<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Okupansi Bed UKS' }}</h4>
        <span class="badge bg-primary-subtle text-primary">{{ count($data['items'] ?? []) }} bed</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Bed</th>
                        <th>Ruang</th>
                        <th class="text-center">Gender</th>
                        <th>Status</th>
                        <th>Pasien</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->bed }}</td>
                            <td class="fs-13">{{ $item->ruang }}</td>
                            <td class="text-center fs-13">{{ $item->gender }}</td>
                            <td>
                                @php
                                    $badge = match ($item->status) {
                                        'tersedia' => 'success',
                                        'dipakai'  => 'warning',
                                        'perbaikan' => 'danger',
                                        default    => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $item->status }}</span>
                            </td>
                            <td class="fs-13">{{ $item->pasien ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-hotel-bed-line', 'title' => 'Belum ada data bed'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
