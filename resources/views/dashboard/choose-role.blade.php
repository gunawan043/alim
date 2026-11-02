@extends('layouts.master')
@section('title', 'Pilih Dashboard')
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Dashboard @endslot
        @slot('title') Pilih Dashboard @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="mb-1">Anda memegang dua jabatan kepala</h4>
                    <p class="text-muted mb-0">
                        Pilih area kerja yang ingin dibuka. Menu Asrama dan Satuan Pendidikan tetap tersedia di sidebar,
                        jadi Anda tidak perlu login ulang untuk berpindah.
                    </p>
                </div>
            </div>

            <div class="row g-3">
                @if($dormitories->isNotEmpty())
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header border-bottom-dashed">
                                <h5 class="card-title mb-0"><i class="ri-home-heart-line me-1 text-primary"></i>Kepala Asrama</h5>
                                <small class="text-muted">Operasional pengasuhan dan asrama</small>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    @foreach($dormitories as $dormitory)
                                        <a href="{{ route('user.dashboard.asrama', ['userId' => $userId]) }}"
                                           class="btn btn-light text-start border">
                                            <i class="ri-hotel-line me-1 text-primary"></i>
                                            <strong>{{ $dormitory->name }}</strong>
                                            <span class="d-block small text-muted">{{ $dormitory->code }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($schools->isNotEmpty())
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header border-bottom-dashed">
                                <h5 class="card-title mb-0"><i class="ri-building-4-line me-1 text-success"></i>Kepala Satuan Pendidikan</h5>
                                <small class="text-muted">Akademik, GTK, dan manajemen sekolah</small>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    @foreach($schools as $school)
                                        <a href="{{ route('user.dashboard.satuan-pendidikan', ['userId' => $userId]) }}"
                                           class="btn btn-light text-start border">
                                            <i class="ri-school-line me-1 text-success"></i>
                                            <strong>{{ $school->name }}</strong>
                                            <span class="d-block small text-muted">{{ $school->npsn ?? $school->school_code }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="alert alert-info mt-3 mb-0 small">
                <i class="ri-information-line me-1"></i>
                Penetapan Kepala Asrama dan Kepala Satuan Pendidikan tetap hanya dapat dilakukan oleh
                <strong>Personalia</strong> atau <strong>Super Admin</strong>.
            </div>
        </div>
    </div>
@endsection
