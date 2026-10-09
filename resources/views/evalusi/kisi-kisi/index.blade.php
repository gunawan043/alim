@extends('layouts.master')
@section('title') Kisi-kisi @endsection

@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php
        $stats = $statistics ?? ['total' => 0, 'aktif' => 0, 'tahun_ajaran_ini' => 0, 'jenis_ujian' => 0];
        $statusAktif = request()->filled('is_active') ? (bool) request('is_active') : null;
        $jenisLabels = [
            'sts' => 'STS',
            'sas' => 'SAS',
            'ulangan_harian' => 'Ulangan Harian',
            'try_out' => 'Try Out',
            'latihan' => 'Latihan',
        ];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') Kisi-kisi @endslot
        @slot('title') Daftar Kisi-kisi @endslot
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
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-list-3-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Kisi-kisi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh kisi-kisi satuan pendidikan</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-checkbox-circle-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['aktif']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Siap dipakai menyusun paket soal</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-calendar-check-line text-info"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Tahun Ajaran Ini</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['tahun_ajaran_ini']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <i class="ri-calendar-2-line me-1"></i>{{ $activeYear->name ?? 'Belum ada TA aktif' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-stack-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Jenis Ujian</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['jenis_ujian']) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>STS, SAS, ulangan harian, dll.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="kisiKisiList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Kisi-kisi</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $kisis->total() }} kisi-kisi</span>
                                <span class="text-muted small ms-2">Klik judul untuk melihat rincian butir soal.</span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <form class="d-flex flex-wrap gap-2 align-items-center">
                                <select name="subject_id" class="form-select" style="width:200px">
                                    <option value="">Semua Mapel</option>
                                    @foreach($subjects as $sub)
                                        <option value="{{ $sub->id }}" {{ (string) request('subject_id') === (string) $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                    @endforeach
                                </select>
                                <select name="semester" class="form-select" style="width:130px">
                                    <option value="">Semua Semester</option>
                                    <option value="ganjil" {{ request('semester') === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ request('semester') === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                                @if(request('jenis_ujian'))<input type="hidden" name="jenis_ujian" value="{{ request('jenis_ujian') }}">@endif
                                @if(request()->filled('is_active'))<input type="hidden" name="is_active" value="{{ request('is_active') }}">@endif
                                <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                                <a href="{{ url()->current() }}" class="btn btn-light" title="Reset"><i class="ri-refresh-line"></i></a>
                                <a href="{{ route('user.kisi-kisi-soal.create', ['userId' => $userId]) }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Buat Kisi-kisi
                                </a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => null]) }}" class="filter-badge {{ $statusAktif === null ? 'active' : '' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => 1]) }}" class="filter-badge {{ $statusAktif === true ? 'active' : '' }}"><i class="ri-checkbox-circle-line"></i> Aktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['is_active' => 0]) }}" class="filter-badge {{ $statusAktif === false ? 'active' : '' }}"><i class="ri-close-circle-line"></i> Nonaktif</a>
                        <span class="text-muted small ms-2 me-2">·</span>
                        @foreach($jenisLabels as $value => $label)
                            <a href="{{ request()->fullUrlWithQuery(['jenis_ujian' => request('jenis_ujian') === $value ? null : $value]) }}"
                               class="filter-badge {{ request('jenis_ujian') === $value ? 'active' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-freeze mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Judul</th>
                                <th>Mapel</th>
                                <th>Fase</th>
                                <th class="text-center">Sem</th>
                                <th class="text-center">Jenis</th>
                                <th class="text-center">Status</th>
                                <th>Tgl</th>
                                <th class="text-end" style="width:120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kisis as $kisi)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.kisi-kisi-soal.show', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" class="fw-medium link-primary">{{ $kisi->judul }}</a>
                                        @if($kisi->deskripsi)
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($kisi->deskripsi, 70) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $kisi->subject->name ?? '-' }}</td>
                                    <td>{{ $kisi->gradeLevel->nama ?? '-' }}</td>
                                    <td class="text-center">{{ ucfirst($kisi->semester) }}</td>
                                    <td class="text-center"><span class="badge bg-info-subtle text-info">{{ str_replace('_', ' ', $kisi->jenis_ujian) }}</span></td>
                                    <td class="text-center">
                                        @if($kisi->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $kisi->created_at->format('d M Y') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('user.kisi-kisi-soal.edit', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" class="btn btn-sm btn-soft-warning" title="Edit">
                                            <i class="ri-pencil-line"></i>
                                        </a>
                                        <form action="{{ route('user.kisi-kisi-soal.destroy', ['userId' => $userId, 'kisiUuid' => $kisi->id]) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus kisi-kisi ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-soft-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                                            Belum ada kisi-kisi pada filter ini.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-body pt-0">
                    @include('shared._pagination', ['paginator' => $kisis])
                </div>
            </div>
        </div>
    </div>
@endsection
