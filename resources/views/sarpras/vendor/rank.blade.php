@extends('layouts.master')
@section('title') Ranking Vendor @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.vendor.index') }}">Vendor</a> @endslot
        @slot('title') Ranking Vendor
    @endcomponent

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><div><h5 class="card-title mb-0"><i class="ri-trophy-line me-2 text-warning"></i>Ranking Performa Vendor</h5><p class="text-muted small mb-0">Berdasarkan rating dan riwayat pekerjaan.</p></div><a href="{{ route('sarpras.vendor.index') }}" class="btn btn-light btn-sm">Kembali</a></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>#</th><th>Kode</th><th>Vendor</th><th>Rating</th><th>Jumlah Rating</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($rankings as $i => $v)
                <tr><td>{{ $i + 1 }}</td><td><code>{{ $v['vendor_code'] ?? '-' }}</code></td><td class="fw-medium">{{ $v['name'] ?? '-' }}</td><td><span class="text-warning">★</span> {{ number_format((float) ($v['rating_avg'] ?? 0), 1) }}</td><td>{{ $v['rating_count'] ?? 0 }}</td><td><span class="badge bg-success-subtle text-success">{{ ucfirst($v['status'] ?? '-') }}</span></td><td class="text-end"><a href="{{ route('sarpras.vendor.show', ['id' => $v['vendor_id']]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
            @empty
                <x-empty-state colspan="7" title="Belum ada ranking" message="Ranking muncul setelah vendor memiliki penilaian." />
            @endforelse
        </tbody></table></div></div>
    </div>
@endsection
