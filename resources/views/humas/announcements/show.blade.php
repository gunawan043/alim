@extends('layouts.master')
@section('title', $announcement->title)
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('personalia._gtk-ui')
    <div class="card"><div class="card-body"><div class="d-flex justify-content-between gap-2 mb-3"><span class="badge bg-info-subtle text-info">{{ ucfirst($announcement->category) }}</span><small class="text-muted">{{ $announcement->published_at?->format('d F Y H:i') }}</small></div><h3 class="mb-3">{{ $announcement->title }}</h3><div class="text-muted mb-4">{!! nl2br(e($announcement->content)) !!}</div><a href="{{ route('user.announcements.index',['userId'=>$userId]) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Kembali</a></div></div>
@endsection
