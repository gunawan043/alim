@extends('layouts.master')
@section('title') {{ $decree->decree_number }} @endsection

@section('css')
<style>
    /* Tabel matriks: lebar kolom mengikuti isi teks */
    .matrix-table {
        width: auto;
        table-layout: auto;
    }
    .matrix-table th,
    .matrix-table td {
        white-space: nowrap;
        width: 1%;
        vertical-align: top;
        padding: 0.5rem 0.6rem;
    }
    /* Kolom Mapel & Tugas Tambahan perlu bisa membungkus jika teks panjang */
    .matrix-table th.col-mapel,
    .matrix-table th.col-tugas,
    .matrix-table td.col-mapel,
    .matrix-table td.col-tugas {
        white-space: normal;
        min-width: 160px;
        width: auto;
    }
    /* Baris guru */
    .matrix-row-teacher > td {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }
    .matrix-row-teacher .subject-list,
    .matrix-row-teacher .task-list {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    /* Baris lanjutan mapel (hanya jika > 2 mapel) */
    .matrix-row-subject > td {
        background-color: rgba(0,0,0,0.015);
        font-size: 0.85rem;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">Surat Keputusan</a> @endslot
        @slot('title') {{ $decree->decree_number }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('matrix_warnings') && count(session('matrix_warnings')))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>Catatan penyimpanan matriks:</strong>
            <ul class="mb-0 mt-1">
                @foreach(session('matrix_warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info SK --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $decree->title }}</h5>
                    <div class="d-flex gap-2">
                        @if($decree->status === 'active')
                            <span class="badge bg-success-subtle text-success">Aktif</span>
                        @elseif($decree->status === 'archived')
                            <span class="badge bg-secondary-subtle text-secondary">Arsip</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Draft</span>
                        @endif
                        <a href="{{ route('user.institution-decrees.edit', ['userId' => $userId, 'id' => $decree->id]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-pencil-line"></i> Edit SK &amp; Matriks
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0" style="max-width:700px">
                        <tr>
                            <th class="text-muted" style="width:200px;">Nomor SK</th>
                            <td><code>{{ $decree->decree_number }}</code></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Jenis SK</th>
                            <td>{{ $decree->decree_type }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tahun Ajaran</th>
                            <td>{{ $decree->academicYear?->name ?? '-' }}@if($decree->academicYear?->semester_text) ({{ $decree->academicYear->semester_text }})@endif</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Sekolah</th>
                            <td>{{ $decree->school?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tanggal Dikeluarkan</th>
                            <td>{{ ($decree->issued_date?->translatedFormat('d F Y')) ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tanggal Efektif</th>
                            <td>{{ ($decree->effective_date?->translatedFormat('d F Y')) ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Penandatangan</th>
                            <td>
                                @if($decree->signer)
                                    {{ $decree->signer->name }} — <span class="text-muted">{{ $decree->signed_position }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Deskripsi</th>
                            <td>{{ $decree->description ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Matriks Pembagian Tugas Mengajar (tampilan saja) --}}
    @if($decree->decree_type === 'SK Pembagian Tugas' || count($matrixRows))
    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="alert alert-info mb-3 py-2 px-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <div class="text-muted text-uppercase fw-bold mb-1" style="font-size:11px;">
                            Lampiran I. Surat Keputusan Kepala
                        </div>
                        <div class="fw-bold">{{ $decree->school?->name ?? '-' }}</div>
                        <div class="mt-1" style="font-size:11px;">
                            NOMOR&nbsp;&nbsp;: <strong>{{ $decree->decree_number }}</strong> &nbsp;|&nbsp;
                            TANGGAL : <strong>{{ $decree->issued_date?->translatedFormat('d F Y') ?? '-' }}</strong> &nbsp;|&nbsp;
                            TA <strong>{{ $decree->academicYear?->name ?? '-' }}</strong>
                        </div>
                        <div class="mt-1" style="font-size:11px;">
                            TENTANG : <strong>{{ $decree->title }}</strong>
                        </div>
                    </div>
                    <div class="text-end text-muted" style="font-size:10px;">
                        Matriks Pembagian Tugas Mengajar
                        @if($matrixIsSnapshot && $matrixSnapshotAt)
                            <div class="mt-1"><span class="badge bg-success-subtle text-success">Snapshot {{ $matrixSnapshotAt->translatedFormat('d M Y H:i') }}</span></div>
                        @else
                            <div class="mt-1"><span class="badge bg-warning-subtle text-warning">Data live</span></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex flex-wrap flex-lg-nowrap justify-content-between align-items-center gap-2 overflow-hidden">
                    <div class="d-flex flex-nowrap gap-2 align-items-center flex-shrink-0">
                        <span class="badge bg-primary-subtle text-primary text-nowrap">
                            <i class="ri-group-line me-1"></i>{{ count($matrixRows) }} GTK
                        </span>
                        <span class="badge bg-info-subtle text-info text-nowrap">
                            <i class="ri-stack-line me-1"></i>{{ $studyGroups->count() }} Rombel
                        </span>
                    </div>

                    <div class="d-flex flex-wrap flex-lg-nowrap gap-2 align-items-center flex-grow-1 justify-content-lg-end min-w-0">
                        <button type="button" class="btn btn-md btn-soft-secondary text-nowrap flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#printSettingsModal">
                            <i class="ri-printer-settings-line me-1"></i><span class="d-none d-lg-inline">Pengaturan Cetak</span>
                        </button>
                        <a href="{{ route('user.institution-decrees.print', ['userId' => $userId, 'id' => $decree->id]) }}"
                           class="btn btn-md btn-soft-secondary text-nowrap flex-shrink-0" target="_blank">
                            <i class="ri-printer-line me-1"></i><span class="d-none d-lg-inline">Cetak SK</span>
                        </a>
                        <a href="{{ route('user.institution-decrees.edit', ['userId' => $userId, 'id' => $decree->id]) }}"
                           class="btn btn-md btn-success text-nowrap flex-shrink-0">
                            <i class="ri-edit-2-line me-1"></i>Edit SK &amp; Matriks
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="alert alert-info border-0 rounded-0 py-2 mb-0">
                        <i class="ri-information-line me-1"></i>
                        Tampilan ini hanya untuk melihat. Nama, jabatan, mapel, dan tugas tambahan berada pada baris yang sama; baris bertambah mengikuti jumlah mapel/tugas terbanyak.
                    </div>

                    @if($studyGroups->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="ri-file-list-3-line fs-1 mb-2 d-block"></i>
                            <p>Tidak ada data kelas untuk sekolah ini.</p>
                        </div>
                    @else
                        <div class="table-responsive" style="overflow-x:auto;">
                            <table class="table table-bordered table-hover table-md align-middle mb-0 table-freeze">
                                <thead class="table-light text-center align-middle position-sticky top-0 z-2">
                                    <tr>
                                        <th rowspan="2" class="text-nowrap">No</th>
                                        <th rowspan="2" class="text-nowrap">Nama</th>
                                        <th rowspan="2" class="text-nowrap">Jabatan</th>
                                        <th rowspan="2" class="text-wrap" style="min-width:160px">Mapel</th>
                                        <th rowspan="2" class="text-wrap" style="min-width:160px">Tugas Tambahan</th>
                                        @foreach($sortedGrades as $grade)
                                            <th colspan="{{ count($grade['groups']) }}" class="text-nowrap">{{ $grade['name'] }}</th>
                                        @endforeach
                                        <th rowspan="2" class="text-nowrap">JP</th>
                                        <th rowspan="2" class="text-nowrap">Total Jam</th>
                                    </tr>
                                    <tr>
                                        @foreach($sortedGrades as $grade)
                                            @foreach($grade['groups'] as $sg)
                                                <th class="text-nowrap">{{ $sg['name'] }}</th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($matrixRows as $index => $row)
                                        @php
                                            $subjects = $row['subjects'] ?? [];
                                            $tasks = $row['tasks'] ?? [];
                                            $rowCount = max(count($subjects), count($tasks), 1);
                                        @endphp

                                        @for($i = 0; $i < $rowCount; $i++)
                                            @php
                                                $subjectRow = $subjects[$i] ?? null;
                                                $task = $tasks[$i] ?? null;
                                            @endphp
                                            <tr>
                                                @if($i === 0)
                                                    <td rowspan="{{ $rowCount }}" class="text-center fw-semibold">{{ $index + 1 }}</td>
                                                    <td rowspan="{{ $rowCount }}" class="fw-semibold text-nowrap">
                                                        {{ $row['teacher_name'] }}
                                                        @if($row['external'])
                                                            <div class="small text-info fw-normal">
                                                                <i class="ri-building-2-line me-1"></i>
                                                                {{ $row['home_work_unit'] ? ' · '.$row['home_work_unit'] : '' }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td rowspan="{{ $rowCount }}" class="text-wrap">
                                                        <span class="badge bg-secondary-subtle text-secondary">{{ $row['position'] }}</span>
                                                    </td>
                                                @endif

                                                <td class="text-wrap">
                                                    @if($subjectRow)
                                                        <span class="fw-semibold">{{ $subjectRow['subject_name'] }}</span>
                                                        @if($subjectRow['is_coordinator'])
                                                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:10px;">Koordinator</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="text-wrap">
                                                    @if($task)
                                                        <span class="badge bg-warning-subtle text-warning me-1 mb-1">
                                                            {{ $task['name'] }}
                                                            @if($task['study_group_name']) · {{ $task['study_group_name'] }} @endif
                                                            @if($task['weekly_hours']) · {{ (int) $task['weekly_hours'] }} JP @endif
                                                        </span>
                                                    @endif
                                                </td>
                                                @foreach($sortedGrades as $grade)
                                                    @foreach($grade['groups'] as $sg)
                                                        <td class="text-center">
                                                            @if($subjectRow && !empty($subjectRow['hours'][$sg['id']]))
                                                                <span class="small fw-semibold text-success text-nowrap">{{ (int) $subjectRow['hours'][$sg['id']] }} JP</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                @endforeach
                                                <td class="text-center fw-semibold">{{ $subjectRow ? (int) $subjectRow['total'] : 0 }}</td>
                                                @if($i === 0)
                                                    <td rowspan="{{ $rowCount }}" class="text-center fw-bold">{{ (int) $row['total_hours'] }}</td>
                                                @endif
                                            </tr>
                                        @endfor
                                    @empty
                                        <tr>
                                            <td colspan="{{ 5 + $studyGroups->count() + 2 }}" class="text-center py-4 text-muted">
                                                <i class="ri-team-line fs-3 d-block mb-2"></i>
                                                Belum ada GTK pada satuan kerja ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="ri-information-line me-1"></i>
                        Isi tabel dibekukan sebagai snapshot. Gunakan tombol Edit SK &amp; Matriks untuk mengubah data.
                    </small>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('institution-decrees._print_settings_modal')
@endsection