@extends('layouts.master')
@section('title') Monitoring Rombel & Kamar Asrama @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Monitoring Rombel & Kamar
    @endcomponent

    <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="ri-information-line fs-5"></i>
        <span>Data kepadatan untuk kebutuhan pendataan Sarpras dan Kebersihan. Tahun ajaran aktif: <strong>{{ $activeYear?->name ?? '-' }}</strong>{{ $activeYear?->semester ? ' — '.ucfirst($activeYear->semester) : '' }}.</span>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Total Santri', $stats['students'], 'primary', 'ri-team-line', 'Santri berstatus aktif'],
            ['Rombel', $stats['rombels'], 'info', 'ri-group-line', $stats['class_filled'].' / '.$stats['class_capacity'].' kursi terisi'],
            ['Belum Berombol', $stats['without_rombel'], 'warning', 'ri-user-unfollow-line', 'Perlu penempatan rombel'],
            ['Kamar Asrama', $stats['dorm_rooms'], 'success', 'ri-hotel-bed-line', $stats['dorm_residents'].' / '.$stats['dorm_capacity'].' tempat tidur terisi'],
            ['Penghuni Asrama', $stats['dorm_residents'], 'info', 'ri-user-location-line', 'Penghuni aktif tahun ajaran'],
            ['Belum Dapat Kamar', $stats['without_room'], 'danger', 'ri-door-closed-line', 'Perlu penempatan kamar'],
        ] as [$label, $value, $tone, $icon, $sub])
            <div class="col-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body py-3 d-flex align-items-center gap-3">
                        <span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span>
                        <div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div><div class="text-muted" style="font-size:.7rem">{{ $sub }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sarpras.monitoring.rombel-kamar') }}" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Satuan Pendidikan</label><select name="school_id" class="form-select"><option value="">Semua</option>@foreach($schools as $school)<option value="{{ $school->id }}" {{ request('school_id') === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Asrama</label><select name="dormitory_id" class="form-select"><option value="">Semua</option>@foreach($dormitories as $dormitory)<option value="{{ $dormitory->id }}" {{ request('dormitory_id') === $dormitory->id ? 'selected' : '' }}>{{ $dormitory->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Cari</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Rombel / kamar / wali…"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i>Tampilkan</button></div>
                <div class="col-md-1"><a href="{{ route('sarpras.monitoring.rombel-kamar') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i></a></div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-group-line me-2 text-primary"></i>Jumlah Santri per Rombel</h5><p class="text-muted small mb-0">Kepadatan rombel membantu perencanaan meja, kursi, papan tulis, dan beban kebersihan kelas.</p></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Satuan Pendidikan</th><th>Rombel</th><th>Tingkat</th><th>Wali Kelas</th><th class="text-end">Santri</th><th class="text-end">Kapasitas</th><th style="min-width:150px">Keterisian</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($rombels as $i => $rombel)
                        @php
                            $percent = $rombel->capacity > 0 ? round($rombel->students_count / $rombel->capacity * 100) : null;
                            $tone = $percent === null ? 'secondary' : ($percent > 100 ? 'danger' : ($percent >= 80 ? 'warning' : 'success'));
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="small">{{ $rombel->school?->name ?? '-' }}</td>
                            <td><div class="fw-medium">{{ $rombel->name }}</div><small class="text-muted">{{ $rombel->code }}</small></td>
                            <td>{{ $rombel->gradeLevel?->name ?? '-' }}</td>
                            <td class="small">{{ $rombel->homeroomTeacher?->name ?? '-' }}</td>
                            <td class="text-end fw-semibold">{{ number_format($rombel->students_count) }}</td>
                            <td class="text-end text-muted">{{ $rombel->capacity ? number_format($rombel->capacity) : '-' }}</td>
                            <td>
                                @if($percent !== null)
                                    <div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-{{ $tone }}" style="width:{{ min(100, $percent) }}%"></div></div><small class="text-{{ $tone }}">{{ $percent }}%</small></div>
                                @else
                                    <span class="text-muted small">Kapasitas belum diisi</span>
                                @endif
                            </td>
                            <td>
                                @if($percent === null)<span class="badge bg-secondary-subtle text-secondary">Belum ada kapasitas</span>
                                @elseif($percent > 100)<span class="badge bg-danger-subtle text-danger">Melebihi kapasitas</span>
                                @elseif($percent >= 80)<span class="badge bg-warning-subtle text-warning">Hampir penuh</span>
                                @else<span class="badge bg-success-subtle text-success">Memadai</span>@endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="9" title="Belum ada rombel" message="Data rombel akan tampil setelah tahun ajaran dan rombel disiapkan." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-hotel-bed-line me-2 text-success"></i>Jumlah Santri per Kamar Asrama</h5><p class="text-muted small mb-0">Kepadatan kamar membantu perencanaan tempat tidur, lemari, sanitasi, dan beban kebersihan.</p></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Asrama</th><th>Kamar</th><th>Wing / Lantai</th><th>Wali Kamar</th><th class="text-end">Penghuni</th><th class="text-end">Kapasitas</th><th style="min-width:150px">Keterisian</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($dormRooms as $i => $room)
                        @php
                            $percent = $room->capacity > 0 ? round($room->residents_count / $room->capacity * 100) : null;
                            $tone = $percent === null ? 'secondary' : ($percent > 100 ? 'danger' : ($percent >= 80 ? 'warning' : 'success'));
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="small">{{ $room->dormitory?->name ?? '-' }}</td>
                            <td><div class="fw-medium">{{ $room->name ?: $room->code }}</div><small class="text-muted">{{ $room->code }}</small></td>
                            <td class="small">{{ $room->wing?->name ?? '-' }}{{ $room->floor ? ' · Lantai '.$room->floor : '' }}</td>
                            <td class="small">{{ $room->activeSupervisor?->user?->name ?? '-' }}</td>
                            <td class="text-end fw-semibold">{{ number_format($room->residents_count) }}</td>
                            <td class="text-end text-muted">{{ $room->capacity ? number_format($room->capacity) : '-' }}</td>
                            <td>
                                @if($percent !== null)
                                    <div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-{{ $tone }}" style="width:{{ min(100, $percent) }}%"></div></div><small class="text-{{ $tone }}">{{ $percent }}%</small></div>
                                @else
                                    <span class="text-muted small">Kapasitas belum diisi</span>
                                @endif
                            </td>
                            <td>
                                @if($percent === null)<span class="badge bg-secondary-subtle text-secondary">Belum ada kapasitas</span>
                                @elseif($percent > 100)<span class="badge bg-danger-subtle text-danger">Melebihi kapasitas</span>
                                @elseif($percent >= 80)<span class="badge bg-warning-subtle text-warning">Hampir penuh</span>
                                @else<span class="badge bg-success-subtle text-success">Memadai</span>@endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="9" title="Belum ada kamar asrama" message="Data kamar akan tampil setelah master kamar asrama diisi." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-building-2-line me-2 text-primary"></i>Ringkasan per Satuan Kerja</h5><p class="text-muted small mb-0">Gambaran beban pendataan aset, ruang, dan asrama per unit.</p></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>No</th><th>Satuan Kerja</th><th class="text-end">Ruang</th><th class="text-end">Aset</th><th class="text-end">Kamar Asrama</th><th class="text-end">Penghuni</th><th class="text-end">Petugas Sarpras</th></tr></thead>
                    <tbody>
                    @forelse($unitSummary as $i => $unit)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $unit->name }}</td>
                            <td class="text-end">{{ number_format($unit->asset_rooms_count) }}</td>
                            <td class="text-end">{{ number_format($unit->assets_count) }}</td>
                            <td class="text-end">{{ number_format($unit->dorm_rooms_count) }}</td>
                            <td class="text-end">{{ number_format($unit->residents_count) }}</td>
                            <td class="text-end"><a href="{{ route('sarpras.petugas.index', ['work_unit_id' => $unit->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-user-star-line me-1"></i>Lihat</a></td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" title="Belum ada satuan kerja" message="Data unit akan tampil setelah master satuan kerja tersedia." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
