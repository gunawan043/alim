@extends('layouts.master')
@section('title', 'Pengumuman')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('personalia._gtk-ui')
    @component('components.breadcrumb') @slot('li_1') Informasi @endslot @slot('title') Pengumuman &amp; Publikasi @endslot @endcomponent
    <div class="row g-3">
        @forelse($announcements as $announcement)
            <div class="col-lg-6"><div class="card card-animate h-100">@if($announcement->images->isNotEmpty())<img src="{{ $announcement->images->first()->url }}" alt="Banner {{ $announcement->title }}" class="card-img-top" style="height:220px;object-fit:cover;">@endif<div class="card-body"><div class="d-flex justify-content-between gap-2 mb-2"><span class="badge bg-info-subtle text-info">{{ ucfirst($announcement->category) }}</span>@if($announcement->is_pinned)<span class="badge bg-warning-subtle text-warning"><i class="ri-pushpin-2-line me-1"></i>Penting</span>@endif</div><h5 class="card-title">{{ $announcement->title }}</h5><p class="text-muted small">{{ $announcement->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($announcement->content), 160) }}</p><div class="d-flex justify-content-between align-items-center"><small class="text-muted">{{ $announcement->published_at?->format('d/m/Y') }}</small><a href="{{ route('user.announcements.show',['userId'=>$userId,'id'=>$announcement->id]) }}" class="btn btn-sm btn-soft-primary">Baca</a></div></div></div></div>
        @empty<div class="col-12"><div class="card"><div class="card-body text-center py-5 text-muted">Belum ada pengumuman.</div></div></div>@endforelse
    </div>
    <div class="mt-3">{{ $announcements->links() }}</div>
@endsection
