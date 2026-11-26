@extends('layouts.master')
@section('title', 'Input Setoran Harian')
@push('styles')
<style>
    .sh-table th {
        font-size: .76rem; font-weight: 600; color: var(--bs-secondary-color);
        padding: .65rem 1rem; white-space: nowrap;
    }
    .sh-table td { padding: .7rem 1rem; vertical-align: middle; }
    .sh-table tbody tr:last-child td { border-bottom: 0; }
    .sh-stat { font-size: .72rem; color: var(--bs-secondary-color); }
    .sh-actions .btn { min-width: 96px; }
    @media (max-width: 575.98px) {
        .sh-actions { display: flex; gap: .4rem; }
        .sh-actions .btn { min-width: 0; flex: 1 1 0; }
    }
</style>
@endpush
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $selectedGroupId = $group?->id;
        $dateValue = $date ?? now()->toDateString();
        $filledCount = $rows->filter(fn ($row) => $row['ziyadah'] || $row['rabt'])->count();
        $ziyadahCount = $rows->filter(fn ($row) => $row['ziyadah'])->count();
        $rabtCount = $rows->filter(fn ($row) => $row['rabt'])->count();
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Input Setoran Harian @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-warning alert-dismissible fade show"><i class="ri-information-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Input Setoran Harian</h5>
                    <p class="text-muted mb-0 small">
                        Pilih <strong>Ziyadah</strong> (hafalan baru) atau <strong>Rabt</strong> (mengikat hafalan lama)
                        pada baris santri. Halaman input dibuka sesuai jenis yang dipilih.
                    </p>
                </div>
                <div class="col-sm-auto">
                    <a href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId, 'group_id' => $selectedGroupId]) }}"
                       class="btn btn-soft-secondary"><i class="ri-history-line me-1"></i>Riwayat Setoran</a>
                </div>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Halaqah</label>
                    <select name="group_id" class="form-select" onchange="this.form.submit()" @disabled($groups->isEmpty())>
                        @forelse($groups as $g)
                            <option value="{{ $g->id }}" @selected($selectedGroupId === $g->id)>{{ $g->name }} — {{ $g->school?->name }}</option>
                        @empty
                            <option>Tidak ada halaqah</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Tanggal Setoran</label>
                    <input type="date" name="date" value="{{ $dateValue }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="col-md-4 d-flex flex-wrap justify-content-md-end gap-2">
                    <span class="badge bg-success-subtle text-success align-self-center">Ziyadah {{ $ziyadahCount }}</span>
                    <span class="badge bg-primary-subtle text-primary align-self-center">Rabt {{ $rabtCount }}</span>
                    <span class="badge bg-light text-body align-self-center">{{ $filledCount }} / {{ $rows->count() }} santri</span>
                </div>
            </form>
        </div>

        @if(! $group)
            <div class="card-body text-center text-muted py-5">
                <i class="ri-team-line fs-2 d-block mb-2"></i>
                Belum ada halaqah yang dapat Anda input. Hubungi koordinator Tahfidz sekolah.
            </div>
        @elseif($rows->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="ri-user-search-line fs-2 d-block mb-2"></i>
                Belum ada santri aktif di halaqah ini.
            </div>
        @else
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover sh-table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:52px">#</th>
                                <th>Santri</th>
                                <th class="text-center">Juz</th>
                                <th>Setoran Hari Ini</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $index => $row)
                                @php
                                    $student = $row['student'];
                                    $ziyadah = $row['ziyadah'];
                                    $rabt = $row['rabt'];
                                    $hasAny = $ziyadah || $rabt;
                                    $entryUrl = fn (string $jenis) => route('user.tahfidz.setoran-bulk.entry', [
                                        'userId' => $userId, 'studentId' => $student->id,
                                        'group_id' => $group->id, 'date' => $dateValue, 'jenis' => $jenis,
                                    ]);
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs flex-shrink-0">
                                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                    {{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}
                                                </div>
                                            </div>
                                            <div class="ms-2">
                                                <div class="fw-semibold">{{ $student->name }}</div>
                                                <div class="sh-stat">{{ $student->nis }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $rabt?->juz ?? $ziyadah?->juz ?? '—' }}</td>
                                    <td>
                                        @if($hasAny)
                                            <div class="small">
                                                @if($ziyadah)
                                                    <div>
                                                        <span class="badge bg-success-subtle text-success me-1">Ziyadah</span>
                                                        {{ $ziyadah->surah_start_name ?? '—' }}{{ $ziyadah->ayat_start ? ':'.$ziyadah->ayat_start : '' }}
                                                        @if($ziyadah->nilai_setoran !== null)<span class="text-muted">· nilai {{ $ziyadah->nilai_setoran }}</span>@endif
                                                    </div>
                                                @endif
                                                @if($rabt)
                                                    <div class="{{ $ziyadah ? 'mt-1' : '' }}">
                                                        <span class="badge bg-primary-subtle text-primary me-1">Rabt</span>
                                                        {{ $rabt->surah_start_name ?? '—' }}{{ $rabt->ayat_start ? ':'.$rabt->ayat_start : '' }}
                                                        @if($rabt->nilai_setoran !== null)<span class="text-muted">· nilai {{ $rabt->nilai_setoran }}</span>@endif
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted small">Belum ada setoran</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $hasAny ? 'success' : 'secondary' }}-subtle text-{{ $hasAny ? 'success' : 'secondary' }}">
                                            {{ $hasAny ? 'Terisi' : 'Belum' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="sh-actions d-inline-flex gap-2">
                                            <a href="{{ $entryUrl('ziyadah') }}"
                                               class="btn btn-sm {{ $ziyadah ? 'btn-soft-success' : 'btn-success' }}">
                                                <i class="ri-add-line me-1"></i>{{ $ziyadah ? 'Edit Ziyadah' : 'Ziyadah' }}
                                            </a>
                                            <a href="{{ $entryUrl('robet') }}"
                                               class="btn btn-sm {{ $rabt ? 'btn-soft-primary' : 'btn-primary' }}">
                                                <i class="ri-link me-1"></i>{{ $rabt ? 'Edit Rabt' : 'Rabt' }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
