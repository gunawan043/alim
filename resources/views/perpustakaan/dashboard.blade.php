@extends('layouts.master')
@section('title', 'Perpustakaan')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('perpustakaan._styles')

    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('title') Dashboard @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-primary-subtle mb-3"><div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-1"><i class="ri-book-2-line text-primary me-1"></i>Perpustakaan {{ $year?->name }}</h5><p class="text-muted mb-0 small">Kelola koleksi, anggota, sirkulasi, dan kunjungan per unit.</p></div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('user.perpustakaan.loans.index', ['userId' => $userId, 'unit_id' => $units->first()?->id]) }}" class="btn btn-primary btn-sm"><i class="ri-exchange-line me-1"></i>Peminjaman</a>
                <a href="{{ route('user.perpustakaan.visits.index', ['userId' => $userId, 'unit_id' => $units->first()?->id]) }}" class="btn btn-soft-primary btn-sm"><i class="ri-footprint-line me-1"></i>Kunjungan</a>
                <a href="{{ route('user.perpustakaan.rooms.index', ['userId' => $userId, 'unit_id' => $units->first()?->id]) }}" class="btn btn-soft-info btn-sm"><i class="ri-calendar-event-line me-1"></i>Ruang</a>
                <a href="{{ route('user.perpustakaan.stock-opnames.index', ['userId' => $userId, 'unit_id' => $units->first()?->id]) }}" class="btn btn-soft-warning btn-sm"><i class="ri-file-list-2-line me-1"></i>Stock Opname</a>
            </div>
        </div>
    </div></div>

    <div class="row g-2 mb-3">
        @foreach([['Judul', $stats['titles'], 'ri-book-mark-line', 'primary'], ['Eksemplar', $stats['items'], 'ri-stack-line', 'info'], ['Tersedia', $stats['available'], 'ri-checkbox-circle-line', 'success'], ['Anggota', $stats['members'], 'ri-team-line', 'warning'], ['Pinjaman Aktif', $stats['loans'], 'ri-exchange-line', 'danger'], ['Kunjungan Hari Ini', $stats['visits_today'], 'ri-footprint-line', 'secondary']] as [$label,$value,$icon,$color])
            <div class="col-xl-2 col-md-4 col-6"><div class="card card-animate"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded"><i class="{{ $icon }} text-{{ $color }}"></i></span></span><div><div class="text-muted small text-uppercase">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div></div></div></div></div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-6"><div class="card h-100"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Peminjaman Terbaru</h5></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Anggota</th><th>Buku</th><th>Jatuh Tempo</th><th>Status</th></tr></thead><tbody>
            @forelse($recentLoans as $loan)
                <tr><td>{{ $loan->member?->display_name }}</td><td class="small">{{ $loan->items->pluck('item.title.title')->filter()->implode(', ') ?: '—' }}</td><td>{{ $loan->due_date?->format('d/m/Y') }}</td><td><span class="badge bg-primary-subtle text-primary">{{ ucfirst($loan->status) }}</span></td></tr>
            @empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada peminjaman.</td></tr>@endforelse
        </tbody></table></div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Kunjungan Terbaru</h5></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Nama</th><th>Kelas</th><th>Tanggal</th><th>Keperluan</th></tr></thead><tbody>
            @forelse($recentVisits as $visit)
                <tr><td>{{ $visit->visitor_name }}</td><td>{{ $visit->class_snapshot ?? '—' }}</td><td>{{ $visit->visit_date?->format('d/m/Y') }}</td><td class="small">{{ $visit->purpose ?: '—' }}</td></tr>
            @empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada kunjungan.</td></tr>@endforelse
        </tbody></table></div></div></div>
    </div>
@endsection
