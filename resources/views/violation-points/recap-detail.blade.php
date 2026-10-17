@extends('layouts.master')
@section('title') Detail Poin Santri @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Peserta Didik</a> @endslot
        @slot('li_2') <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}">Rekap Poin per Santri</a> @endslot
        @slot('title') {{ $student->name }} @endslot
    @endcomponent

    @php
        $level = ['subtle' => 'success-subtle', 'text' => 'success', 'label' => 'Tidak Ada'];
        if ($totalPoints >= 25) {
            $level = ['subtle' => 'dark-subtle', 'text' => 'dark', 'label' => 'Sangat Berat'];
        } elseif ($totalPoints >= 16) {
            $level = ['subtle' => 'danger-subtle', 'text' => 'danger', 'label' => 'Berat'];
        } elseif ($totalPoints >= 6) {
            $level = ['subtle' => 'warning-subtle', 'text' => 'warning', 'label' => 'Sedang'];
        } elseif ($totalPoints >= 1) {
            $level = ['subtle' => 'info-subtle', 'text' => 'info', 'label' => 'Ringan'];
        }

        $words = preg_split('/\s+/', trim((string) $student->name)) ?: [];
        $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1).(count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : ''));
    @endphp

    {{-- Ringkasan santri --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-error-warning-line text-danger"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Poin</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalPoints) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Akumulasi poin santri ini</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-file-list-3-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Jumlah Pelanggaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $violations->total() }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh catatan tercatat</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-bar-chart-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Tingkat Pelanggaran</p>
                            <span class="badge bg-{{ $level['subtle'] }} text-{{ $level['text'] }}">{{ $level['label'] }}</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Berdasarkan akumulasi poin</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary text-white rounded-circle">{{ $initials }}</span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Identitas</p>
                            <h6 class="mb-0 fw-semibold">{{ $student->name }}</h6>
                            <small class="text-muted">{{ $student->nisn ? 'NISN: '.$student->nisn : 'NISN: -' }}</small>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label mt-2">
                        <i class="ri-government-line me-1"></i>
                        {{ $student->studyGroups->first()?->studyGroup?->full_name ?? 'Rombel: -' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0">Riwayat Pelanggaran</h5>
                    <p class="text-muted mb-0 small">Disusun kronologis dari yang terbaru.</p>
                </div>
                <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Kembali ke Rekap
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px">No</th>
                            <th style="width:110px">Tanggal</th>
                            <th>Jenis Pelanggaran</th>
                            <th class="text-center" style="width:80px">Poin</th>
                            <th>Tindakan</th>
                            <th style="width:150px">Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($violations as $i => $v)
                            <tr>
                                <td class="text-center text-muted">{{ $violations->firstItem() + $i }}</td>
                                <td class="small">{{ $v->violation_date->format('d/m/Y') }}</td>
                                <td>{{ $v->violation_type }}</td>
                                <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $v->points }}</span></td>
                                <td><span class="small text-muted">{{ Str::limit($v->action_taken, 50) ?: '—' }}</span></td>
                                <td class="small">{{ $v->recordedBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6"
                                title="Santri ini tidak memiliki pelanggaran"
                                message="Tidak ada catatan pelanggaran pada santri ini." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body pt-0">
            @include('shared._pagination', ['paginator' => $violations])
        </div>
    </div>
@endsection
