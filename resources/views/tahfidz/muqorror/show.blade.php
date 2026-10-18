@extends('layouts.master')
@section('title', $plan->title)

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $monthNames = [1=>'Juli',2=>'Agustus',3=>'September',4=>'Oktober',5=>'November',6=>'Desember',7=>'Januari',8=>'Februari',9=>'Maret',10=>'April',11=>'Mei',12=>'Juni'];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}">Muqorror</a> @endslot
        @slot('li_2') Detail @endslot
        @slot('title') {{ $plan->title }} @endslot
    @endcomponent

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-1">{{ $plan->title }}</h5>
                <div class="text-muted small">
                    {{ strtoupper($plan->jenjang ?? '—') }} · {{ $plan->gradeLevel?->name ?? 'Semua kelas' }} · {{ $plan->package?->name ?? 'Tanpa paket' }} · {{ ucfirst($plan->semester) }} {{ $plan->academicYear?->name }}
                    · {{ $plan->school?->name ?? 'Departemen / semua sekolah' }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.muqorror.print', ['userId' => $userId, 'id' => $plan->id]) }}" target="_blank" class="btn btn-outline-dark"><i class="ri-printer-line me-1"></i>Cetak</a>
                <a href="{{ route('user.tahfidz.muqorror.edit', ['userId' => $userId, 'id' => $plan->id]) }}" class="btn btn-warning"><i class="ri-edit-line me-1"></i>Edit</a>
                <a href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}" class="btn btn-light">Kembali</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Bulan</th><th>Target Hafalan</th><th>Halaman</th><th>Baris/Bulan</th><th>Baris/Hari</th><th>Hari Aktif</th><th>Dari</th><th>Sampai</th><th>STS/SAS</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                    @forelse($plan->months as $month)
                        <tr>
                            <td class="fw-semibold">{{ $monthNames[$month->month] ?? $month->month }}</td>
                            <td>{{ $month->target_juz_start ? 'Juz '.$month->target_juz_start : '—' }}{{ $month->target_juz_end ? ' s.d. '.$month->target_juz_end : '' }}</td>
                            <td>{{ $month->target_pages ?? '—' }}</td>
                            <td>{{ $month->target_lines_month ?? '—' }}</td>
                            <td>{{ $month->target_lines_day ?? '—' }}</td>
                            <td>{{ $month->active_days ?? '—' }}</td>
                            <td>{{ $month->from_surah_name ?: '—' }} {{ $month->from_ayat ? ':'.$month->from_ayat : '' }}</td>
                            <td>{{ $month->to_surah_name ?: '—' }} {{ $month->to_ayat ? ':'.$month->to_ayat : '' }}</td>
                            <td>{!! $month->exam_type ? '<span class="badge bg-warning-subtle text-warning">'.strtoupper($month->exam_type).'</span>' : '—' !!}</td>
                            <td class="small">{{ $month->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Belum ada target bulanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
