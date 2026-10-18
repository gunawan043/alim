@extends('layouts.master')
@section('title', $plan->title)

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId]) }}">RPP</a> @endslot
        @slot('li_2') Detail @endslot
        @slot('title') {{ $plan->title }} @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
            <div><h5 class="card-title mb-1">{{ $plan->title }}</h5><div class="text-muted small">{{ $plan->type === 'iqro' ? 'RPP Iqro’' : 'RPP Tahfizh' }} · {{ $plan->gradeLevel?->name ?? 'Semua kelas' }} · {{ ucfirst($plan->semester) }} {{ $plan->academicYear?->name }} · {{ $plan->kbm_month ?? 'Bulan KBM —' }}</div></div>
            <div class="d-flex gap-2"><a href="{{ route('user.tahfidz.rpp.print', ['userId' => $userId, 'id' => $plan->id]) }}" target="_blank" class="btn btn-outline-dark"><i class="ri-printer-line me-1"></i>Cetak</a><a href="{{ route('user.tahfidz.rpp.edit', ['userId' => $userId, 'id' => $plan->id]) }}" class="btn btn-warning"><i class="ri-edit-line me-1"></i>Edit</a><a href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId]) }}" class="btn btn-light">Kembali</a></div>
        </div>
        <div class="card-body">
            @if($plan->monthly_target)<div class="mb-3"><strong>Target Bulanan:</strong> {!! nl2br(e($plan->monthly_target)) !!}</div>@endif
            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    @if($plan->type === 'iqro')
                        <tr><th>No</th><th>Bagian</th><th>Kegiatan Guru</th><th>Kegiatan Siswa</th><th>Alokasi</th></tr>
                    @else
                        <tr><th>Pekan</th><th>Hari</th><th>Target Pekanan</th><th>Target Harian</th><th>Keterangan</th></tr>
                    @endif
                </thead>
                <tbody>
                    @forelse($plan->items as $i => $item)
                        @if($plan->type === 'iqro')
                            <tr><td class="text-center">{{ $i+1 }}</td><td>{{ $item->section ?? '—' }}</td><td class="small">{!! nl2br(e($item->teacher_activity)) !!}</td><td class="small">{!! nl2br(e($item->student_activity)) !!}</td><td class="text-center">{{ $item->duration_minutes ? $item->duration_minutes.' menit' : '—' }}</td></tr>
                        @else
                            <tr><td class="text-center">{{ $item->week ?? '—' }}</td><td>{{ $item->learning_day ?? '—' }}</td><td>{{ $item->week_target ?? '—' }}</td><td class="small">{{ $item->daily_target ?? '—' }}</td><td class="small">{{ $item->notes ?? '—' }}</td></tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada butir RPP.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
@endsection
