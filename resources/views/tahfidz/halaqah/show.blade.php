@extends('layouts.master')
@section('title', $group->name)
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $juzSelesai = $activeMembers->sum(fn ($member) => ($juzProgress[$member->student_id] ?? collect())->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count());
        $targetAverage = $recaps->pluck('pencapaian_target_persen')->filter(fn ($value) => $value !== null)->avg();
        $activeCount = $activeMembers->count();
        $capacity = $group->max_members;
        $fillPercent = $capacity ? min(100, (int) round($activeCount / $capacity * 100)) : null;
        $scheduleCount = $group->schedules->where('is_active', true)->count();
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah</a> @endslot @slot('li_2') Detail @endslot @slot('title') {{ $group->name }} @endslot @endcomponent

    <style>
        .hq-hero { border-left: 4px solid var(--bs-primary); }
        .hq-hero .hq-meta { display: flex; flex-wrap: wrap; gap: .35rem .9rem; }
        .hq-hero .hq-meta span { display: inline-flex; align-items: center; gap: .3rem; color: var(--bs-secondary-color); font-size: .8125rem; }
        .hq-stat { border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: .75rem .9rem; height: 100%; background: var(--bs-body-bg); }
        .hq-stat .hq-stat-label { font-size: .6875rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); font-weight: 600; }
        .hq-stat .hq-stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .hq-stat .hq-stat-note { font-size: .7rem; color: var(--bs-secondary-color); }
        .hq-help { font-size: .8125rem; color: var(--bs-secondary-color); margin: .15rem 0 0; }
        .hq-section { display: flex; align-items: flex-start; gap: .6rem; }
        .hq-section .hq-section-icon { flex: 0 0 auto; width: 2.1rem; height: 2.1rem; border-radius: .5rem; display: flex; align-items: center; justify-content: center; background: var(--bs-primary-bg-subtle); color: var(--bs-primary); }
        .hq-toolbar { display: flex; flex-wrap: wrap; gap: .5rem; }
        .hq-toolbar .btn { white-space: nowrap; }
        .hq-member-table td, .hq-member-table th { white-space: nowrap; }
        .hq-member-table td:first-child { white-space: normal; min-width: 180px; }
        @media (max-width: 767.98px) {
            .hq-toolbar { width: 100%; }
            .hq-toolbar .btn { flex: 1 1 calc(50% - .5rem); }
        }
    </style>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- ── Ringkasan halaqah ─────────────────────────────────────────── --}}
    <div class="card hq-hero">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="card-title mb-0">{{ $group->name }}</h5>
                        {!! $group->is_active
                            ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                            : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>' !!}
                    </div>
                    <div class="hq-meta">
                        <span><i class="ri-building-line"></i>{{ $group->school?->name ?? '—' }}</span>
                        <span><i class="ri-graduation-cap-line"></i>{{ $group->gradeLevel?->name ?? $group->studyGroup?->name ?? 'Tanpa kelas' }}</span>
                        <span><i class="ri-user-star-line"></i>Guru: <strong>{{ $group->teacher?->name ?? '—' }}</strong></span>
                        <span><i class="ri-shield-user-line"></i>Koordinator: {{ $group->coordinator?->name ?? '—' }}</span>
                        <span><i class="ri-calendar-line"></i>{{ $group->academicYear?->name ?? '—' }}</span>
                        <span><i class="ri-venus-mars-line"></i>{{ $group->gender === 'putri' ? 'Putri' : 'Putra' }}</span>
                    </div>
                    @if($group->notes)
                        <p class="hq-help mt-2 mb-0"><i class="ri-sticky-note-line me-1"></i>{{ $group->notes }}</p>
                    @endif
                </div>
                <div class="hq-toolbar align-self-start">
                    <a href="{{ route('user.tahfidz.setoran.create', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-primary"><i class="ri-add-line me-1"></i>Input Setoran</a>
                    <a href="{{ route('user.tahfidz.progress.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-outline-primary"><i class="ri-line-chart-line me-1"></i>Progres &amp; Rekap</a>
                    <a href="{{ route('user.tahfidz.targets.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-outline-secondary"><i class="ri-focus-3-line me-1"></i>Target Hafalan</a>
                    @if($canTarget)
                        <form method="POST" action="{{ route('user.tahfidz.halaqah.targets.apply', ['userId'=>$userId,'id'=>$group->id]) }}" class="d-inline" onsubmit="return confirm('Terapkan target muqorror ke seluruh santri aktif?')">
                            @csrf
                            <button class="btn btn-outline-success"><i class="ri-magic-line me-1"></i>Terapkan Muqorror</button>
                        </form>
                    @endif
                    @if($canManage)
                        <a href="{{ route('user.tahfidz.halaqah.edit', ['userId'=>$userId,'id'=>$group->id]) }}" class="btn btn-warning"><i class="ri-edit-line me-1"></i>Edit</a>
                    @endif
                    <a href="{{ route('user.tahfidz.halaqah.index', ['userId'=>$userId]) }}" class="btn btn-light">Kembali</a>
                </div>
            </div>

            <div class="row g-2 mt-3">
                <div class="col-6 col-xl-3">
                    <div class="hq-stat">
                        <div class="hq-stat-label">Anggota Aktif</div>
                        <div class="hq-stat-value">{{ $activeCount }}</div>
                        <div class="hq-stat-note">
                            @if($capacity)
                                dari kapasitas {{ $capacity }} santri
                            @else
                                tidak ada batas kapasitas
                            @endif
                        </div>
                        @if($fillPercent !== null)
                            <div class="progress mt-2" style="height:4px">
                                <div class="progress-bar {{ $fillPercent >= 100 ? 'bg-warning' : 'bg-primary' }}" style="width:{{ $fillPercent }}%"></div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="hq-stat">
                        <div class="hq-stat-label">Total Setoran</div>
                        <div class="hq-stat-value">{{ number_format($group->setorans_count) }}</div>
                        <div class="hq-stat-note">Seluruh setoran yang tercatat</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="hq-stat">
                        <div class="hq-stat-label">Juz Selesai</div>
                        <div class="hq-stat-value text-success">{{ $juzSelesai }}</div>
                        <div class="hq-stat-note">Sudah ziyadah / tasmi</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="hq-stat">
                        <div class="hq-stat-label">Rata Capaian Target</div>
                        <div class="hq-stat-value">{{ $targetAverage !== null ? number_format((float) $targetAverage, 1).'%' : '—' }}</div>
                        <div class="hq-stat-note">Rata-rata dari rekap santri</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Anggota & setoran terakhir ─────────────────────────────────── --}}
    <div class="row g-3 mt-0">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div class="hq-section">
                        <div class="hq-section-icon"><i class="ri-team-line"></i></div>
                        <div>
                            <h6 class="card-title mb-0">Anggota Halaqah <span class="badge bg-primary-subtle text-primary ms-1">{{ $activeCount }}</span></h6>
                            <p class="hq-help">Santri yang aktif mengikuti halaqah ini. Satu halaqah boleh berisi santri dari beberapa rombel, asal kelasnya sama.</p>
                        </div>
                    </div>
                    @if($canManage)
                        <button type="button" class="btn btn-primary btn-sm" onclick="openPullModal()"><i class="ri-user-add-line me-1"></i>Tarik Santri</button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 tahfidz-table hq-member-table">
                        <thead class="table-light">
                            <tr><th>Nama</th><th>NIS</th><th>Progres</th><th>Status</th><th class="text-end">Aksi</th></tr>
                        </thead>
                        <tbody>
                            @forelse($group->members as $member)
                                @php
                                    $rows = $juzProgress[$member->student_id] ?? collect();
                                    $selesai = $rows->whereIn('status', ['selesai_ziyadah','sudah_tasmi'])->count();
                                    $avg = $rows->whereNotNull('percentage')->avg('percentage');
                                @endphp
                                <tr>
                                    <td class="fw-medium">{{ $member->student?->name ?? '—' }}</td>
                                    <td class="text-muted">{{ $member->student?->nis ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success">{{ $selesai }} juz</span>
                                        @if($avg)
                                            <span class="text-muted small ms-1">{{ number_format($avg, 1) }}%</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $member->status === 'aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $member->status === 'aktif' ? 'Aktif' : 'Keluar' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('user.tahfidz.santri.show', ['userId'=>$userId,'studentId'=>$member->student_id]) }}" class="btn btn-sm btn-outline-primary" title="Detail tahfidz santri"><i class="ri-eye-line"></i></a>
                                        @if($canManage && $member->status === 'aktif')
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-member"
                                                data-action="{{ route('user.tahfidz.halaqah.members.remove', ['userId'=>$userId,'id'=>$group->id,'memberId'=>$member->id]) }}"
                                                data-name="{{ $member->student?->name }}" title="Keluarkan dari halaqah"><i class="ri-user-unfollow-line"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="ri-team-line fs-3 d-block mb-2"></i>
                                        Belum ada anggota.
                                        @if($canManage)
                                            <div class="small mt-1">Gunakan tombol <strong>Tarik Santri</strong> untuk memasukkan santri ke halaqah ini.</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div class="hq-section">
                        <div class="hq-section-icon"><i class="ri-file-add-line"></i></div>
                        <div>
                            <h6 class="card-title mb-0">Setoran Terakhir</h6>
                            <p class="hq-help">30 setoran terbaru dari halaqah ini.</p>
                        </div>
                    </div>
                    <a href="{{ route('user.tahfidz.setoran.index', ['userId'=>$userId,'group_id'=>$group->id]) }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Jenis</th><th>Nilai</th></tr></thead>
                        <tbody>
                            @forelse($group->setorans as $setoran)
                                <tr>
                                    <td class="text-nowrap">{{ $setoran->setoran_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $setoran->student?->name ?? '—' }}</td>
                                    <td class="small">{{ $setoran->setoran_type }}</td>
                                    <td>{{ $setoran->nilai_setoran ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada setoran. Catat setoran lewat tombol <strong>Input Setoran</strong>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @php
                    $perJenis = $group->setorans->groupBy('setoran_type')->map->count()->sortDesc();
                @endphp
                <div class="card-body border-top">
                    <div class="hq-stat-label mb-2">Ringkasan 30 Setoran Terakhir</div>
                    @if($perJenis->isEmpty())
                        <p class="hq-help mb-0">Belum ada data untuk diringkas. Ringkasan muncul setelah santri mulai menyetor.</p>
                    @else
                        @foreach($perJenis as $jenis => $jumlah)
                            <div class="d-flex justify-content-between align-items-center py-1 {{ ! $loop->last ? 'border-bottom border-dashed' : '' }}">
                                <span class="small">{{ ucfirst((string) $jenis) }}</span>
                                <span class="badge bg-primary-subtle text-primary">{{ $jumlah }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Jadwal ─────────────────────────────────────────────────────── --}}
    <div class="card mt-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div class="hq-section">
                <div class="hq-section-icon"><i class="ri-calendar-schedule-line"></i></div>
                <div>
                    <h6 class="card-title mb-0">Jadwal Halaqah <span class="badge bg-success-subtle text-success ms-1">{{ $scheduleCount }} aktif</span></h6>
                    <p class="hq-help">Waktu rutin kegiatan halaqah. Jadwal bentrok dengan guru, ruang, atau santri yang sama akan ditolak.</p>
                </div>
            </div>
            @if($canSchedule)
                <button type="button" class="btn btn-primary btn-sm" onclick="openScheduleModal()"><i class="ri-add-line me-1"></i>Tambah Jadwal</button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Hari</th><th>Waktu</th><th>Jenis</th><th>Ruang</th><th>Berlaku</th><th>Status</th>@if($canSchedule)<th class="text-end">Aksi</th>@endif</tr></thead>
                <tbody>
                    @forelse($group->schedules as $schedule)
                        @php
                            $schedulePayload = [
                                'id' => $schedule->id,
                                'day_of_week' => $schedule->day_of_week,
                                'time_start' => substr((string) $schedule->time_start, 0, 5),
                                'time_end' => substr((string) $schedule->time_end, 0, 5),
                                'schedule_type' => $schedule->schedule_type,
                                'room' => $schedule->room,
                                'is_active' => (bool) $schedule->is_active,
                                'effective_from' => $schedule->effective_from?->format('Y-m-d'),
                                'effective_until' => $schedule->effective_until?->format('Y-m-d'),
                                'notes' => $schedule->notes,
                            ];
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ $schedule->day_name }}</td>
                            <td class="text-nowrap">{{ substr((string) $schedule->time_start, 0, 5) }}–{{ substr((string) $schedule->time_end, 0, 5) }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $schedule->type_label }}</span></td>
                            <td>{{ $schedule->room ?? '—' }}</td>
                            <td class="small">{{ $schedule->effective_from?->format('d/m/Y') ?? 'Selalu' }}{{ $schedule->effective_until ? ' – '.$schedule->effective_until->format('d/m/Y') : '' }}</td>
                            <td><span class="badge {{ $schedule->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            @if($canSchedule)
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Edit jadwal" data-schedule="{{ json_encode($schedulePayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openScheduleEditModal(JSON.parse(this.dataset.schedule))"><i class="ri-edit-line"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-schedule" title="Hapus jadwal" data-action="{{ route('user.tahfidz.halaqah.schedules.destroy', ['userId'=>$userId,'id'=>$group->id,'scheduleId'=>$schedule->id]) }}"><i class="ri-delete-bin-line"></i></button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSchedule ? 7 : 6 }}" class="text-center text-muted py-5">
                                <i class="ri-calendar-line fs-3 d-block mb-2"></i>
                                Belum ada jadwal halaqah.
                                @if($canSchedule)
                                    <div class="small mt-1">Tambahkan jadwal agar santri dan guru tahu waktu kegiatan.</div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canSchedule)
        <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('user.tahfidz.halaqah.schedules.store', ['userId'=>$userId,'id'=>$group->id]) }}" id="scheduleForm">@csrf<input type="hidden" name="_method" value="PUT" id="scheduleMethod" disabled><div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="scheduleModalTitle">Tambah Jadwal Halaqah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">Hari <span class="text-danger">*</span></label><select name="day_of_week" id="schedule_day" class="form-select" required>@foreach([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'] as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Mulai <span class="text-danger">*</span></label><input type="time" name="time_start" id="schedule_start" class="form-control" required></div><div class="col-md-4"><label class="form-label">Selesai <span class="text-danger">*</span></label><input type="time" name="time_end" id="schedule_end" class="form-control" required></div><div class="col-md-6"><label class="form-label">Jenis</label><select name="schedule_type" id="schedule_type" class="form-select" required><option value="setoran">Setoran</option><option value="murajaah">Murajaah</option><option value="tasmi">Tasmi’</option><option value="evaluasi">Evaluasi</option><option value="tikror">Tikror</option></select></div><div class="col-md-6"><label class="form-label">Ruang</label><input name="room" id="schedule_room" class="form-control" maxlength="100"></div><div class="col-md-4"><label class="form-label">Berlaku Mulai</label><input type="date" name="effective_from" id="schedule_effective_from" class="form-control"></div><div class="col-md-4"><label class="form-label">Berlaku Sampai</label><input type="date" name="effective_until" id="schedule_effective_until" class="form-control"></div><div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" id="schedule_notes" rows="2" class="form-control"></textarea></div><div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" id="schedule_active" class="form-check-input" checked><label class="form-check-label" for="schedule_active">Aktif</label></div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="scheduleSubmit"><i class="ri-save-line me-1"></i>Simpan Jadwal</button></div></form></div></div></div>
        <form id="deleteScheduleForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="deleteScheduleModal" title="Hapus Jadwal?" message="Jadwal halaqah ini akan dihapus." submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteScheduleForm" />
    @endif

    @if($canManage)
        <div class="modal fade" id="pullModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-1"><i class="ri-user-add-line me-1"></i>Tarik Santri ke {{ $group->name }}</h5>
                            <p class="hq-help mb-0">Pilih santri dari daftar santri sekolah. Santri yang sudah ada di halaqah lain ditandai dan akan dipindahkan ke halaqah ini.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="px-3 py-2 border-bottom bg-light-subtle">
                            <div class="row align-items-center g-2">
                                <div class="col-md-7">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                                        <input type="text" id="pullSearch" class="form-control" placeholder="Cari nama, NIS, atau NISN..." autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-5 text-md-end">
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="checkbox" id="pullSelectAll" onchange="pullToggleAll(this)">
                                        <label class="form-check-label fw-semibold" for="pullSelectAll">Pilih Semua</label>
                                    </div>
                                    <span id="pullBadge" class="badge bg-success ms-1" style="display:none"></span>
                                </div>
                            </div>
                            @if($group->gradeLevel?->name)
                                <div class="form-text mt-1">Halaqah ini untuk <strong>{{ $group->gradeLevel->name }}</strong>; santri dari kelas lain ditolak agar tingkat halaqah tetap satu.</div>
                            @endif
                        </div>
                        <div id="pullList" class="list-group list-group-flush" style="max-height:380px;overflow-y:auto;">
                            <div class="text-center text-muted py-5" id="pullLoading">
                                <div class="spinner-border spinner-border-sm text-primary"></div>
                                <div class="mt-2">Memuat data santri...</div>
                            </div>
                        </div>
                        <div id="pullEmpty" class="text-center text-muted py-5" style="display:none">
                            <i class="ri-user-follow-line fs-1 d-block mb-2"></i>
                            <p class="mb-0" id="pullEmptyText">Semua santri sudah masuk halaqah ini.</p>
                        </div>
                        <div id="pullError" class="text-center py-4" style="display:none">
                            <i class="ri-error-warning-line fs-1 text-danger d-block mb-2"></i>
                            <p class="text-danger mb-2" id="pullErrorText">Gagal memuat data santri.</p>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadPullStudents()"><i class="ri-refresh-line me-1"></i>Coba Lagi</button>
                        </div>
                    </div>
                    <div class="modal-footer bg-light-subtle">
                        <span id="pullInfo" class="me-auto text-muted small"></span>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success" id="pullSubmit" disabled onclick="submitPull()"><i class="ri-add-line me-1"></i>Masukkan ke Halaqah</button>
                    </div>
                </div>
            </div>
        </div>

        <form id="removeMemberForm" method="POST" class="d-none">@csrf</form>
        <x-modal-confirm id="removeMemberModal" title="Keluarkan Santri?" message="<strong id='removeMemberName'>Santri</strong> akan dikeluarkan dari halaqah ini." submit-label="Ya, Keluarkan" submit-icon="ri-user-unfollow-line" submit-class="btn-danger" form-id="removeMemberForm" />
    @endif
@endsection

@push('scripts')
<script>
    @php
        $scheduleStoreUrl = route('user.tahfidz.halaqah.schedules.store', ['userId'=>$userId,'id'=>$group->id]);
        $scheduleUpdateUrl = route('user.tahfidz.halaqah.schedules.update', ['userId'=>$userId,'id'=>$group->id,'scheduleId'=>'__ID__']);
    @endphp
    function setScheduleField(id, value) { const el = document.getElementById(id); if (el) el.value = value ?? ''; }
    function openScheduleModal() {
        const form = document.getElementById('scheduleForm');
        if (!form || !window.bootstrap) return;
        form.reset(); form.action = @json($scheduleStoreUrl);
        document.getElementById('scheduleMethod').disabled = true;
        document.getElementById('scheduleModalTitle').textContent = 'Tambah Jadwal Halaqah';
        document.getElementById('scheduleSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Jadwal';
        document.getElementById('schedule_active').checked = true;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal')).show();
    }
    function openScheduleEditModal(data) {
        const form = document.getElementById('scheduleForm');
        if (!form || !data || !window.bootstrap) return;
        form.action = @json($scheduleUpdateUrl).replace('__ID__', data.id);
        document.getElementById('scheduleMethod').disabled = false;
        document.getElementById('scheduleModalTitle').textContent = 'Edit Jadwal Halaqah';
        document.getElementById('scheduleSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Perubahan';
        setScheduleField('schedule_day', data.day_of_week); setScheduleField('schedule_start', data.time_start); setScheduleField('schedule_end', data.time_end); setScheduleField('schedule_type', data.schedule_type); setScheduleField('schedule_room', data.room); setScheduleField('schedule_effective_from', data.effective_from); setScheduleField('schedule_effective_until', data.effective_until); setScheduleField('schedule_notes', data.notes);
        document.getElementById('schedule_active').checked = !!data.is_active;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-schedule').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteScheduleForm').action = this.dataset.action;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteScheduleModal')).show();
            });
        });
        document.querySelectorAll('.remove-member').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('removeMemberForm').action = this.dataset.action;
                document.getElementById('removeMemberName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('removeMemberModal')).show();
            });
        });
    });

    // ── Tarik santri ke halaqah (alur seperti rombel: cari → pilih → tarik) ──
    const PULL_AVAILABLE_URL = @json(route('user.tahfidz.halaqah.members.available', ['userId' => $userId, 'id' => $group->id]));
    const PULL_SUBMIT_URL = @json(route('user.tahfidz.halaqah.members.pull', ['userId' => $userId, 'id' => $group->id]));
    const PULL_CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let pullStudents = [];
    let pullSelected = new Set();

    function pullState(state) {
        document.getElementById('pullList').style.display = state === 'list' ? '' : 'none';
        document.getElementById('pullLoading').style.display = state === 'loading' ? '' : 'none';
        document.getElementById('pullEmpty').style.display = state === 'empty' ? '' : 'none';
        document.getElementById('pullError').style.display = state === 'error' ? '' : 'none';
    }

    function openPullModal() {
        pullStudents = [];
        pullSelected = new Set();
        document.getElementById('pullSearch').value = '';
        document.getElementById('pullSelectAll').checked = false;
        pullState('loading');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('pullModal')).show();
        loadPullStudents();
    }

    async function loadPullStudents() {
        pullState('loading');
        try {
            const res = await fetch(PULL_AVAILABLE_URL, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (! res.ok) {
                let message = 'Server error: ' + res.status;
                try { message = (await res.json()).message || message; } catch (_) {}
                throw new Error(message);
            }
            pullStudents = (await res.json()).data || [];
            pullRender();
        } catch (err) {
            document.getElementById('pullErrorText').textContent = 'Gagal memuat data santri: ' + err.message;
            pullState('error');
        }
    }

    function pullRender() {
        const term = document.getElementById('pullSearch').value.trim().toLowerCase();
        const visible = pullStudents.filter(function (s) {
            if (! term) return true;
            return [s.name, s.nis, s.nisn].filter(Boolean).join(' ').toLowerCase().includes(term);
        });

        const list = document.getElementById('pullList');
        list.innerHTML = visible.map(function (s) {
            const others = s.group_name ? ' <span class="badge bg-warning-subtle text-warning ms-1">dari ' + pullEscape(s.group_name) + '</span>' : '';
            return '<label class="list-group-item d-flex align-items-center gap-2 py-2">' +
                '<input class="form-check-input pull-checkbox flex-shrink-0" type="checkbox" value="' + s.id + '"' + (pullSelected.has(s.id) ? ' checked' : '') + '>' +
                '<span class="flex-grow-1"><span class="fw-medium">' + pullEscape(s.name) + '</span>' + others +
                '<span class="text-muted small d-block">NIS ' + pullEscape(s.nis || '-') + (s.class_name ? ' · ' + pullEscape(s.class_name) : '') + '</span></span>' +
                '</label>';
        }).join('');

        list.querySelectorAll('.pull-checkbox').forEach(function (box) {
            box.addEventListener('change', function () {
                if (this.checked) pullSelected.add(this.value); else pullSelected.delete(this.value);
                pullUpdateSelection();
            });
        });

        if (! pullStudents.length) {
            document.getElementById('pullEmptyText').textContent = 'Semua santri sekolah ini sudah masuk halaqah.';
            pullState('empty');
        } else if (! visible.length) {
            document.getElementById('pullEmptyText').textContent = 'Tidak ada santri yang cocok dengan pencarian.';
            pullState('empty');
        } else {
            pullState('list');
        }

        pullUpdateSelection();
    }

    function pullEscape(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function pullUpdateSelection() {
        const badge = document.getElementById('pullBadge');
        const submit = document.getElementById('pullSubmit');
        badge.style.display = pullSelected.size ? '' : 'none';
        badge.textContent = pullSelected.size + ' dipilih';
        submit.disabled = pullSelected.size === 0;
        document.getElementById('pullInfo').textContent = pullSelected.size ? pullSelected.size + ' santri akan dimasukkan' : '';
    }

    function pullToggleAll(box) {
        document.querySelectorAll('.pull-checkbox').forEach(function (cb) {
            cb.checked = box.checked;
            if (box.checked) pullSelected.add(cb.value); else pullSelected.delete(cb.value);
        });
        pullUpdateSelection();
    }

    async function submitPull() {
        if (! pullSelected.size) return;
        const button = document.getElementById('pullSubmit');
        button.disabled = true;
        try {
            const res = await fetch(PULL_SUBMIT_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': PULL_CSRF,
                },
                body: JSON.stringify({ student_ids: Array.from(pullSelected) }),
            });
            const json = await res.json().catch(() => ({}));
            if (! res.ok || json.success === false) {
                throw new Error(json.message || 'Santri gagal dimasukkan.');
            }
            window.location.reload();
        } catch (err) {
            button.disabled = false;
            if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: err.message });
            } else {
                alert(err.message);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('pullSearch')?.addEventListener('input', function () {
            clearTimeout(window.__pullTimer);
            window.__pullTimer = setTimeout(pullRender, 200);
        });
    });
</script>
@endpush
