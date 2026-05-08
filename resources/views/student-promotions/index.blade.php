@extends('layouts.master')
@section('title') Promosi Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('title') Promosi Santri @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Promosi Santri</h5>
                            <p class="text-muted mb-0">Kelola kenaikan kelas, tinggal kelas, dan kelulusan massal.</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.student-promotions.create', ['userId' => $userId]) }}"
                               class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> Promosi Baru
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <select name="academic_year" class="form-control">
                                <option value="">Semua Tahun Ajaran</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ request('academic_year') == $ay->id ? 'selected' : '' }}>
                                        {{ $ay->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-filter-line me-1"></i> Filter
                            </button>
                            <a href="{{ route('user.student-promotions.index', ['userId' => $userId]) }}"
                               class="btn btn-light">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Rombel Asal</th>
                                    <th>Rombel Tujuan</th>
                                    <th>Tanggal Efektif</th>
                                    <th>Siswa</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($promotions as $promo)
                                    <tr>
                                        <td>{{ $loop->iteration + ($promotions->currentPage() - 1) * $promotions->perPage() }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $promo->fromAcademicYear?->name }}</span>
                                            <br>
                                            <small class="text-muted">→ {{ $promo->toAcademicYear?->name }}</small>
                                        </td>
                                        <td>{{ $promo->fromStudyGroup?->full_name ?? '-' }}</td>
                                        <td>{{ $promo->toStudyGroup?->full_name ?? '-' }}</td>
                                        <td>{{ $promo->promotion_date?->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $promo->total_students }}</span>
                                            @if($promo->success_count > 0)
                                                <span class="badge bg-success-subtle text-success">{{ $promo->success_count }} ✓</span>
                                            @endif
                                            @if($promo->failed_count > 0)
                                                <span class="badge bg-danger-subtle text-danger">{{ $promo->failed_count }} ✗</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $promo->status_badge_color }}-subtle text-{{ $promo->status_badge_color }}">
                                                {{ $promo->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('user.student-promotions.show', ['userId' => $userId, 'id' => $promo->id]) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="ri-arrow-up-line fs-1"></i>
                                            <p class="mb-0">Belum ada data promosi.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $promotions->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
