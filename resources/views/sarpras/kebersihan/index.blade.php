@extends('layouts.master')
@section('title') Kebersihan & CS @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Kebersihan & CS
    @endcomponent

    <div class="alert alert-info d-flex align-items-center gap-2"><i class="ri-google-line fs-5"></i><span>Foto bukti dapat berupa <strong>tautan Google Drive</strong> atau upload/kamera langsung dengan watermark. Koordinator memantau; petugas mengerjakan area yang ditugaskan.</span></div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Area Kerja', $stats['areas'], 'primary', 'ri-map-pin-line'],
            ['Uraian Pekerjaan', $stats['tasks'], 'info', 'ri-list-check-2'],
            ['Laporan Hari Ini', $stats['today'], 'success', 'ri-file-check-line'],
            ['Menunggu Verifikasi', $stats['pending'], 'warning', 'ri-time-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Area Kerja', 'Kelola area, gedung/ruang Sarpras, uraian pekerjaan, dan petugas.', route('sarpras.kebersihan.areas'), 'ri-map-pin-line', 'primary'],
            ['Laporan Kebersihan', 'Lihat, filter, dan verifikasi laporan harian petugas.', route('sarpras.kebersihan.reports'), 'ri-file-list-3-line', 'success'],
            ['Program Tahunan', 'Program kebersihan 1 tahun dengan checklist 12 bulan.', route('sarpras.kebersihan.programs'), 'ri-calendar-todo-line', 'info'],
        ] as [$title, $desc, $url, $icon, $tone])
            <div class="col-md-4"><a href="{{ $url }}" class="card h-100 text-decoration-none text-dark border-0 shadow-sm"><div class="card-body"><span class="avatar-sm mb-3"><span class="avatar-title rounded bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><h5>{{ $title }}</h5><p class="text-muted small mb-0">{{ $desc }}</p></div></a></div>
        @endforeach
    </div>

    @if($canReport)
        <div class="card mb-3 border-success"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="mb-1"><i class="ri-checkbox-multiple-line me-1 text-success"></i>Checklist Harian</h5><p class="text-muted small mb-0">Isi pekerjaan, kondisi, foto sebelum/sesudah, dan backup petugas.</p></div><a href="{{ route('sarpras.kebersihan.report.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Buat Laporan Harian</a></div></div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Laporan Terbaru</h5><a href="{{ route('sarpras.kebersihan.reports') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Area</th><th>Petugas</th><th>Kondisi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($recentReports as $report)
                @php $tone = ['submitted'=>'warning','verified'=>'success','rejected'=>'danger'][$report->status] ?? 'secondary'; @endphp
                <tr><td class="small">{{ $report->report_date?->format('d/m/Y') }}</td><td class="small">{{ $report->area?->name }}</td><td class="small">{{ $report->officer?->name }}</td><td class="small">{{ ucfirst(str_replace('_',' ',$report->overall_condition)) }}</td><td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst($report->status) }}</span></td><td class="text-end"><a href="{{ route('sarpras.kebersihan.report.show', $report->id) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
            @empty
                <x-empty-state colspan="6" title="Belum ada laporan" message="Laporan harian petugas akan tampil di sini." />
            @endforelse
        </tbody></table></div></div>
    </div>
@endsection
