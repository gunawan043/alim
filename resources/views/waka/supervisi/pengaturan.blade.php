@extends('waka.master')
@section('title') Pengaturan Supervisor @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @php
        $total = $pengaturan->count();
        $supervisors = $pengaturan->pluck('supervisor_gtk_id')->unique()->count();
        $supervisees = $pengaturan->pluck('supervisee_gtk_id')->unique()->count();
        $active = $pengaturan->where('is_active', true)->count();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('waka.supervisi.index') }}">Supervisi</a> @endslot
        @slot('title') Pengaturan Supervisor @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        @foreach([
            ['Total Pasangan', $total, 'primary', 'ri-links-line'],
            ['Supervisor', $supervisors, 'info', 'ri-user-star-line'],
            ['Guru Disupervisi', $supervisees, 'success', 'ri-group-line'],
            ['Aktif', $active, 'warning', 'ri-checkbox-circle-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-90">
                    <div class="card-body py-3 d-flex align-items-center gap-2">
                        <span class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $tone }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $tone }}"></i></span></span>
                        <div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px">{{ $label }}</p><h3 class="fw-bold mb-0">{{ number_format($value) }}</h3></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="alert alert-light border small d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="ri-route-line me-1 text-primary"></i>Atur pasangan <strong>Supervisor → Guru yang Disupervisi</strong>. Pasangan ini yang muncul saat membuat jadwal supervisi, sehingga tidak ada lagi pemilihan bebas yang tidak sesuai.</span>
        <div class="d-flex gap-2">
            <a href="{{ route('waka.supervisi.index') }}" class="btn btn-sm btn-light">Kembali ke Supervisi</a>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPengaturanModal"><i class="ri-add-line me-1"></i>Tambah Pengaturan</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0">Daftar Pengaturan Supervisor</h5><p class="text-muted small mb-0">Tahun ajaran: {{ $academicYears->firstWhere('id', $selectedYearId)?->name ?? 'Semua' }}</p></div>
            <form method="GET" action="{{ route('waka.supervisi-pengaturan.index') }}" class="d-flex gap-2">
                <select name="academic_year_id" class="form-select form-select-sm" style="min-width:200px">
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ (string) $selectedYearId === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary"><i class="ri-filter-3-line me-1"></i>Tampilkan</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tahun Ajaran</th>
                            <th>Supervisor</th>
                            <th>Guru yang Disupervisi</th>
                            <th>Catatan</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width:150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($pengaturan as $row)
                        <tr>
                            <td class="small">{{ $row->academicYear?->name ?? '-' }}</td>
                            <td><span class="fw-semibold">{{ $row->supervisor?->name ?? '-' }}</span></td>
                            <td>{{ $row->supervisee?->name ?? '-' }}</td>
                            <td class="small text-muted">{{ $row->notes ?: '—' }}</td>
                            <td class="text-center">
                                <span class="badge {{ $row->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('waka.supervisi-pengaturan.toggle', $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm {{ $row->is_active ? 'btn-soft-warning' : 'btn-soft-success' }}" title="{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="{{ $row->is_active ? 'ri-pause-line' : 'ri-play-line' }}"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-soft-danger" data-bs-toggle="modal" data-bs-target="#deletePengaturanModal"
                                        data-action="{{ route('waka.supervisi-pengaturan.destroy', $row->id) }}"
                                        data-name="{{ $row->supervisor?->name }} → {{ $row->supervisee?->name }}" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="6" title="Belum ada pengaturan supervisor" message="Tambahkan pasangan supervisor dan guru yang akan disupervisi." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addPengaturanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('waka.supervisi-pengaturan.store') }}" method="POST">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-add-line me-1"></i>Tambah Pengaturan Supervisor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ (string) old('academic_year_id', $selectedYearId) === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                                <select name="supervisor_gtk_id" class="form-select" required>
                                    <option value="">— Pilih Supervisor —</option>
                                    @foreach($gtks as $gtk)
                                        <option value="{{ $gtk->id }}" {{ old('supervisor_gtk_id') === $gtk->id ? 'selected' : '' }}>{{ $gtk->name }}{{ $gtk->latestEmployment?->jabatan ? ' · '.$gtk->latestEmployment->jabatan : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Guru yang Disupervisi <span class="text-danger">*</span></label>
                                <select name="supervisee_gtk_id" class="form-select" required>
                                    <option value="">— Pilih Guru —</option>
                                    @foreach($gtks as $gtk)
                                        <option value="{{ $gtk->id }}" {{ old('supervisee_gtk_id') === $gtk->id ? 'selected' : '' }}>{{ $gtk->name }}{{ $gtk->latestEmployment?->nupy ? ' · NUPY '.$gtk->latestEmployment->nupy : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-control" maxlength="1000" value="{{ old('notes') }}" placeholder="Opsional">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deletePengaturanForm" method="POST" action="" class="d-none">@csrf @method('DELETE')</form>
    <x-modal-confirm id="deletePengaturanModal" icon="https://cdn.lordicon.com/tdrtiskw.json" primary-color="#f7b84b" secondary-color="#005981"
        title="Hapus Pengaturan?" message="Pasangan <strong id=&quot;deletePengaturanLabel&quot;>ini</strong> akan dihapus." form-id="deletePengaturanForm" submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" />

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('deletePengaturanModal');
            if (!modal) return;
            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;
                document.getElementById('deletePengaturanForm')?.setAttribute('action', trigger.dataset.action || '');
                var label = document.getElementById('deletePengaturanLabel');
                if (label) label.textContent = trigger.dataset.name || 'ini';
            });
        });
    </script>
    @endpush
@endsection
