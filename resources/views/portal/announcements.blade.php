@extends('layouts.master')
@section('title', 'Pengumuman Wali Santri')
@section('content')
    @component('components.breadcrumb') @slot('title') Pengumuman @endslot <a href="{{ route('portal.dashboard',['token'=>$token]) }}" class="btn btn-sm btn-outline-secondary ms-3"><i class="ri-arrow-left-line me-1"></i>Portal</a> @endcomponent
    <div class="row g-3">
        @forelse($announcements as $announcement)
            <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-body"><div class="d-flex justify-content-between mb-2"><span class="badge bg-info-subtle text-info">{{ ucfirst($announcement->category) }}</span>@if($announcement->is_pinned)<span class="badge bg-warning-subtle text-warning">Penting</span>@endif</div><h5>{{ $announcement->title }}</h5><p class="text-muted small">{{ $announcement->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($announcement->content), 150) }}</p><div class="d-flex justify-content-between align-items-center"><small class="text-muted">{{ $announcement->published_at?->format('d/m/Y') }}</small><a href="{{ route('portal.announcements.show',['token'=>$token,'id'=>$announcement->id]) }}" class="btn btn-sm btn-soft-primary">Baca</a></div></div></div></div>
        @empty<div class="col-12"><div class="card"><div class="card-body text-center py-5 text-muted">Belum ada pengumuman untuk wali santri.</div></div></div>@endforelse
    </div>
    <div class="mt-3">{{ $announcements->links() }}</div>
@endsection
