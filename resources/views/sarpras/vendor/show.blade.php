@extends('layouts.sarpras')

@section('title', 'Detail Vendor — ' . $vendor->name)

@section('content')
<div class="card mb-3">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $vendor->name }}</h5>
        <div>
            <a href="{{ route('sarpras.vendor.edit', $vendor) }}" class="btn btn-light btn-sm">Edit</a>
            <a href="{{ route('sarpras.vendor.index') }}" class="btn btn-outline-light btn-sm">Kembali</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h6>Informasi Dasar</h6>
                <table class="table table-sm table-borderless">
                    <tr><th>Kode:</th><td>{{ $vendor->vendor_code ?? '-' }}</td></tr>
                    <tr><th>NPWP:</th><td>{{ $vendor->npwp ?? '-' }}</td></tr>
                    <tr><th>Tipe:</th><td>{{ ucfirst(str_replace('_', ' ', $vendor->vendor_type ?? '')) }}</td></tr>
                    <tr><th>Status:</th><td>
                        <span class="badge bg-{{ match($vendor->status) {
                            'active' => 'success', 'inactive' => 'secondary', 'blacklist' => 'danger', default => 'info'
                        }}">{{ ucfirst($vendor->status) }}</span>
                    </td></tr>
                    <tr><th>Kategori:</th><td>{{ $vendor->category->name ?? '-' }}</td></tr>
                    <tr><th>Risk:</th><td>{{ ucfirst($vendor->risk_classification ?? '-') }}</td></tr>
                    <tr><th>Credit Limit:</th><td>Rp {{ number_format($vendor->credit_limit ?? 0, 0, ',', '.') }}</td></tr>
                </table>
            </div>
            <div class="col-md-4">
                <h6>Kontak</h6>
                <table class="table table-sm table-borderless">
                    <tr><th>Telp:</th><td>{{ $vendor->phone ?? '-' }}</td></tr>
                    <tr><th>Email:</th><td>{{ $vendor->email ?? '-' }}</td></tr>
                    <tr><th>Website:</th><td>{{ $vendor->website ?? '-' }}</td></tr>
                </table>
                <h6 class="mt-3">Alamat</h6>
                <p>{{ $vendor->addresses->first()?->street_address ?? '-' }}<br>
                {{ $vendor->addresses->first()?->city ?? '' }}{{ $vendor->addresses->first()?->province ? ', ' . $vendor->addresses->first()->province : '' }}
                {{ $vendor->addresses->first()?->postal_code ?? '' }}</p>
            </div>
            <div class="col-md-4">
                <h6>Performa Vendor</h6>
                <table class="table table-sm table-borderless">
                    <tr><th>Rating:</th><td>
                        @if ($vendor->rating_count > 0)
                            ⭐ {{ number_format($vendor->rating_avg, 1) }}/5.0 ({{ $vendor->rating_count }})
                        @else
                            Belum ada rating
                        @endif
                    </td></tr>
                    <tr><th>Skor:</th><td>{{ $performance['score'] ?? '-' }}/{{ $performance['grade'] ?? '-' }}</td></tr>
                    <tr><th>On-Time %:</th><td>{{ $performance['on_time_pct'] ?? '-' }}%</td></tr>
                    <tr><th>Total Value:</th><td>Rp {{ number_format($performance['total_value'] ?? 0, 0, ',', '.') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<h5 class="mt-4">Hubungan Terkait</h5>
<ul class="nav nav-tabs">
    <li class="nav-item"><a class="nav-link active" href="#contracts" data-bs-toggle="tab">Contracts</a></li>
    <li class="nav-item"><a class="nav-link" href="#sla" data-bs-toggle="tab">SLAs</a></li>
    <li class="nav-item"><a class="nav-link" href="#warranties" data-bs-toggle="tab">Warranties</a></li>
</ul>
<div class="tab-content border border-top-0 p-3">
    <div class="tab-pane active" id="contracts">
        @forelse ($vendor->contracts as $contract)
            <div class="card mb-2">
                <div class="card-body">
                    <strong>{{ $contract->name }}</strong> ({{ $contract->contract_number }})
                    <br>
                    <small>{{ $contract->start_date->format('d M Y') }} - {{ $contract->end_date->format('d M Y') }}</small>
                </div>
            </div>
        @empty
            <p class="text-muted">Belum ada contracts.</p>
        @endforelse
    </div>
    <div class="tab-pane" id="sla">SLA tab content</div>
    <div class="tab-pane" id="warranties">Warranty tab content</div>
</div>
@endsection