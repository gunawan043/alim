@extends('layouts.master')

@section('title') Laporan GTK @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Laporan @endslot
        @slot('li_2') <a href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Semua Laporan</a> @endslot
        @slot('title') Laporan GTK @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Total GTK</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['total']) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Akun Aktif</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($summary['aktif']) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body py-3">
                <div class="text-muted small text-uppercase fw-semibold">Akun Nonaktif</div>
                <div class="fs-3 fw-bold text-secondary">{{ number_format($summary['nonaktif']) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('user.laporan.gtk', ['userId' => $userId]) }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Cari</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama / email…">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Jenis GTK</label>
                    <select name="jenis" class="form-select">
                        <option value="">Semua</option>
                        @foreach($jenisOptions as $jenis)
                            <option value="{{ $jenis }}" {{ request('jenis') === $jenis ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $jenis)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
                    <a href="{{ route('user.laporan.export', array_merge(['userId' => $userId, 'type' => 'gtk'], request()->query())) }}"
                       class="btn btn-success w-100"><i class="ri-download-2-line me-1"></i>CSV</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0"><i class="ri-contacts-book-2-line me-2 text-primary"></i>Daftar GTK</h5>
            <a href="{{ route('user.laporan.pdf', array_merge(['userId' => $userId, 'type' => 'gtk'], request()->query())) }}"
               target="_blank" class="btn btn-sm btn-danger">
                <i class="ri-file-pdf-2-line me-1"></i>Cetak PDF
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Jenis GTK</th>
                            <th>Status Kepegawaian</th>
                            <th>Status Akun</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gtk as $i => $person)
                            <tr>
                                <td>{{ $gtk->firstItem() + $i }}</td>
                                <td>
                                    <div class="fw-medium">{{ $person->name }}</div>
                                    <div class="small text-muted">{{ $person->email }}</div>
                                </td>
                                <td>{{ $person->employment?->jabatan ?: '—' }}</td>
                                <td>{{ $person->employment?->jenis_gtk ? ucwords(str_replace('_', ' ', $person->employment->jenis_gtk)) : '—' }}</td>
                                <td>{{ $person->employment?->status_kepegawaian ? ucwords(str_replace('_', ' ', $person->employment->status_kepegawaian)) : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $person->is_active ? 'success' : 'secondary' }}-subtle text-{{ $person->is_active ? 'success' : 'secondary' }}">
                                        {{ $person->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" title="Tidak ada data GTK"
                                message="Tidak ada GTK yang sesuai filter." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($gtk->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $gtk])
            </div>
        @endif
    </div>
@endsection
