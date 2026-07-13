@extends('layouts.sarpras')

@section('title', 'Manajemen Vendor')

@section('content')
<div class="card">
    <div class="card-header bg-gradient-primary text-white d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Vendor & Mitra Eksternal</h5>
        <a href="{{ route('sarpras.vendor.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Tambah Vendor
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" id="search" class="form-control" placeholder="Cari nama, kode, NPWP...">
            </div>
            <div class="col-md-3">
                <select id="filter-status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                    <option value="blacklist">Blacklist</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filter-category" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama Vendor</th>
                        <th>Kategori</th>
                        <th>NPWP</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            <td><code>{{ $vendor->vendor_code ?? '-' }}</code></td>
                            <td>
                                <strong>{{ $vendor->name }}</strong>
                                <br><small class="text-muted">{{ $vendor->vendor_type }}</small>
                            </td>
                            <td>{{ $vendor->category->name ?? '-' }}</td>
                            <td>{{ $vendor->npwp ?? '-' }}</td>
                            <td>
                                {{ $vendor->phone ?? '-' }}<br>
                                <small class="text-muted">{{ $vendor->email ?? '' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ match($vendor->status) {
                                    'active' => 'success',
                                    'inactive' => 'secondary',
                                    'blacklist' => 'danger',
                                    default => 'info'
                                }}">{{ ucfirst($vendor->status) }}</span>
                            </td>
                            <td>
                                @if ($vendor->rating_count > 0)
                                    <span class="text-warning">★ {{ number_format($vendor->rating_avg, 1) }}</span>
                                    <br><small class="text-muted">({{ $vendor->rating_count }})</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('sarpras.vendor.show', $vendor) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4">Tidak ada data vendor</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $vendors->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('search').addEventListener('input', debounce(function() {
    window.location.search = `q=${this.value}&status=${document.getElementById('filter-status').value}&category_id=${document.getElementById('filter-category').value}`;
}));

document.getElementById('filter-status').addEventListener('change', function() {
    window.location.search = `q=${document.getElementById('search').value}&status=${this.value}&category_id=${document.getElementById('filter-category').value}`;
});

document.getElementById('filter-category').addEventListener('change', function() {
    window.location.search = `q=${document.getElementById('search').value}&status=${document.getElementById('filter-status').value}&category_id=${this.value}`;
});

function debounce(fn, delay = 300) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), delay); };
}
</script>
@endpush