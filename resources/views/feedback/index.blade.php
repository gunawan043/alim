@extends('layouts.master')

@section('title', 'Kritik, Saran & Laporan Bug')

@section('content')
    @php
        $statusColors = [
            'baru' => 'warning',
            'diproses' => 'info',
            'menunggu_informasi' => 'secondary',
            'selesai' => 'success',
            'ditolak' => 'danger',
        ];
        $priorityColors = [
            'rendah' => 'secondary',
            'sedang' => 'info',
            'tinggi' => 'warning',
            'kritis' => 'danger',
        ];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Layanan @endslot
        @slot('title') Kritik, Saran & Laporan Bug @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Laporan', $stats['total'], 'ri-file-list-3-line', 'primary'],
            ['Baru', $stats['baru'], 'ri-inbox-unarchive-line', 'warning'],
            ['Diproses', $stats['diproses'], 'ri-loader-4-line', 'info'],
            ['Selesai', $stats['selesai'], 'ri-checkbox-circle-line', 'success'],
        ] as [$label, $value, $icon, $color])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $color }}-subtle rounded fs-3">
                                    <i class="{{ $icon }} text-{{ $color }}"></i>
                                </span>
                            </span>
                            <div>
                                <p class="text-uppercase fw-semibold text-muted mb-0 stat-label">{{ $label }}</p>
                                <h4 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-1">
                        <i class="ri-feedback-line text-primary me-1"></i>
                        {{ $canManage ? 'Semua Laporan Pengguna' : 'Laporan Saya' }}
                    </h5>
                    <p class="text-muted small mb-0">
                        {{ $canManage
                            ? 'Laporan dari seluruh satuan kerja/divisi. Gunakan filter untuk meninjau laporan tertentu.'
                            : 'Kritik, saran, dan laporan bug yang Anda kirim beserta status prosesnya.' }}
                    </p>
                </div>
                <a href="{{ route('user.feedback.create', ['userId' => $userId]) }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i> Buat Laporan
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-lg-3 col-md-4">
                    <input type="search" name="q" class="form-control form-control-sm"
                           value="{{ request('q') }}" placeholder="Cari judul/isi laporan...">
                </div>
                <div class="col-lg-2 col-md-3">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        @foreach(\App\Models\AppFeedbackReport::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2">
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua kategori</option>
                        @foreach(\App\Models\AppFeedbackReport::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2">
                    <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua prioritas</option>
                        @foreach(\App\Models\AppFeedbackReport::PRIORITIES as $value => $label)
                            <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($canManage && $divisions->isNotEmpty())
                    <div class="col-lg-2 col-md-3">
                        <select name="division" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua divisi</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division }}" @selected(request('division') === $division)>{{ $division }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-lg-auto d-flex gap-2">
                    <button class="btn btn-sm btn-primary"><i class="ri-search-line me-1"></i>Cari</button>
                    <a href="{{ route('user.feedback.index', ['userId' => $userId]) }}" class="btn btn-sm btn-light" title="Reset">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Laporan</th>
                            <th>Kategori</th>
                            @if($canManage)
                                <th>Pelapor / Divisi</th>
                            @endif
                            <th>Prioritas</th>
                            <th>Status</th>
                            <th>Bukti</th>
                            <th>Tanggal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            @php
                                $statusColor = $statusColors[$report->status] ?? 'secondary';
                                $priorityColor = $priorityColors[$report->priority] ?? 'secondary';
                            @endphp
                            <tr>
                                <td style="min-width:250px">
                                    <div class="fw-semibold">{{ $report->title }}</div>
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($report->description, 100) }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $report->categoryLabel() }}</span></td>
                                @if($canManage)
                                    <td>
                                        <div class="fw-medium">{{ $report->submitter?->name ?? '—' }}</div>
                                        <div class="text-muted small">
                                            {{ $report->division ?: ($report->jabatan ?: '—') }}
                                            @if($report->school)
                                                · {{ $report->school->name }}
                                            @endif
                                        </div>
                                    </td>
                                @endif
                                <td>
                                    <span class="badge bg-{{ $priorityColor }}-subtle text-{{ $priorityColor }}">
                                        {{ $report->priorityLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                        {{ $report->statusLabel() }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($report->attachments_count > 0)
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <i class="ri-attachment-2 me-1"></i>{{ $report->attachments_count }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted small text-nowrap">{{ $report->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('user.feedback.show', ['userId' => $userId, 'id' => $report->id]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ri-eye-line me-1"></i>Lihat
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 8 : 7 }}" class="text-center py-5 text-muted">
                                    <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                                    Belum ada laporan pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
@endsection
