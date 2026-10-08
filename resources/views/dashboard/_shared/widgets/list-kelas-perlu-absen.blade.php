<div class="card card-height-100">
    <div class="card-header d-flex align-items-center">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Kelas Perlu Diabsen' }}</h4>
        <span class="badge bg-warning-subtle text-warning">{{ count($data['items'] ?? []) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="widget-table-scroll">
            <ul class="list-group list-group-flush">
                @forelse ($data['items'] ?? [] as $item)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 fw-medium">{{ $item['kelas'] }}</p>
                            <small class="text-muted">{{ $item['mapel'] }} · {{ $item['jam'] }}</small>
                        </div>
                        <a href="{{ \Illuminate\Support\Facades\Route::has('user.absensi.harian.create') ? route('user.absensi.harian.create', ['userId' => $user->id]) : '#' }}"
                           class="btn btn-sm btn-soft-primary">Absen</a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0">
                        @include('dashboard._shared.widgets._empty', ['icon' => 'ri-checkbox-circle-line', 'title' => 'Semua kelas sudah diabsen'])
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
