<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Halaqah Aktif' }}</h4>
        <span class="badge bg-success-subtle text-success">{{ count($data['items'] ?? []) }} halaqah</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Halaqah</th>
                        <th>Musyrif</th>
                        <th>Koordinator</th>
                        <th class="text-center">Anggota</th>
                        <th class="text-center">Gender</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">
                                {{ $item->name }}
                                @if ($item->room)
                                    <br><small class="text-muted">{{ $item->room }}</small>
                                @endif
                            </td>
                            <td class="fs-13">{{ $item->musyrif ?? '—' }}</td>
                            <td class="fs-13">{{ $item->koordinator ?? '—' }}</td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $item->anggota }}/{{ $item->max_members ?? '∞' }}</span></td>
                            <td class="text-center fs-13">{{ ucfirst($item->gender ?? '—') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-group-line', 'title' => 'Belum ada halaqah aktif'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
