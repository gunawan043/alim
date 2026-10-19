@extends('layouts.master')
@section('title', 'Progres Juz & Rekap Capaian')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Progres Juz & Rekap Capaian @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Santri', $stats['students'], 'ri-user-heart-line', 'primary', 'Anggota aktif'],
            ['Juz Selesai', $stats['juz'], 'ri-check-double-line', 'success', 'Ziyadah + tasmi'],
            ['Rata Nilai', $stats['average'] !== null ? number_format((float)$stats['average'], 2) : '—', 'ri-bar-chart-line', 'info', 'Setoran terverifikasi'],
            ['Rata Target', $stats['target'] !== null ? number_format((float)$stats['target'], 1).'%' : '—', 'ri-focus-3-line', 'warning', 'Capaian target'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ $value }}</h3></div></div><p class="text-muted mb-0 stat-label">{{ $note }}</p></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-line-chart-line text-primary me-1"></i>Progres Juz Santri</h5><p class="text-muted mb-0 small">Diambil dari setoran ziyadah terverifikasi dan rekap capaian.</p></div><a href="{{ route('user.tahfidz.targets.index', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-focus-3-line me-1"></i>Target Hafalan</a></div>
        <div class="card-body border-bottom"><form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">Halaqah</label><select name="group_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(request('group_id')===$group->id)>{{ $group->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label small">Santri</label><select name="student_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(request('student_id')===$student->id)>{{ $student->name }} ({{ $student->nis }})</option>@endforeach</select></div>
        </form></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 tahfidz-table"><thead class="table-light"><tr><th>Santri</th><th>Halaqah</th><th class="text-center">Juz</th><th class="text-center">Halaman</th><th class="text-center">Rata Nilai</th><th class="text-center">Target</th><th class="text-center">Posisi</th><th>Detail Juz</th></tr></thead><tbody>
            @forelse($rows as $row)
                <tr><td><a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$row['member']->student_id]) }}" class="fw-medium text-decoration-none">{{ $row['member']->student?->name }}</a><div class="text-muted small">{{ $row['member']->student?->nis }}</div></td><td>{{ $row['member']->group?->name }}</td><td class="text-center"><span class="badge bg-success-subtle text-success">{{ $row['completed'] }}</span><span class="text-muted small">/{{ $row['total_juz'] }}</span></td><td class="text-center">{{ $row['halaman'] !== null ? number_format((float)$row['halaman'], 1) : '—' }}</td><td class="text-center">{{ $row['average'] !== null ? number_format((float)$row['average'], 2) : '—' }}</td><td class="text-center">{{ $row['target'] !== null ? number_format((float)$row['target'], 1).'%' : '—' }}</td><td class="text-center">{{ $row['last_juz'] ? 'Juz '.$row['last_juz'] : '—' }}</td><td>@if($row['rows']->isNotEmpty())<div class="d-flex flex-wrap gap-1">@foreach($row['rows'] as $juz)<span class="badge {{ in_array($juz->status,['selesai_ziyadah','sudah_tasmi'],true) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">J{{ $juz->juz_number }} · {{ number_format((float)$juz->percentage,0) }}%</span>@endforeach</div>@else<span class="text-muted small">Belum ada progres.</span>@endif</td></tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">Belum ada data progres.</td></tr>@endforelse
        </tbody></table></div>
    </div>
@endsection
