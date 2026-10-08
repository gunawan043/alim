<div class="card dash-quick">
    <div class="card-header">
        <h4 class="card-title">{{ $data['label'] ?? 'Aksi Cepat' }}</h4>
    </div>
    <div class="card-body">
        <div class="row g-2">
            @forelse ($data['items'] ?? [] as $item)
                <div class="col-xl-6 col-md-6">
                    <a href="{{ $item['url'] }}" class="btn btn-soft-{{ $item['color'] }} w-100 py-3">
                        <i class="{{ $item['icon'] }} fs-4 d-block mb-2"></i>
                        <span class="fw-medium">{{ $item['label'] }}</span>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    @include('dashboard._shared.widgets._empty', [
                        'icon'  => 'ri-links-line',
                        'title' => 'Tidak ada aksi tersedia',
                    ])
                </div>
            @endforelse
        </div>
    </div>
</div>
