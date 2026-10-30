@extends('layouts.master')
@section('title', $announcement->title)
@section('content')
    @php
    $userId = $userId ?? auth()->id();
    $announcementBackRoute = $announcementBackRoute ?? 'user.announcements.index';
    $announcementBackParams = $announcementBackParams ?? ['userId' => $userId];
@endphp
    @include('personalia._gtk-ui')
    <div class="card"><div class="card-body"><div class="d-flex justify-content-between gap-2 mb-3"><span class="badge bg-info-subtle text-info">{{ ucfirst($announcement->category) }}</span><small class="text-muted">{{ $announcement->published_at?->format('d F Y H:i') }}</small></div><h3 class="mb-3">{{ $announcement->title }}</h3>
        @if($announcement->activity_start_date)
            <div class="alert alert-info py-2 mb-3 small">
                <i class="ri-calendar-event-line me-1"></i>
                Tanggal kegiatan: <strong>{{ $announcement->activity_start_date->format('d M Y') }}</strong>
                @if($announcement->activity_end_date && $announcement->activity_end_date->format('Y-m-d') !== $announcement->activity_start_date->format('Y-m-d'))
                    – <strong>{{ $announcement->activity_end_date->format('d M Y') }}</strong>
                @endif
            </div>
        @endif
        @if($announcement->images->isNotEmpty())
            <div id="announcementCarousel" class="carousel slide mb-4" data-bs-ride="carousel" style="max-width:420px;">
                <div class="carousel-inner rounded">
                    @foreach($announcement->images as $image)
                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                            <img src="{{ $image->url }}" class="d-block w-100 rounded" alt="Banner {{ $loop->iteration }}" style="aspect-ratio:4/5;object-fit:cover;">
                        </div>
                    @endforeach
                </div>
                @if($announcement->images->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#announcementCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#announcementCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                    <div class="carousel-indicators">
                        @foreach($announcement->images as $image)
                            <button type="button" data-bs-target="#announcementCarousel" data-bs-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @elseif($announcement->banner_url)
            <img src="{{ $announcement->banner_url }}" alt="Banner" class="img-fluid rounded mb-4" style="max-width:420px;">
        @endif
        <div class="text-muted mb-4">{!! nl2br(e($announcement->content)) !!}</div><a href="{{ route($announcementBackRoute, $announcementBackParams) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Kembali</a></div></div>
@endsection
