@extends('layouts.master')
@section('title') Laporan Peminjaman @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.laporan.index') }}">Laporan</a> @endslot
    @slot('title') Peminjaman @endslot
@endcomponent

<div class="row">
    {{-- SUMMARY CARDS --}}
    <div class="col-sm-6 col-md-4">
        <div class="card card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3"><div class="avatar-sm"><div class="avatar-title bg-warning-subtle text-warning rounded fs-4">⏳</div></div></div>
                <div>
                    <p class="text-muted small mb-1">Menunggu</p>
                    <h4 class="mb-0">{{ $summary['pending'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3"><div class="avatar-sm"><div class="avatar-title bg-info-subtle text-info rounded fs-4">✓</div></div></div>
                <div>
                    <p class="text-muted small mb-1">Disetujui</p>
                    <h4 class="mb-0">{{ $summary['approved'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3"><div class="avatar-sm"><div class="avatar-title bg-primary-subtle text-primary rounded fs-4">📦</div></div></div>
                <div>
                    <p class="text-muted small mb-1">Sedang Dipinjam</p>
                    <h4 class="mb-0">{{ $summary['dipinjam'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3"><div class="avatar-sm"><div class="avatar-title bg-success-subtle text-success rounded fs-4">↩</div></div></div>
                <div>
                    <p class="text-muted small mb-1">Dikembalikan</p>
                    <h4 class="mb-0">{{ $summary['dikembalikan'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3"><div class="avatar-sm"><div class="avatar-title bg-danger-subtle text-danger rounded fs-4">⚠</div></div></div>
                <div>
                    <p class="text-muted small mb-1">Terlambat</p>
                    <h4 class="mb-0">{{ $summary['terlambat'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3"><div class="avatar-sm"><div class="avatar-title bg-secondary-subtle text-secondary rounded fs-4">Σ</div></div></div>
                <div>
                    <p class="text-muted small mb-1">Total Keseluruhan</p>
                    <h4 class="mb-0">{{ $summary['total'] }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detail Peminjaman</h5>
                @can('viewAllSarpras', \App\Models\User::class)
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="ri-printer-line me-1"></i> Cetak</button>
                @endcan
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    @if($schools->isNotEmpty())
                    <div class="col-md-3">
                        <select name="school_id" class="form-select">
                            <option value="">Semua Satuan Pendidikan</option>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach(['pending','approved','dipinjam','dikembalikan','terlambat','dibatalkan'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_',' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="Dari">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="Sampai">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i> Filter</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('sarpras.laporan.peminjaman') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Aset</th>
                                <th>Kode</th>
                                <th>Peminjam</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Kondisi Pinjam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loans as $l)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><a href="{{ route('sarpras.aset.show', ['id' => $l->asset_id]) }}">{{ $l->asset?->asset_name ?? '-' }}</a></td>
                                <td><code>{{ $l->asset?->asset_code ?? '-' }}</code></td>
                                <td>{{ $l->borrower?->name ?? '-' }}</td>
                                <td>{{ $l->loan_date?->format('d/m/Y') }}</td>
                                <td>
                                    {{ $l->expected_return_date?->format('d/m/Y') }}
                                    @if($l->status === 'dipinjam' && $l->expected_return_date && $l->expected_return_date->isPast())
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst(str_replace('_',' ', $l->condition_on_loan ?? '-')) }}</td>
                                <td>
                                    @php $sc=['pending'=>'warning','approved'=>'info','dipinjam'=>'primary','dikembalikan'=>'success','terlambat'=>'danger','dibatalkan'=>'secondary']; @endphp
                                    <span class="badge bg-{{ $sc[$l->status] ?? 'secondary' }}-subtle text-{{ $sc[$l->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_',' ', $l->status)) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-4">Tidak ada data peminjaman.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection