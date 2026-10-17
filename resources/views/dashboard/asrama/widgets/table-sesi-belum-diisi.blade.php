<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Sesi Belum Diisi Hari Ini' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="widget-table-scroll">
            <ul class="list-group list-group-flush">
                @forelse ($data['items'] ?? [] as $item)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 fw-medium">{{ $item['session'] }}</p>
                            <small class="text-muted">{{ $item['tanggal'] }}</small>
                        </div>
                        <a href="{{ \Illuminate\Support\Facades\Route::has('user.asrama.index') ? route('user.asrama.index', ['userId' => $user->id]) : '#' }}"
                           class="btn btn-sm btn-soft-primary">Catat</a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0">
                        @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-check-line', 'title' => 'Semua sesi hari ini sudah diisi'])
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
