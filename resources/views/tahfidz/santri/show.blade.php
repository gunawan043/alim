@extends('layouts.master')
@section('title', 'Detail Tahfidz — '.$student->name)
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Detail Santri @endslot @slot('title') {{ $student->name }} @endslot @endcomponent

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-1">{{ $student->name }}</h5>
                <div class="text-muted small">{{ $student->nis }} · {{ $student->currentClassHistory?->studyGroup?->name ?? $member->group?->studyGroup?->name ?? '—' }} · {{ $student->school?->name }}</div>
                <div class="small mt-1">Halaqah: <strong>{{ $member->group?->name }}</strong> · Guru: {{ $member->group?->teacher?->name ?? '—' }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.input.index', ['userId'=>$userId,'student_id'=>$student->id]) }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Input Hafalan</a>
                <a href="{{ url()->previous() }}" class="btn btn-light">Kembali</a>
            </div>
        </div>
    </div>

    <div class="row tahfidz-stat mt-1">
        @foreach([
            ['Setoran', $stats['setoran'], 'ri-file-list-3-line', 'primary', $stats['verified'].' terverifikasi'],
            ['Juz Selesai', $stats['juz'], 'ri-check-double-line', 'success', 'Ziyadah + tasmi'],
            ['Rata Nilai', $stats['average'] !== null ? number_format((float) $stats['average'], 2) : '—', 'ri-bar-chart-line', 'info', 'Setoran terverifikasi'],
            ['Capaian Target', $stats['target'] !== null ? number_format((float) $stats['target'], 1).'%' : '—', 'ri-focus-3-line', 'warning', $stats['certificates'].' syahadah'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                    <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ $value }}</h3></div>
                </div>
                <p class="text-muted mb-0 stat-label">{{ $note }}</p>
            </div></div></div>
        @endforeach
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-line-chart-line text-primary me-1"></i>Progres Juz</h5></div>
                <div class="card-body">
                    @if($progress->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($progress as $row)
                                <span class="badge {{ in_array($row->status, ['selesai_ziyadah','sudah_tasmi'], true) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}" title="{{ $row->halaman_completed }}/{{ $row->total_halaman_juz }} halaman">J{{ $row->juz_number }} · {{ number_format((float) $row->percentage, 0) }}%</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">Belum ada progres juz.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-focus-3-line text-primary me-1"></i>Target Terakhir</h5></div>
                <div class="card-body">
                    @if($targets->isNotEmpty())
                        @php $target = $targets->last(); @endphp
                        <div class="row g-2">
                            <div class="col-6"><div class="text-muted small">Semester</div><div class="fw-semibold">{{ ucfirst($target->semester) }} · Bulan {{ $target->target_bulan }}</div></div>
                            <div class="col-6"><div class="text-muted small">Juz</div><div class="fw-semibold">{{ $target->juz_start ?? '—' }}{{ $target->juz_end ? '–'.$target->juz_end : '' }}</div></div>
                            <div class="col-6"><div class="text-muted small">Halaman</div><div class="fw-semibold">{{ $target->target_halaman ?? '—' }}</div></div>
                            <div class="col-6"><div class="text-muted small">Hadits</div><div class="fw-semibold">{{ $target->target_hadits ?? 0 }}</div></div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Target belum diatur.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><h5 class="card-title mb-0"><i class="ri-history-line text-primary me-1"></i>Riwayat Setoran</h5><span class="badge bg-primary-subtle text-primary">{{ $history->count() }}</span></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Materi</th><th class="text-center">Halaman</th><th class="text-center">Nilai</th><th>Status</th><th>Verifikasi</th><th>Guru</th></tr></thead>
                <tbody>
                    @forelse($history as $setoran)
                        <tr>
                            <td>{{ $setoran->setoran_date?->format('d/m/Y') }}</td>
                            <td>{{ $setoran->setoran_type_label }}</td>
                            <td class="small">{{ $setoran->juz ? 'Juz '.$setoran->juz : '' }} {{ $setoran->surah_start_name }}{{ $setoran->ayat_start ? ':'.$setoran->ayat_start : '' }}{{ $setoran->surah_end_name ? ' — '.$setoran->surah_end_name : '' }}{{ $setoran->ayat_end ? ':'.$setoran->ayat_end : '' }}</td>
                            <td class="text-center">{{ $setoran->jumlah_halaman ?? '—' }}</td>
                            <td class="text-center fw-semibold">{{ $setoran->nilai_setoran ?? '—' }}</td>
                            <td><span class="badge {{ $setoran->status==='lulus' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $setoran->status_label }}</span></td>
                            <td><span class="badge {{ $setoran->verification_status==='verified' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $setoran->verification_status }}</span></td>
                            <td class="small">{{ $setoran->teacher?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada setoran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-calendar-check-line text-primary me-1"></i>Mutaba’ah Terakhir</h5></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th class="text-center">Tilawah</th><th class="text-center">Tikror</th><th>Catatan</th></tr></thead><tbody>
                    @forelse($mutabaah as $record)
                        <tr><td>{{ $record->record_date?->format('d/m/Y') }}</td><td class="text-center">{{ $record->tilawah_halaman }}</td><td class="text-center">{{ $record->tikror_mandiri_halaman }}</td><td class="small text-muted">{{ \Illuminate\Support\Str::limit($record->catatan_musyrif, 60) }}</td></tr>
                    @empty<tr><td colspan="4" class="text-center text-muted py-3">Belum ada mutaba’ah.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-award-line text-primary me-1"></i>Tasmi’ & Syahadah</h5></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Sesi</th><th class="text-center">Nilai</th><th>Predikat</th><th>Syahadah</th></tr></thead><tbody>
                    @forelse($tasmi as $participant)
                        <tr><td>{{ $participant->session?->session_name }}</td><td class="text-center">{{ $participant->score?->nilai_akhir !== null ? number_format((float) $participant->score->nilai_akhir, 2) : '—' }}</td><td>{{ $participant->score?->predikat_label ?? '—' }}</td><td>@if($participant->certificate)<a href="{{ route('user.tahfidz.tasmi.certificate.print', ['userId'=>$userId,'certificateId'=>$participant->certificate->id]) }}" target="_blank" class="small">{{ $participant->certificate->certificate_number }}</a>@else<span class="text-muted small">Belum</span>@endif</td></tr>
                    @empty<tr><td colspan="4" class="text-center text-muted py-3">Belum ada tasmi’.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
@endsection
