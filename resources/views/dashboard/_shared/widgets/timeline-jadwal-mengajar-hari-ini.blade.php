<div class="card card-height-100">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">{{ $data['label'] ?? 'Jadwal Mengajar Hari Ini' }}</h4>
        <a href="{{ \Illuminate\Support\Facades\Route::has('user.teacher-qr.scan') ? route('user.teacher-qr.scan', ['userId' => $user->id]) : '#' }}"
           class="btn btn-sm btn-soft-primary">Scan Presensi</a>
    </div>
    <div class="card-body p-0">
        <div class="widget-table-scroll">
            <ul class="list-group list-group-flush">
                @forelse ($data['items'] ?? [] as $item)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 fw-medium">{{ $item['mapel'] }} — {{ $item['kelas'] }}</p>
                            <small class="text-muted">
                                {{ $item['jam'] }}
                                @if ($item['ruang']) · Ruang {{ $item['ruang'] }} @endif
                            </small>
                        </div>
                        <span class="badge bg-{{ $item['status'] === 'selesai' ? 'success' : ($item['status'] === 'berlangsung' ? 'warning' : 'secondary') }}-subtle text-{{ $item['status'] === 'selesai' ? 'success' : ($item['status'] === 'berlangsung' ? 'warning' : 'secondary') }}">
                            {{ $item['status'] === 'selesai' ? 'Selesai' : ($item['status'] === 'berlangsung' ? 'Berlangsung' : 'Belum diabsen') }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0">
                        @include('dashboard._shared.widgets._empty', ['icon' => 'ri-calendar-line', 'title' => 'Tidak ada jadwal mengajar hari ini'])
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
