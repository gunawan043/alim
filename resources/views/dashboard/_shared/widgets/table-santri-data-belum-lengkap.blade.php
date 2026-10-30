<div class="card card-height-100">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Data Santri Belum Lengkap' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ number_format($data['total'] ?? 0) }} santri</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive widget-table-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-muted fs-13">
                        <th>Nama</th>
                        <th>Field Kosong</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['items'] ?? [] as $item)
                        <tr>
                            <td class="fw-medium">{{ $item['name'] }}</td>
                            <td><span class="badge bg-danger-subtle text-danger">{{ $item['missing'] }}</span></td>
                            <td class="text-end">
                                @if (!empty($item['url']))
                                    <a href="{{ $item['url'] }}" class="btn btn-soft-warning btn-sm">
                                        <i class="ri-edit-box-line me-1"></i>Lengkapi
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="border-0 p-0">
                            @include('dashboard._shared.widgets._empty', ['icon' => 'ri-user-follow-line', 'title' => 'Semua data santri lengkap'])
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
