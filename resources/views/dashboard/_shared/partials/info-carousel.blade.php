@php
    $carouselId = 'info-carousel-'.substr(md5($user->id), 0, 6);
@endphp

<div class="card mb-3 info-carousel-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="ri-newspaper-line me-1 text-primary"></i>Info Terbaru
        </h5>
        <a href="{{ route('user.announcements.index', ['userId' => $user->id]) }}" class="btn btn-sm btn-light border">
            Lihat semua
        </a>
    </div>
    <div id="{{ $carouselId }}" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="hover">
        <div class="carousel-indicators">
            @foreach($slides as $index => $slide)
                <button type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide-to="{{ $index }}"
                        @class(['active' => $index === 0]) aria-label="Slide {{ $index + 1 }}"></button>
            @endforeach
        </div>
        <div class="carousel-inner">
            @foreach($slides as $index => $slide)
                <div @class(['carousel-item', 'active' => $index === 0])>
                    <a href="{{ $slide['url'] }}" class="d-block info-slide">
                        <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] }}" class="d-block w-100 info-slide-image">
                        <div class="info-slide-caption">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @if($slide['is_pinned'])
                                    <span class="badge bg-warning text-dark"><i class="ri-pushpin-2-line me-1"></i>Penting</span>
                                @endif
                                <span class="badge bg-light text-dark">{{ ucfirst($slide['category']) }}</span>
                                @if($slide['school'])
                                    <span class="badge bg-light text-dark">{{ $slide['school'] }}</span>
                                @endif
                            </div>
                            <h4 class="mb-1 text-white">{{ $slide['title'] }}</h4>
                            <p class="mb-0 text-white-50 small">{{ $slide['excerpt'] }}</p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        @if($slides->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Sebelumnya</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Berikutnya</span>
            </button>
        @endif
    </div>
</div>
