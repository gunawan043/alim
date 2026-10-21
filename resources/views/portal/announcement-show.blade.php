@extends('layouts.master')
@section('title', $announcement->title)
@section('content')
    @component('components.breadcrumb') @slot('title') Pengumuman @endslot <a href="{{ route('portal.announcements',['token'=>$token]) }}" class="btn btn-sm btn-outline-secondary ms-3"><i class="ri-arrow-left-line me-1"></i>Kembali</a> @endcomponent
    <div class="card"><div class="card-body"><div class="d-flex justify-content-between mb-3"><span class="badge bg-info-subtle text-info">{{ ucfirst($announcement->category) }}</span><small class="text-muted">{{ $announcement->published_at?->format('d F Y H:i') }}</small></div><h3>{{ $announcement->title }}</h3><div class="text-muted mt-4">{!! nl2br(e($announcement->content)) !!}</div></div></div>
@endsection
