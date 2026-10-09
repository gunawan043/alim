@extends('layouts.master')
@section('title') Paket Soal @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $stats = $statistics ?? ['total' => 0, 'draft' => 0, 'review' => 0, 'final' => 0];
        $statusQuick = request('status');
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Paket Soal @endslot
        @slot('title') Daftar Paket Soal @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-archive-stack-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Paket</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh paket soal sekolah</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-secondary-subtle rounded fs-2"><i class="ri-draft-line text-secondary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Draft</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['draft']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-edit-2-line me-1"></i>Belum diajukan review</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-search-eye-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Menunggu Review</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['review']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-time-line me-1"></i>Termasuk perlu perbaikan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-verified-badge-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Final / Published</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['final']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-check-double-line me-1"></i>Siap cetak / didistribusikan</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="paketSoalList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Paket Soal</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $pakets->total() }} paket</span>
                                <span class="text-muted small ms-2">Paket dibentuk otomatis dari kisi-kisi &amp; bank soal.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <form class="d-flex flex-wrap gap-2 align-items-center">
                                @if(request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                                <select name="jenis_ujian" class="form-select" style="width:180px">
                                    <option value="">Semua Jenis</option>
                                    @foreach(['sts','sas','ulangan_harian','try_out','latihan'] as $j)
                                        <option value="{{ $j }}" {{ request('jenis_ujian') === $j ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $j)) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                                <a href="{{ url()->current() }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="filter-badge {{ ! $statusQuick ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'draft']) }}" class="filter-badge {{ $statusQuick === 'draft' ? 'active' : '' }}"><i class="ri-draft-line"></i> Draft</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'review']) }}" class="filter-badge {{ $statusQuick === 'review' ? 'active' : '' }}"><i class="ri-search-eye-line"></i> Menunggu Review</a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'final']) }}" class="filter-badge {{ $statusQuick === 'final' ? 'active' : '' }}"><i class="ri-verified-badge-line"></i> Final / Published</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Judul</th>
                                <th class="text-center">Kode</th>
                                <th class="text-center">Jenis</th>
                                <th class="text-center">Soal</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width:240px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pakets as $pkt)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.paket-soal.show', $pkt->id) }}" class="fw-medium link-primary">{{ $pkt->judul }}</a>
                                        <br><small class="text-muted">{{ $pkt->kisiKisi->subject->name ?? '-' }} · {{ $pkt->kisiKisi->jenis_ujian ?? '-' }}</small>
                                    </td>
                                    <td class="text-center"><span class="badge bg-light text-dark">{{ $pkt->kode_paket }}</span></td>
                                    <td class="text-center"><span class="badge bg-info-subtle text-info">{{ str_replace('_', ' ', $pkt->kisiKisi->jenis_ujian ?? '') }}</span></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary">{{ $pkt->jumlah_soal_aktual }} soal</span>
                                    </td>
                                    <td class="text-center">
                                        @if($pkt->is_published)
                                            <span class="badge bg-success-subtle text-success">Published</span>
                                        @elseif(in_array($pkt->workflow_status, ['approved', 'published']))
                                            <span class="badge bg-success-subtle text-success">{{ \App\Models\PaketSoal::WORKFLOW_OPTIONS[$pkt->workflow_status] ?? ucfirst($pkt->workflow_status) }}</span>
                                        @elseif($pkt->workflow_status === 'review')
                                            <span class="badge bg-info-subtle text-info">Menunggu Review</span>
                                        @elseif($pkt->workflow_status === 'revisi')
                                            <span class="badge bg-danger-subtle text-danger">Perlu Perbaikan</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(!$pkt->is_published && $pkt->jumlah_soal_aktual > 0)
                                        <form action="{{ route('user.paket-soal.publish', $pkt->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" onclick="return confirm('Publish paket ini?')">
                                                <i class="ri-check-line"></i> Publish
                                            </button>
                                        </form>
                                        @endif
                                        <a href="{{ route('user.paket-soal.reroll', $pkt->id) }}" class="btn btn-sm btn-outline-danger" title="Acak ulang soal">
                                            <i class="ri-refresh-line"></i>
                                        </a>
                                        <form action="{{ route('user.paket-soal.destroy', $pkt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-archive-stack-line fs-1 d-block mb-2"></i>
                                            Belum ada paket soal pada filter ini.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-body pt-0">
                    @include('shared._pagination', ['paginator' => $pakets])
                </div>
            </div>
        </div>
    </div>
@endsection
