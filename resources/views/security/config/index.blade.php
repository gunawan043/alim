@extends('layouts.master')

@section('title', 'SOP & Konfigurasi Keamanan')

@include('security.partials.styles')

@push('css')
<style>
    .kfg-tabs .nav-link {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .55rem .9rem; border-radius: 8px; border: 0;
        color: var(--bs-secondary-color); font-weight: 500;
    }
    .kfg-tabs .nav-link:hover { background: var(--bs-tertiary-bg); color: var(--bs-body-color); }
    .kfg-tabs .nav-link.active {
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
        font-weight: 600;
    }
    .kfg-tabs .kfg-count {
        font-size: .7rem; font-weight: 600; line-height: 1;
        min-width: 20px; padding: 3px 6px; border-radius: 20px;
        background: var(--bs-secondary-bg); color: var(--bs-secondary-color);
        font-variant-numeric: tabular-nums;
    }
    .kfg-tabs .nav-link.active .kfg-count {
        background: var(--bs-primary); color: #fff;
    }
    .kfg-toolbar {
        display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
        padding: .75rem 1rem; border-bottom: 1px solid var(--bs-border-color-translucent);
    }
    .kfg-toolbar .kfg-hint { font-size: .8rem; color: var(--bs-secondary-color); flex: 1 1 240px; }
    .kfg-table th {
        font-size: .75rem; font-weight: 600; letter-spacing: .01em;
        text-transform: none; color: var(--bs-secondary-color);
        padding: .6rem 1rem; white-space: nowrap;
    }
    .kfg-table td { padding: .7rem 1rem; vertical-align: middle; }
    .kfg-table tbody tr:last-child td { border-bottom: 0; }
    .kfg-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .78rem; font-weight: 600;
        padding: .2rem .45rem; border-radius: 6px;
        background: var(--bs-secondary-bg); color: var(--bs-body-color);
    }
    .kfg-sub { font-size: .78rem; color: var(--bs-secondary-color); }
    .kfg-sop {
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 10px; padding: 1rem 1.1rem; height: 100%;
        background: var(--bs-body-bg);
    }
    .kfg-sop h6 { display: flex; align-items: center; gap: .5rem; font-size: .9rem; margin-bottom: .6rem; }
    .kfg-sop ol { margin: 0; padding-left: 1.1rem; font-size: .82rem; color: var(--bs-secondary-color); }
    .kfg-sop li + li { margin-top: .3rem; }
    @media (max-width: 575.98px) {
        .kfg-tabs .nav-link { padding: .45rem .6rem; font-size: .82rem; }
    }
</style>
@endpush

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('title') SOP & Konfigurasi @endslot
    @endcomponent

    @php
        $tabs = [
            'pos' => ['label' => 'Pos Jaga', 'icon' => 'ri-map-pin-line', 'count' => $posts->count(), 'hint' => 'Pos jaga menjadi titik penempatan regu dan asal pencatatan tamu.'],
            'shift' => ['label' => 'Shift', 'icon' => 'ri-time-line', 'count' => $shifts->count(), 'hint' => 'Shift dipakai pada jadwal patroli dan regu.'],
            'zona' => ['label' => 'Zona Area', 'icon' => 'ri-layout-masonry-line', 'count' => $zones->count(), 'hint' => 'Zona membantu menandai area berisiko pada laporan insiden.'],
            'jenis' => ['label' => 'Jenis Insiden', 'icon' => 'ri-list-settings-line', 'count' => $types->count(), 'hint' => 'Jenis insiden dipakai saat melaporkan kejadian.'],
            'kontak' => ['label' => 'Kontak Darurat', 'icon' => 'ri-phone-line', 'count' => $contacts->count(), 'hint' => 'Nomor yang dihubungi saat keadaan darurat.'],
            'sop' => ['label' => 'SOP Pengamanan', 'icon' => 'ri-file-shield-2-line', 'count' => null, 'hint' => null],
        ];
        $activeMeta = $tabs[$tab] ?? $tabs['pos'];

        $summary = [
            ['label' => 'Pos Jaga', 'value' => $posts->where('is_active', true)->count(), 'sub' => 'aktif', 'icon' => 'ri-map-pin-line'],
            ['label' => 'Shift', 'value' => $shifts->count(), 'sub' => 'pembagian jam', 'icon' => 'ri-time-line'],
            ['label' => 'Zona Area', 'value' => $zones->count(), 'sub' => 'terdaftar', 'icon' => 'ri-layout-masonry-line'],
            ['label' => 'Kontak Darurat', 'value' => $contacts->count(), 'sub' => 'tersimpan', 'icon' => 'ri-phone-line'],
        ];
    @endphp

    <div class="row g-3 mb-1">
        @foreach($summary as $item)
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 stat-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-4">
                                <i class="{{ $item['icon'] }} text-primary"></i>
                            </span>
                        </span>
                        <div class="min-w-0">
                            <div class="fs-4 fw-semibold lh-1">{{ number_format($item['value']) }}</div>
                            <div class="kfg-sub mt-1 text-truncate">{{ $item['label'] }} · {{ $item['sub'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-3">
        <div class="card-header border-bottom-dashed pb-0">
            <ul class="nav nav-pills kfg-tabs gap-1 flex-nowrap overflow-auto pb-2" role="tablist">
                @foreach($tabs as $key => $meta)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                           href="{{ route('user.keamanan.config.index', ['userId' => $userId, 'tab' => $key]) }}">
                            <i class="{{ $meta['icon'] }}"></i>
                            <span>{{ $meta['label'] }}</span>
                            @if($meta['count'] !== null)
                                <span class="kfg-count">{{ $meta['count'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- ══ POS JAGA ══ --}}
        @if($tab === 'pos')
            <div class="kfg-toolbar">
                <span class="kfg-hint">{{ $activeMeta['hint'] }}</span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#postModal">
                    <i class="ri-add-line me-1"></i> Tambah Pos
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover kfg-table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Pos</th><th>Zona</th><th>Lokasi</th><th>Telepon</th><th>Status</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($posts as $post)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="kfg-code">{{ $post->code }}</span>
                                        <span class="fw-semibold">{{ $post->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $post->zone->name ?? '—' }}</td>
                                <td class="text-muted">{{ $post->location ?: '—' }}</td>
                                <td>
                                    @if($post->phone)
                                        <a href="tel:{{ $post->phone }}" class="text-decoration-none">
                                            <i class="ri-phone-line me-1"></i>{{ $post->phone }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $post->is_active ? 'success' : 'danger' }}-subtle text-{{ $post->is_active ? 'success' : 'danger' }}">
                                        {{ $post->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('user.keamanan.config.posts.destroy', ['userId' => $userId, 'post' => $post->id]) }}"
                                          data-confirm="Hapus pos {{ $post->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger" title="Hapus pos">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" title="Belum ada pos jaga" message="Tambahkan pos gerbang atau pos dalam." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="modal fade" id="postModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-map-pin-add-line me-2 text-primary"></i> Tambah Pos Jaga</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('user.keamanan.config.posts.store', ['userId' => $userId]) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Kode <span class="text-danger">*</span></label>
                                        <input type="text" name="code" class="form-control" placeholder="POS-01" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Pos Gerbang Utama" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Zona</label>
                                        <select name="zone_id" class="form-select">
                                            <option value="">— Tanpa zona —</option>
                                            @foreach($zones as $zone)
                                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Telepon</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Lokasi</label>
                                        <input type="text" name="location" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══ SHIFT ══ --}}
        @if($tab === 'shift')
            <div class="kfg-toolbar">
                <span class="kfg-hint">{{ $activeMeta['hint'] }}</span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#shiftModal">
                    <i class="ri-add-line me-1"></i> Tambah Shift
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover kfg-table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Shift</th><th>Jam Jaga</th><th>Durasi</th><th>Catatan</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                            @php
                                $start = \Illuminate\Support\Carbon::parse($shift->start_time);
                                $end = \Illuminate\Support\Carbon::parse($shift->end_time);
                                $minutes = $end->lt($start) ? $start->diffInMinutes($end->copy()->addDay()) : $start->diffInMinutes($end);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="kfg-code">{{ $shift->code }}</span>
                                        <span class="fw-semibold">{{ $shift->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $start->format('H:i') }}</span>
                                    <span class="text-muted mx-1">—</span>
                                    <span class="fw-medium">{{ $end->format('H:i') }}</span>
                                </td>
                                <td><span class="kfg-sub">{{ intdiv($minutes, 60) }} jam {{ $minutes % 60 }} mnt</span></td>
                                <td class="text-muted">{{ $shift->notes ?: '—' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('user.keamanan.config.shifts.destroy', ['userId' => $userId, 'shift' => $shift->id]) }}"
                                          data-confirm="Hapus shift {{ $shift->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger" title="Hapus shift">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="5" title="Belum ada shift" message="Tambahkan pembagian jam jaga." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="modal fade" id="shiftModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-time-line me-2 text-primary"></i> Tambah Shift</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('user.keamanan.config.shifts.store', ['userId' => $userId]) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Kode <span class="text-danger">*</span></label>
                                        <input type="text" name="code" class="form-control" placeholder="S1" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Pagi" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                        <input type="time" name="start_time" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                        <input type="time" name="end_time" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══ ZONA ══ --}}
        @if($tab === 'zona')
            <div class="kfg-toolbar">
                <span class="kfg-hint">{{ $activeMeta['hint'] }}</span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#zoneModal">
                    <i class="ri-add-line me-1"></i> Tambah Zona
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover kfg-table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Zona</th><th>Tingkat Risiko</th><th>Pos Terkait</th><th>Keterangan</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($zones as $zone)
                            @php
                                $riskColor = $zone->risk_level === 'tinggi' ? 'danger' : ($zone->risk_level === 'sedang' ? 'warning' : 'success');
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="kfg-code">{{ $zone->code }}</span>
                                        <span class="fw-semibold">{{ $zone->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $riskColor }}-subtle text-{{ $riskColor }}">{{ $zone->riskLabel() }}</span>
                                </td>
                                <td>
                                    @if($zone->posts_count)
                                        <a href="{{ route('user.keamanan.config.index', ['userId' => $userId, 'tab' => 'pos']) }}"
                                           class="text-decoration-none">{{ $zone->posts_count }} pos</a>
                                    @else
                                        <span class="text-muted">Belum ada pos</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $zone->description ?: '—' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('user.keamanan.config.zones.destroy', ['userId' => $userId, 'zone' => $zone->id]) }}"
                                          data-confirm="Hapus zona {{ $zone->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger" title="Hapus zona">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="5" title="Belum ada zona" message="Tambahkan zona area pesantren." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="modal fade" id="zoneModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-layout-masonry-line me-2 text-primary"></i> Tambah Zona</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('user.keamanan.config.zones.store', ['userId' => $userId]) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Kode <span class="text-danger">*</span></label>
                                        <input type="text" name="code" class="form-control" placeholder="Z-01" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Area Asrama Putra" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Tingkat Risiko <span class="text-danger">*</span></label>
                                        <select name="risk_level" class="form-select" required>
                                            @foreach(\App\Models\Security\SecurityZone::RISK_OPTIONS as $value => $label)
                                                <option value="{{ $value }}" {{ $value === 'sedang' ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Keterangan</label>
                                        <textarea name="description" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══ JENIS INSIDEN ══ --}}
        @if($tab === 'jenis')
            <div class="kfg-toolbar">
                <span class="kfg-hint">{{ $activeMeta['hint'] }}</span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#typeModal">
                    <i class="ri-add-line me-1"></i> Tambah Jenis
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover kfg-table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Jenis Insiden</th><th>Tingkat Default</th><th>Laporan</th><th>Keterangan</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                            @php
                                // Warna mengikuti SecurityIncident::severityColor().
                                $sev = match ($type->default_severity) {
                                    'kritis' => 'danger',
                                    'berat' => 'warning',
                                    'sedang' => 'info',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="kfg-code">{{ $type->code }}</span>
                                        <span class="fw-semibold">{{ $type->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $sev }}-subtle text-{{ $sev }}">
                                        {{ \App\Models\Security\SecurityIncident::SEVERITY_OPTIONS[$type->default_severity] ?? $type->default_severity }}
                                    </span>
                                </td>
                                <td>
                                    <span class="kfg-sub">{{ $type->incidents_count }} laporan</span>
                                </td>
                                <td class="text-muted">{{ $type->description ?: '—' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('user.keamanan.config.incident-types.destroy', ['userId' => $userId, 'type' => $type->id]) }}"
                                          data-confirm="Hapus jenis insiden {{ $type->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger" title="Hapus jenis">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="5" title="Belum ada jenis insiden" message="Tambahkan jenis agar laporan lebih terstruktur." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="modal fade" id="typeModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-list-settings-line me-2 text-primary"></i> Tambah Jenis Insiden</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('user.keamanan.config.incident-types.store', ['userId' => $userId]) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Kode <span class="text-danger">*</span></label>
                                        <input type="text" name="code" class="form-control" placeholder="INS-01" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Orang Asing Masuk" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Tingkat Default <span class="text-danger">*</span></label>
                                        <select name="default_severity" class="form-select" required>
                                            @foreach(\App\Models\Security\SecurityIncident::SEVERITY_OPTIONS as $value => $label)
                                                <option value="{{ $value }}" {{ $value === 'sedang' ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Keterangan</label>
                                        <textarea name="description" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══ KONTAK DARURAT ══ --}}
        @if($tab === 'kontak')
            <div class="kfg-toolbar">
                <span class="kfg-hint">{{ $activeMeta['hint'] }}</span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#contactModal">
                    <i class="ri-add-line me-1"></i> Tambah Kontak
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover kfg-table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Nama</th><th>Jabatan / Instansi</th><th>Telepon</th><th>Kategori</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($contacts as $contact)
                            <tr>
                                <td class="fw-semibold">{{ $contact->name }}</td>
                                <td class="text-muted">{{ $contact->role ?: '—' }}</td>
                                <td>
                                    <a href="tel:{{ $contact->phone }}" class="text-decoration-none fw-medium">
                                        <i class="ri-phone-line me-1"></i>{{ $contact->phone }}
                                    </a>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $contact->categoryLabel() }}</span></td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('user.keamanan.config.contacts.destroy', ['userId' => $userId, 'contact' => $contact->id]) }}"
                                          data-confirm="Hapus kontak {{ $contact->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger" title="Hapus kontak">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="5" title="Belum ada kontak darurat" message="Tambahkan nomor penting: pimpinan, polisi, damkar, rumah sakit." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-phone-line me-2 text-primary"></i> Tambah Kontak Darurat</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('user.keamanan.config.contacts.store', ['userId' => $userId]) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jabatan / Instansi</label>
                                        <input type="text" name="role" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Telepon <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                        <select name="category" class="form-select" required>
                                            @foreach(\App\Models\Security\SecurityContact::CATEGORY_OPTIONS as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Urutan Tampil</label>
                                        <input type="number" name="sort_order" class="form-control" min="0" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══ SOP ══ --}}
        @if($tab === 'sop')
            <div class="kfg-toolbar">
                <span class="kfg-hint">Alur kerja standar Satuan Keamanan. Ikuti urutan langkah pada tiap prosedur.</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="kfg-sop">
                            <h6><i class="ri-door-open-line text-primary"></i> Prosedur Pos Gerbang</h6>
                            <ol>
                                <li>Setiap tamu wajib menyerahkan identitas dan menyebutkan keperluan.</li>
                                <li>Catat tamu pada menu <strong>Log Tamu</strong>, lalu cetak kartu tamu.</li>
                                <li>Kartu tamu wajib dikembalikan saat tamu keluar; catat waktu keluar.</li>
                                <li>Kendaraan yang masuk dicatat pada menu <strong>Log Kendaraan</strong>.</li>
                                <li>Tamu tanpa keperluan jelas atau mencurigakan ditolak dan dilaporkan.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="kfg-sop">
                            <h6><i class="ri-shield-check-line text-success"></i> Prosedur Verifikasi Izin Santri</h6>
                            <ol>
                                <li>Pindai QR pada kartu izin santri melalui menu <strong>Scan QR Izin</strong>.</li>
                                <li>Pastikan nama santri, penjemput, dan masa berlaku izin sesuai.</li>
                                <li>Bila QR tidak terbaca, gunakan <strong>Verifikasi Manual</strong> dengan kode izin.</li>
                                <li>Izin yang belum disetujui atau penjemput belum diverifikasi tidak boleh dilepas.</li>
                                <li>Catat waktu keluar dan kembali; santri terlambat masuk daftar pantauan.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="kfg-sop">
                            <h6><i class="ri-map-pin-line text-warning"></i> Prosedur Patroli</h6>
                            <ol>
                                <li>Buka jadwal patroli hari ini, tekan <strong>Mulai</strong>.</li>
                                <li>Pindai QR setiap checkpoint sesuai urutan rute.</li>
                                <li>Tandai kondisi <strong>Aman</strong> atau <strong>Ada Temuan</strong> beserta catatan.</li>
                                <li>Temuan yang berpotensi bahaya wajib dibuatkan laporan insiden.</li>
                                <li>Patroli selesai otomatis setelah semua checkpoint dipindai.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="kfg-sop">
                            <h6><i class="ri-alert-line text-danger"></i> Prosedur Penanganan Insiden</h6>
                            <ol>
                                <li>Amankan lokasi dan pastikan tidak ada korban tambahan.</li>
                                <li>Catat insiden pada menu <strong>Laporan Insiden</strong> dalam hari yang sama.</li>
                                <li>Hubungi kontak darurat sesuai kategori kejadian.</li>
                                <li>Setiap tindakan dicatat pada riwayat tindak lanjut.</li>
                                <li>Insiden ditutup oleh Kepala/Koordinator dengan uraian penyelesaian.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <i class="ri-information-line me-1"></i>
                            Aturan tata tertib dan kebijakan izin santri yang berlaku dapat dilihat di
                            <a href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}" class="alert-link">Kebijakan Izin Santri</a>
                            dan
                            <a href="{{ route('user.boarding-regulations.index', ['userId' => $userId]) }}" class="alert-link">Peraturan Asrama</a>.
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
