@extends('layouts.master')
@section('title') Penghargaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Penghargaan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="row g-3 mb-2">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-warning">
                <div class="card-body py-3 d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Penghargaan</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-medal-line fs-24 text-warning"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90 border-start border-success">
                <div class="card-body py-3 d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bulan Ini</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['thisMonth'] ?? 0) }}</h3>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line fs-24 text-success"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Daftar Penghargaan</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.asrama.rewards.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-sm btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Tambah
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Cari santri..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">Semua Kategori</option>
                                @foreach(\App\Models\DormitoryReward::categories() as $key => $label)
                                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="level" class="form-select">
                                <option value="">Semua Level</option>
                                @foreach(\App\Models\DormitoryReward::levels() as $key => $label)
                                    <option value="{{ $key }}" {{ request('level') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                    <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-subtle">
                        <tr>
                            <th class="ps-3">Santri</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Level</th>
                            <th>Tanggal</th>
                            <th>Diberikan Oleh</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rewards as $reward)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $reward->student->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $reward->student->nisn ?? '' }}</small>
                                </td>
                                <td>{{ $reward->title }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ $reward->category_text }}</span></td>
                                <td>
                                    @if($reward->level === 'unggulan')
                                        <span class="badge bg-warning-subtle text-warning">{{ $reward->level_text }}</span>
                                    @elseif($reward->level === 'istimewa')
                                        <span class="badge bg-danger-subtle text-danger">{{ $reward->level_text }}</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $reward->level_text }}</span>
                                    @endif
                                </td>
                                <td>{{ $reward->awarded_date->format('d/m/Y') }}</td>
                                <td>{{ $reward->givenBy->name ?? '-' }}</td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('user.asrama.rewards.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'rewardUuid' => $reward->id]) }}" class="btn btn-outline-info" title="Detail">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('user.asrama.rewards.edit', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'rewardUuid' => $reward->id]) }}" class="btn btn-outline-warning" title="Edit">
                                            <i class="ri-pencil-line"></i>
                                        </a>
                                        <form action="{{ route('user.asrama.rewards.destroy', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'rewardUuid' => $reward->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus penghargaan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Belum Ada Data Penghargaan</h6>
                                    <p class="text-muted mb-3">Belum ada penghargaan yang tercatat.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$rewards" />
        </div>
            </div>
        </div>
    </div>
@endsection
