<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Kegiatan Hari Ini' }}</h4>
        <span class="badge bg-info-subtle text-info">{{ now()->translatedFormat('d M Y') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Sesi</th>
                        <th class="text-center">Item Kegiatan</th>
                        <th class="text-center">Tercatat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->session }}</td>
                            <td class="text-center">{{ $item->kegiatan }}</td>
                            <td class="text-center">{{ $item->tercatat }}</td>
                            <td>
                                @if ($item->tercatat > 0)
                                    <span class="badge bg-success-subtle text-success">tercatat</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">belum</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-todo-line', 'title' => 'Belum ada template kegiatan'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
