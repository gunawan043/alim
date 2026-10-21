@extends('layouts.master')
@section('title', 'Rekap Cuti GTK')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('personalia._gtk-ui')
    @component('components.breadcrumb') @slot('li_1') Personalia @endslot @slot('li_2') Cuti @endslot @slot('title') Rekap Cuti @endslot @endcomponent
    <div class="row g-2 mb-3">
        @foreach([['Total Pengajuan', $stats->sum('total'), 'ri-file-list-3-line', 'primary'], ['Disetujui', $stats->get('APPROVED')?->total ?? 0, 'ri-checkbox-circle-line', 'success'], ['Ditolak', $stats->get('REJECTED')?->total ?? 0, 'ri-close-circle-line', 'danger'], ['Hari Disetujui', $stats->get('APPROVED')?->total_hari ?? 0, 'ri-calendar-check-line', 'info']] as [$label, $value, $icon, $color])
            <div class="col-md-3"><div class="card card-animate"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded"><i class="{{ $icon }} text-{{ $color }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div></div></div></div></div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-file-chart-line text-primary me-1"></i>Rekap Cuti {{ $period?->name ?? 'Tahun Aktif' }}</h5><p class="text-muted mb-0 small">Daftar pengajuan cuti pada periode aktif.</p></div><a href="{{ route('user.cuti.rekap.export', ['userId'=>$userId]) }}" class="btn btn-success btn-sm"><i class="ri-file-excel-line me-1"></i>Export CSV</a></div>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Nama</th><th>Jenis Cuti</th><th>Tanggal</th><th>Hari</th><th>Status</th><th>Disetujui Oleh</th></tr></thead><tbody>
        @forelse($rows as $row)
            <tr><td class="fw-medium">{{ $row->user?->name ?? '—' }}</td><td>{{ $row->template?->nama ?? '—' }}</td><td>{{ $row->tanggal_mulai?->format('d/m/Y') }} — {{ $row->tanggal_selesai?->format('d/m/Y') }}</td><td>{{ $row->jumlah_hari }}</td><td><span class="badge bg-{{ $row->status === 'APPROVED' ? 'success' : ($row->status === 'REJECTED' ? 'danger' : 'warning') }}-subtle text-{{ $row->status === 'APPROVED' ? 'success' : ($row->status === 'REJECTED' ? 'danger' : 'warning') }}">{{ ucfirst(strtolower($row->status)) }}</span></td><td>{{ $row->approver?->name ?? '—' }}</td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada pengajuan cuti.</td></tr>@endforelse
        </tbody></table></div>
        <div class="card-body">{{ $rows->links() }}</div>
    </div>
@endsection
