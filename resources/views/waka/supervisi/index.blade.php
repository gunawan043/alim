@extends('waka.master')
@section('title') Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Supervisi @endslot
        @slot('title') Daftar Supervisi @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $stats = $statistics ?? ['total' => 0, 'terjadwal' => 0, 'selesai' => 0, 'avg_nilai' => 0];
        $jabatanUser = strtoupper((string) (auth()->user()->employment?->jabatan ?? ''));
        $canManageSupervisi = str_contains($jabatanUser, 'KEPALA') || str_contains($jabatanUser, 'WAKIL')
            || str_contains($jabatanUser, 'TATA USAHA') || str_contains($jabatanUser, 'KOORDINATOR KURIKULUM');
    @endphp

    <div class="row">
        @foreach([
            ['Total Supervisi', number_format($stats['total']), 'primary', 'ri-file-list-3-line'],
            ['Terjadwal', number_format($stats['terjadwal']), 'warning', 'ri-calendar-event-line'],
            ['Selesai', number_format($stats['selesai']), 'success', 'ri-checkbox-circle-line'],
            ['Rata-rata Nilai', number_format((float) $stats['avg_nilai'], 2), 'info', 'ri-bar-chart-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3 d-flex align-items-center gap-2">
                        <span class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $tone }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $tone }}"></i></span></span>
                        <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px">{{ $label }}</p><h3 class="fw-bold mb-0">{{ $value }}</h3></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="alert alert-light border small d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="ri-route-line me-1 text-primary"></i><strong>Alur:</strong> buat jadwal supervisi → buka detail → isi penilaian → pilih instrumen → isi skor → simpan → cetak.</span>
        <a href="{{ route('waka.supervisi-instrumen.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="ri-settings-3-line me-1"></i>Atur Instrumen
        </a>
    </div>

    <div class="card" id="supervisiList">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0">Daftar Supervisi</h5>
                    <p class="text-muted mb-0 small">Jadwal dan hasil supervisi pembelajaran GTK.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary align-self-center">{{ $supervisiList->total() }} supervisi</span>
                    @if($canManageSupervisi)
                        <a href="{{ route('waka.supervisi-pengaturan.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="ri-settings-3-line me-1"></i>Pengaturan Supervisor
                        </a>
                        <a href="{{ route('waka.supervisi-sk.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-file-shield-2-line me-1"></i>Tim &amp; SK
                        </a>
                    @endif
                    <a href="{{ route('waka.supervisi.jadwal.cetak', ['academic_year_id' => request('academic_year_id'), 'semester' => request('semester')]) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                        <i class="ri-printer-line me-1"></i>Cetak Jadwal
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#filterSupervisiModal">
                        <i class="ri-filter-3-line me-1"></i>Filter
                    </button>
                    <a href="{{ route('waka.supervisi.create') }}" class="btn btn-success btn-sm">
                        <i class="ri-add-line me-1"></i>Tambah Supervisi
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">


            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:130px">Jadwal</th>
                            <th>Guru yang Disupervisi</th>
                            <th style="width:190px">Supervisor</th>
                            <th style="width:160px">Mapel</th>
                            <th style="width:120px">Jenis</th>
                            <th style="width:120px" class="text-center">Status</th>
                            <th style="width:140px" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supervisiList as $s)
                            @php
                                $badge = ['terjadwal' => 'warning', 'berlangsung' => 'info', 'selesai' => 'success', 'dibatalkan' => 'secondary'][$s->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="small">
                                    <div class="fw-medium">{{ $s->tanggal_supervisi?->format('d/m/Y') ?? '—' }}</div>
                                    <div class="text-muted">{{ $s->jam_mulai ? substr($s->jam_mulai, 0, 5) : '—' }}{{ $s->jam_selesai ? ' – '.substr($s->jam_selesai, 0, 5) : '' }}</div>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $s->gtk?->user?->name ?? $s->gtk_name ?? '—' }}</span>
                                    @if($s->gtk?->latest_nupy)
                                        <small class="text-muted d-block">NUPY. {{ $s->gtk->latest_nupy }}</small>
                                    @endif
                                </td>
                                <td class="small">{{ $s->observer?->user?->name ?? $s->observer_name ?? '—' }}</td>
                                <td class="small">{{ $s->mata_pelajaran ?: '—' }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ \Illuminate\Support\Str::headline($s->jenis_supervisi) }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ ucfirst($s->status) }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('waka.supervisi.show', $s->id) }}" class="btn btn-sm btn-soft-primary me-1" title="Detail">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('waka.supervisi.edit', $s->id) }}" class="btn btn-sm btn-soft-warning me-1" title="Edit">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-soft-danger"
                                            title="Hapus"
                                            data-bs-toggle="modal" data-bs-target="#deleteSupervisiModal"
                                            data-action="{{ route('waka.supervisi.destroy', $s->id) }}"
                                            data-name="{{ $s->gtk?->user?->name ?? $s->gtk_name }}"
                                            data-date="{{ $s->tanggal_supervisi?->format('d/m/Y') }}">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7"
                                title="Belum ada data supervisi"
                                message="Tambahkan jadwal supervisi untuk memulai.">
                                <a href="{{ route('waka.supervisi.create') }}" class="btn btn-success mt-3">
                                    <i class="ri-add-line me-1"></i>Tambah Supervisi
                                </a>
                            </x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('shared._pagination', ['paginator' => $supervisiList])
        </div>
    </div>

    <div class="modal fade" id="filterSupervisiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('waka.supervisi.index') }}">
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-filter-3-line me-1"></i>Filter Supervisi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Cari</label>
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Guru / supervisor / mapel">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Supervisor</label>
                                <select name="supervisor_id" class="form-select">
                                    <option value="">Semua supervisor</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}" {{ (string) request('supervisor_id') === (string) $supervisor->id ? 'selected' : '' }}>{{ $supervisor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Semua status</option>
                                    @foreach(\App\Models\Supervisi::STATUS_OPTIONS as $key => $label)
                                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Jenis</label>
                                <select name="jenis_supervisi" class="form-select">
                                    <option value="">Semua jenis</option>
                                    @foreach(\App\Models\Supervisi::JENIS_OPTIONS as $key => $label)
                                        <option value="{{ $key }}" {{ request('jenis_supervisi') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                                <select name="academic_year_id" class="form-select">
                                    <option value="">Semua tahun</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ (string) request('academic_year_id') === (string) $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('waka.supervisi.index') }}" class="btn btn-light">Reset</a>
                        <button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi hapus — template lord-icon/zoomIn --}}
    <form id="deleteSupervisiForm" method="POST" action="" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-confirm id="deleteSupervisiModal"
        icon="https://cdn.lordicon.com/tdrtiskw.json"
        primary-color="#f7b84b" secondary-color="#005981"
        title="Hapus Data Supervisi?"
        message="Data supervisi <strong id=&quot;deleteSupervisiLabel&quot;>ini</strong> akan dihapus. Tindakan ini tidak dapat dibatalkan."
        form-id="deleteSupervisiForm"
        submit-label="Ya, Hapus"
        submit-icon="ri-delete-bin-line"
        submit-class="btn-danger" />

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('deleteSupervisiModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var form = document.getElementById('deleteSupervisiForm');
                if (form) form.setAttribute('action', trigger.getAttribute('data-action') || '');

                var label = document.getElementById('deleteSupervisiLabel');
                if (label) {
                    var name = trigger.getAttribute('data-name') || 'ini';
                    var date = trigger.getAttribute('data-date') || '';
                    label.textContent = (name + (date ? ' — ' + date : '')).trim();
                }
            });
        });
    </script>
    @endpush
@endsection
