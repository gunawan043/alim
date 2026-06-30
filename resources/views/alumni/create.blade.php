@extends('layouts.master')
@section('title') Data Alumni @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Data Alumni @endslot
        @slot('title') Sinkronisasi Alumni @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Sinkronisasi Data Alumni</h5>
                            <p class="text-muted mb-0">Sync data alumni dari santri yang sudah lulus ke daftar alumni.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        Proses ini akan membuat record alumni untuk setiap santri dengan status <strong>lulus</strong> yang belum memiliki record alumni.
                        Record yang sudah ada akan dilewati.
                    </div>

                    <form method="POST" action="{{ route('user.alumni.sync', ['userId' => $userId]) }}">
                        @csrf
                        <div class="text-center py-4">
                            <img src="https://cdn.lordicon.com/msoeawqm.json" class="d-none" alt="">
                            <lord-icon src="https://cdn.lordicon.com/wbpvmfzo.json" trigger="loop"
                                       colors="primary:#0ab39c,secondary:#405189"
                                       style="width:120px;height:120px">
                            </lord-icon>
                            <h5 class="mt-3">Mulai Sinkronisasi</h5>
                            <p class="text-muted">Klik tombol di bawah untuk memulai proses sinkronisasi data alumni.</p>
                            <button type="submit" class="btn btn-primary btn-lg mt-2">
                                <i class="ri-sync-line me-1"></i> Mulai Sinkronisasi
                            </button>
                        </div>
                    </form>

                    <hr>

                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="avatar-sm mx-auto mb-3 bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px">
                                        <i class="ri-user-follow-line text-primary fs-4"></i>
                                    </div>
                                    <h6 class="mb-1">Santri Lulus</h6>
                                    <p class="text-muted small mb-0">Data diambil dari santri dengan status lulus</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="avatar-sm mx-auto mb-3 bg-success-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px">
                                        <i class="ri-git-commit-line text-success fs-4"></i>
                                    </div>
                                    <h6 class="mb-1">Dibuat Otomatis</h6>
                                    <p class="text-muted small mb-0">Record alumni baru dibuat dengan status tracer pending</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="avatar-sm mx-auto mb-3 bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px">
                                        <i class="ri-double-quotes-l text-warning fs-4"></i>
                                    </div>
                                    <h6 class="mb-1">Duplicate Dilindungi</h6>
                                    <p class="text-muted small mb-0">Record yang sudah ada tidak akan duplikasi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('user.alumni.index', ['userId' => $userId]) }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Kembali ke Daftar Alumni
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
