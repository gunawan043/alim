<div class="welcome-banner mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative">
        <div class="flex-grow-1">
            <span class="banner-eyebrow">
                <i class="ri-shield-star-line me-1"></i>{{ $data['jabatan'] ?? 'Dashboard' }}
            </span>
            <h3 class="banner-title">
                {{ $data['greeting'] ?? 'Assalamualaikum' }}, {{ $data['name'] ?? 'Pengguna' }}
            </h3>
            <div class="banner-meta">
                @if (! empty($data['school']))
                    <span><i class="ri-building-4-line me-1"></i>{{ $data['school'] }}</span>
                    <span class="banner-dot">•</span>
                @endif
                <span><i class="ri-calendar-line me-1"></i>{{ $data['date'] ?? '' }}</span>
            </div>
            @if (! empty($data['tasks']))
                <div class="mt-2 d-flex flex-wrap gap-1">
                    @foreach ($data['tasks'] as $task)
                        <span class="banner-chip"><i class="ri-award-line me-1"></i>{{ $task }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="banner-icon-wrap">
            <i class="ri-dashboard-3-line"></i>
        </div>
    </div>
</div>
