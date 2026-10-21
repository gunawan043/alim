@extends('layouts.master')
@section('title', 'Laporan Perpustakaan')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $quickBase = ['userId' => $userId, 'unit_id' => $unit->id, 'month' => $month];
        $filterAction = route('user.perpustakaan.reports.index');
        $filterHidden = ['unit_id' => $unit->id];
        $filterFields = [
            ['name' => 'month', 'label' => 'Bulan', 'type' => 'month', 'value' => $month],
            ['name' => 'tab', 'label' => 'Jenis Laporan', 'type' => 'select', 'options' => ['summary' => 'Ringkasan', 'loans' => 'Statistik Peminjaman', 'popular' => 'Buku Terpopuler', 'members' => 'Anggota Teraktif', 'annual' => 'Laporan Tahunan']],
        ];
        $filterControls = [
            ['type' => 'month', 'name' => 'month', 'label' => 'Bulan', 'value' => $month],
            ['type' => 'select', 'name' => 'tab', 'label' => 'Jenis Laporan', 'options' => ['summary' => 'Ringkasan', 'loans' => 'Statistik Peminjaman', 'popular' => 'Buku Terpopuler', 'members' => 'Anggota Teraktif', 'annual' => 'Laporan Tahunan']],
        ];
        $quickFilters = [];
        foreach (['summary'=>'Ringkasan','loans'=>'Statistik Peminjaman','popular'=>'Buku Terpopuler','members'=>'Anggota Teraktif','annual'=>'Laporan Tahunan'] as $key => $label) {
            $quickFilters[] = ['label' => $label, 'url' => route('user.perpustakaan.reports.index', $quickBase + ['tab' => $key]), 'icon' => $key === 'popular' ? 'ri-fire-line' : ($key === 'members' ? 'ri-user-star-line' : 'ri-bar-chart-2-line'), 'active' => $tab === $key];
        }
    @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Laporan @endslot @slot('title') Statistik &amp; Laporan @endslot @endcomponent
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card mb-3"><div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-bar-chart-2-line text-primary me-1"></i>Laporan {{ $unit->name }}</h5><p class="text-muted mb-0 small">{{ $rangeStart?->format('d/m/Y') }} — {{ $rangeEnd?->format('d/m/Y') }}</p></div><div class="d-flex flex-wrap gap-2"><button class="btn btn-soft-primary btn-sm" onclick="window.print()"><i class="ri-printer-line me-1"></i>Cetak</button></div></div></div>
    <div class="row g-2 mb-3 library-stats">
        @foreach([['Peminjaman',$stats['loans'],'ri-exchange-line','primary'],['Sedang Dipinjam',$stats['active'],'ri-book-open-line','info'],['Kunjungan',$stats['visits'],'ri-footprint-line','success'],['Anggota Aktif',$stats['members'],'ri-team-line','warning'],['Koleksi',$stats['items'],'ri-stack-line','secondary'],['Terlambat',$stats['overdue'],'ri-timer-line','danger']] as [$l,$v,$i,$c])
            <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3"><div class="d-flex gap-2 align-items-center"><span class="avatar-sm"><span class="avatar-title bg-{{ $c }}-subtle rounded"><i class="{{ $i }} text-{{ $c }}"></i></span></span><div><div class="text-muted small">{{ $l }}</div><div class="fs-4 fw-bold">{{ $v }}</div></div></div></div></div></div>
        @endforeach
    </div>
    <div class="card mb-3">@include('perpustakaan._filter-panel')</div>
    <div class="card mb-3">@include('perpustakaan._quick-filters')</div>

    @if($tab === 'summary' || $tab === 'annual')
        <div class="row g-3 mb-3">
            <div class="col-lg-7"><div class="card h-100"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Tren Bulanan</h5></div><div class="table-container"><table class="table table-sm align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Bulan</th><th>Peminjaman</th><th>Kunjungan</th></tr></thead><tbody>@foreach($monthly as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['loans'] }}</td><td>{{ $row['visits'] }}</td></tr>@endforeach</tbody></table></div></div></div>
            <div class="col-lg-5"><div class="card h-100"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0">Buku Terpopuler</h5></div><div class="table-container"><table class="table table-sm align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Judul</th><th class="text-end">Dipinjam</th></tr></thead><tbody>@forelse($topTitles as $row)<tr><td><div class="fw-medium">{{ $row['title'] }}</div><div class="text-muted small">{{ $row['author'] }}</div></td><td class="text-end">{{ $row['count'] }}</td></tr>@empty<tr><td colspan="2" class="text-muted text-center py-4">Belum ada data.</td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    @endif

    @if($tab === 'popular')
        <div class="card mb-3"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-fire-line text-danger me-1"></i>Buku Terpopuler</h5></div><div class="table-container"><table class="table table-hover align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>#</th><th>Judul</th><th>Penulis</th><th>Jumlah Peminjaman</th></tr></thead><tbody>@forelse($topTitles as $index => $row)<tr><td>{{ $index + 1 }}</td><td class="fw-medium">{{ $row['title'] }}</td><td>{{ $row['author'] ?: '—' }}</td><td>{{ $row['count'] }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-5">Belum ada data peminjaman.</td></tr>@endforelse</tbody></table></div></div>
    @endif

    @if($tab === 'members')
        <div class="card mb-3"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-user-star-line text-warning me-1"></i>Anggota Teraktif</h5></div><div class="table-container"><table class="table table-hover align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>#</th><th>Nomor Anggota</th><th>Nama</th><th>Jumlah Peminjaman</th></tr></thead><tbody>@forelse($topMembers as $index => $row)<tr><td>{{ $index + 1 }}</td><td><code>{{ $row['member']?->member_number }}</code></td><td>{{ $row['member']?->display_name }}</td><td>{{ $row['count'] }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-5">Belum ada data peminjaman.</td></tr>@endforelse</tbody></table></div></div>
    @endif

    @if($tab === 'loans' || $tab === 'annual')
        <div class="card"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-file-list-3-line text-primary me-1"></i>Daftar Peminjaman</h5></div><div class="table-container"><table class="table table-hover library-table align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Nomor</th><th>Anggota</th><th>Buku</th><th>Tanggal</th><th>Jatuh Tempo</th><th>Status</th></tr></thead><tbody>@forelse($loans as $loan)<tr><td><code>{{ $loan->loan_number }}</code></td><td>{{ $loan->member?->display_name }}</td><td class="small">{{ $loan->items->pluck('item.title.title')->filter()->implode(', ') ?: '—' }}</td><td>{{ $loan->loan_date?->format('d/m/Y') }}</td><td>{{ $loan->due_date?->format('d/m/Y') }}</td><td><span class="badge bg-light text-dark">{{ ucfirst($loan->status) }}</span></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada peminjaman pada periode ini.</td></tr>@endforelse</tbody></table></div></div>
    @endif
    @include('perpustakaan._filter-modal')
@endsection
