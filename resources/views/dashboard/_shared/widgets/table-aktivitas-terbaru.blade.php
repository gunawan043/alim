<div class="card card-height-100">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Aktivitas Terbaru' }}</h4>
        <span class="badge bg-primary-subtle text-primary">10 terakhir</span>
    </div>
    <div class="card-body p-0">
        <div class="widget-table-scroll">
            <ul class="list-group list-group-flush">
                @forelse ($data['logs'] ?? [] as $log)
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="mb-1 fs-14">{{ $log['description'] }}</p>
                            <small class="text-muted">{{ $log['log_name'] }}</small>
                        </div>
                        <small class="text-muted text-end ms-2">
                            {{ $log['created_at'] ? \Carbon\Carbon::parse($log['created_at'])->diffForHumans() : '—' }}
                        </small>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0">
                        @include('dashboard._shared.widgets._empty', ['icon' => 'ri-history-line', 'title' => 'Belum ada aktivitas'])
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
