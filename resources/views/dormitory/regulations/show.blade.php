@extends('layouts.master')

@section('title', 'Detail Peraturan: ' . $regulation->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0"><i class="ri-book-line me-2"></i>Detail Peraturan Asrama</h4>
                <div>
                    <a href="{{ route('user.boarding-regulations.edit', $regulation->id) }}" class="btn btn-warning btn-lg me-2">
                        <i class="ri-edit-line me-1"></i>Sunting
                    </a>
                    <a href="{{ route('user.boarding-regulations.index') }}" class="btn btn-secondary btn-lg">
                        <i class="ri-arrow-left-circle-line me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Peraturan Details Card --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3 fw-bold">{{ $regulation->name }}</h5>
                    <p class="text-muted mb-4">{{ Str::limit($regulation->description, 200) }}</p>

                    <div class="border-bottom py-3 mb-3">
                        <h6 class="fw-semibold mb-2"><i class="ri-category-fill text-primary me-2"></i>Kategori</h6>
                        <span class="badge bg-info-subtle text-info">{{ $regulation->category ? $regulation->category->name : 'Unknown' }}</span>
                    </div>

                    <div class="py-3 mb-3">
                        <h6 class="fw-semibold mb-2"><i class="ri-file-text-fill text-secondary me-2"></i>Konten Peraturan</h6>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-0">{{ nl2br($regulation->content) }}</p>
                        </div>
                    </div>

                    <div class="py-3">
                        <h6 class="fw-semibold mb-2"><i class="ri-status-fill text-success me-2"></i>Status</h6>
                        @if($regulation->is_active)
                            <span class="badge bg-success-subtle text-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Arsip</span>
                        @endif
                        <small class="text-muted ms-2">({{ $regulation->created_at->format('d F Y H:i') }})</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
