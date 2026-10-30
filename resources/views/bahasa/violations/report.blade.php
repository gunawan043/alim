@extends('layouts.master')

@section('title', 'Rekap Poin Bahasa')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
@endphp

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}">Pelanggaran</a> @endslot
        @slot('title') Rekap Poin Bahasa @endslot
    @endcomponent

    {{-- FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- HEADER HALAMAN --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Rekap Poin Pelanggaran Bahasa</h5>
                    <p class="text-muted mb-0 small">Akumulasi poin per santri. Pembinaan Wakil Departemen Bahasa dimulai pada {{ $threshold }} poin.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}"
                           class="btn btn-outline-secondary">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD UTAMA --}}
    <div class="card">
        <div class="card-body">
            {{-- FILTER --}}
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label small mb-1">Ambang Poin</label>
                        <input type="number" min="1" name="threshold" value="{{ $threshold }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="ri-filter-3-line me-1"></i> Terapkan
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-light flex-shrink-0">
                            <i class="ri-printer-line me-1"></i> Cetak
                        </button>
                    </div>
                </div>
            </form>

            {{-- COUNTER --}}
            <div class="d-flex align-items-center gap-2 mb-2">
                <small class="text-muted">
                    <i class="ri-file-list-line me-1"></i> Menampilkan
                    <strong>{{ count($rows) }}</strong> santri
                </small>
            </div>

            {{-- TABEL --}}
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th>Nama Santri</th>
                            <th>Kelas</th>
                            <th class="text-center">Jumlah Pelanggaran</th>
                            <th class="text-center">Total Poin</th>
                            <th class="text-center">Sudah Dibina</th>
                            <th>Terakhir</th>
                            <th>Tindak Lanjut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $index => $row)
                            <tr @class(['table-warning' => $row['points'] >= $threshold])>
                                <td class="text-center text-muted">{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $row['student']?->name ?? '—' }}</td>
                                <td>{{ $row['class_name'] ?: '—' }}</td>
                                <td class="text-center">{{ $row['count'] }}</td>
                                <td class="text-center fw-bold">{{ $row['points'] }}</td>
                                <td class="text-center">{{ $row['coached'] }}</td>
                                <td>{{ $row['last_date'] ? \Carbon\Carbon::parse($row['last_date'])->format('d/m/Y') : '—' }}</td>
                                <td>
                                    @if($row['points'] >= $threshold)
                                        <span class="badge lang-badge bg-danger-subtle text-danger">Pembinaan Wakil Dept.</span>
                                    @elseif($row['points'] > 0)
                                        <span class="badge lang-badge bg-warning-subtle text-warning">Pembinaan Qismul</span>
                                    @else
                                        <span class="badge lang-badge bg-success-subtle text-success">Tidak Ada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/msoeawqm.json"
                                        trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a"
                                        style="width:75px;height:75px">
                                    </lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Belum ada data pelanggaran</h6>
                                    <p class="text-muted mb-0 small">Belum ada pelanggaran tercatat.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection