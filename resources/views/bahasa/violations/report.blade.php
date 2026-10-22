@extends('layouts.master')

@section('title', 'Rekap Poin Bahasa')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}">Pelanggaran</a> @endslot
        @slot('title') Rekap Poin Bahasa @endslot
    @endcomponent

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Rekap Poin Pelanggaran Bahasa</h4>
            <p class="text-muted mb-0">Akumulasi poin per santri. Pembinaan Wakil Departemen Bahasa dimulai pada {{ $threshold }} poin.</p>
        </div>
        <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Ambang Poin</label>
                    <input type="number" min="1" name="threshold" value="{{ $threshold }}" class="form-control">
                </div>
                <div class="col-md-2"><button class="btn btn-soft-primary w-100"><i class="ri-filter-3-line me-1"></i>Terapkan</button></div>
                <div class="col-md-auto ms-md-auto"><button type="button" onclick="window.print()" class="btn btn-light"><i class="ri-printer-line me-1"></i>Cetak</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:45px">No</th>
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
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $row['student']?->name ?? '-' }}</td>
                                <td>{{ $row['class_name'] ?: '-' }}</td>
                                <td class="text-center">{{ $row['count'] }}</td>
                                <td class="text-center fw-bold">{{ $row['points'] }}</td>
                                <td class="text-center">{{ $row['coached'] }}</td>
                                <td>{{ $row['last_date'] ? \Carbon\Carbon::parse($row['last_date'])->format('d/m/Y') : '-' }}</td>
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
                            <tr><td colspan="8" class="text-center text-muted py-5">Belum ada data pelanggaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
