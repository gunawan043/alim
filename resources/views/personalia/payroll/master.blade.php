@extends('layouts.master')

@section('title') Master Gaji & Aturan Payroll @endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $activeTab = $tab ?? request('tab', 'aturan');
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Gaji & Kompensasi @endslot
        @slot('li_2') <a href="{{ route('user.payroll.index', $userId) }}">Daftar Gaji</a> @endslot
        @slot('title') Master Gaji & Aturan @endslot
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

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-1"><i class="ri-settings-3-line me-2 text-primary"></i>Master Gaji & Aturan Payroll</h5>
            <p class="text-muted small mb-0">Skala gaji, tunjangan, dan aturan potongan berbasis kehadiran.</p>
        </div>
        <a href="{{ route('user.payroll.index', $userId) }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-go-back-line me-1"></i>Kembali
        </a>
    </div>

    <ul class="nav nav-tabs nav-tabs-custom mb-3">
        @foreach(['aturan' => 'Aturan Payroll', 'tunjangan' => 'Tunjangan', 'skala' => 'Skala Gaji', 'import' => 'Import CSV'] as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === $key ? 'active' : '' }}"
                   href="{{ route('user.payroll.master', ['userId' => $userId, 'tab' => $key]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    @if($activeTab === 'aturan')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-equalizer-line me-2 text-primary"></i>Aturan Perhitungan</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.payroll.master.rules', $userId) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Hari Kerja Efektif / Bulan</label>
                            <input type="number" name="payroll_workdays" class="form-control" min="1" max="31"
                                   value="{{ old('payroll_workdays', $rules['payroll_workdays']) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tunjangan Kehadiran / Hari</label>
                            <input type="number" step="0.01" name="payroll_attendance_allowance_per_day" class="form-control"
                                   value="{{ old('payroll_attendance_allowance_per_day', $rules['payroll_attendance_allowance_per_day']) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Potongan / Menit Terlambat</label>
                            <input type="number" step="0.01" name="payroll_late_deduction_per_minute" class="form-control"
                                   value="{{ old('payroll_late_deduction_per_minute', $rules['payroll_late_deduction_per_minute']) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Potongan / Hari Alpa</label>
                            <input type="number" step="0.01" name="payroll_absent_deduction_per_day" class="form-control"
                                   value="{{ old('payroll_absent_deduction_per_day', $rules['payroll_absent_deduction_per_day']) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Potongan / Hari Izin & Sakit</label>
                            <input type="number" step="0.01" name="payroll_leave_deduction_per_day" class="form-control"
                                   value="{{ old('payroll_leave_deduction_per_day', $rules['payroll_leave_deduction_per_day']) }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="attendanceAllowance" name="payroll_attendance_allowance_enabled" value="1"
                                       {{ old('payroll_attendance_allowance_enabled', $rules['payroll_attendance_allowance_enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="attendanceAllowance">Aktifkan tunjangan kehadiran</label>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="usePercentage" name="payroll_use_percentage" value="1"
                                       {{ old('payroll_use_percentage', $rules['payroll_use_percentage']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="usePercentage">Kalikan persentase golongan</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">BPJS TK (%)</label>
                            <input type="number" step="0.01" name="payroll_bpjs_tk_percent" class="form-control"
                                   value="{{ old('payroll_bpjs_tk_percent', $rules['payroll_bpjs_tk_percent']) }}">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="bpjsTkEnabled" name="payroll_bpjs_tk_enabled" value="1"
                                       {{ old('payroll_bpjs_tk_enabled', $rules['payroll_bpjs_tk_enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="bpjsTkEnabled">Aktif</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">BPJS Kesehatan (%)</label>
                            <input type="number" step="0.01" name="payroll_bpjs_kes_percent" class="form-control"
                                   value="{{ old('payroll_bpjs_kes_percent', $rules['payroll_bpjs_kes_percent']) }}">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="bpjsKesEnabled" name="payroll_bpjs_kes_enabled" value="1"
                                       {{ old('payroll_bpjs_kes_enabled', $rules['payroll_bpjs_kes_enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="bpjsKesEnabled">Aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-light border small mt-3">
                        <i class="ri-information-line me-1 text-primary"></i>
                        Potongan hanya dihitung bila nilainya lebih dari 0. Tunjangan kehadiran dihitung dari jumlah hari berstatus <strong>hadir</strong>
                        pada absensi GTK bulan terkait. Persentase golongan memakai tabel <code>persentase_gaji</code> (opsional).
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Aturan</button>
                </form>
            </div>
        </div>
    @endif

    @if($activeTab === 'tunjangan')
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">{{ $editing ? 'Edit Tunjangan' : 'Tambah Tunjangan' }}</h5></div>
                    <div class="card-body">
                        <form action="{{ $editing
                            ? route('user.payroll.master.tunjangan.update', [$userId, $editing->id])
                            : route('user.payroll.master.tunjangan.store', $userId) }}" method="POST">
                            @csrf
                            @if($editing) @method('PUT') @endif
                            <div class="mb-3">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="Kode" class="form-control" required
                                       value="{{ old('Kode', $editing->Kode ?? '') }}" placeholder="TUNJ-PENDIDIKAN">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subjek / Nama Tunjangan <span class="text-danger">*</span></label>
                                <input type="text" name="Subjek" class="form-control" required
                                       value="{{ old('Subjek', $editing->Subjek ?? '') }}" placeholder="Tunjangan Pendidikan">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="Nominal" class="form-control" required
                                       value="{{ old('Nominal', $editing->Nominal ?? 0) }}">
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="tunjanganActive"
                                       {{ old('is_active', $editing->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="tunjanganActive">Aktif</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_attendance_based" value="1" id="tunjanganAttendance"
                                       {{ old('is_attendance_based', $editing->is_attendance_based ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="tunjanganAttendance">Berbasis kehadiran</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="per_hari" value="1" id="tunjanganPerDay"
                                       {{ old('per_hari', $editing->per_hari ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="tunjanganPerDay">Nominal per hari</label>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan</button>
                                @if($editing)
                                    <a href="{{ route('user.payroll.master', ['userId' => $userId, 'tab' => 'tunjangan']) }}" class="btn btn-light">Batal</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ri-gift-line me-2 text-primary"></i>Daftar Tunjangan</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th>Kode</th><th>Subjek</th><th class="text-end">Nominal</th><th>Basis</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                                <tbody>
                                    @forelse($tunjangan as $item)
                                        <tr>
                                            <td class="fw-medium">{{ $item->Kode }}</td>
                                            <td>{{ $item->Subjek }}</td>
                                            <td class="text-end">Rp {{ number_format((float) $item->Nominal, 0, ',', '.') }}</td>
                                            <td class="small text-muted">{{ $item->is_attendance_based ? ($item->per_hari ? 'Kehadiran / hari' : 'Kehadiran') : 'Tetap' }}</td>
                                            <td><span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}-subtle text-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                            <td class="text-end">
                                                <a href="{{ route('user.payroll.master', ['userId' => $userId, 'tab' => 'tunjangan', 'edit' => $item->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-edit-line"></i></a>
                                                <form action="{{ route('user.payroll.master.tunjangan.destroy', [$userId, $item->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus tunjangan ini?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-empty-state colspan="6" title="Belum ada tunjangan" message="Tambahkan komponen tunjangan tetap atau kehadiran." />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'skala')
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">Skala Guru</div><div class="fs-3 fw-bold">{{ $counts['guru'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">Skala Tendik</div><div class="fs-3 fw-bold">{{ $counts['tendik'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">Pendidikan</div><div class="fs-3 fw-bold">{{ $counts['pendidikan'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">Masa Kerja</div><div class="fs-3 fw-bold">{{ $counts['masakerja'] }}</div></div></div></div>
        </div>

        <div class="alert alert-info small">
            Baris skala gaji dapat diimpor melalui tab <strong>Import CSV</strong>. Nominal <code>golongan_1..5</code> dipilih berdasarkan
            golongan pendidikan (SMA=1, D3=2, S1/D4=3, S2=4, S3=5), baris dipilih berdasarkan masa kerja dan level.
        </div>

        @foreach(['Guru' => $scalesGuru, 'Tendik' => $scalesTendik] as $label => $scales)
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Skala Gaji {{ $label }}</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:420px;overflow:auto">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Masa Kerja</th><th>Level</th><th class="text-end">Gol 1</th><th class="text-end">Gol 2</th><th class="text-end">Gol 3</th><th class="text-end">Gol 4</th><th class="text-end">Gol 5</th></tr></thead>
                            <tbody>
                                @forelse($scales as $scale)
                                    <tr>
                                        <td>{{ $scale->masa_kerja }} th</td>
                                        <td>{{ $scale->level }}</td>
                                        @foreach([1,2,3,4,5] as $g)
                                            <td class="text-end">{{ number_format((float) $scale->{'golongan_'.$g}, 0, ',', '.') }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data. Import CSV untuk mengisi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    @if($activeTab === 'import')
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ri-upload-2-line me-2 text-primary"></i>Import Master Gaji (CSV)</h5></div>
                    <div class="card-body">
                        <form action="{{ route('user.payroll.master.import', $userId) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Jenis Data <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="guru">Skala Gaji Guru</option>
                                    <option value="tendik">Skala Gaji Tendik</option>
                                    <option value="salary_scal">Salary Scale (pendidikan + masa kerja)</option>
                                    <option value="pendidikan">Pemetaan Pendidikan → Golongan</option>
                                    <option value="masakerja">Pemetaan Masa Kerja → Level</option>
                                    <option value="persentase">Persentase Gaji per Golongan</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File CSV <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                                <div class="form-text">Gunakan template di samping agar urutan kolom sesuai.</div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="ri-upload-2-line me-1"></i>Import</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ri-download-2-line me-2 text-primary"></i>Template CSV</h5></div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @foreach(['guru' => 'Skala Gaji Guru', 'tendik' => 'Skala Gaji Tendik', 'salary_scal' => 'Salary Scale', 'pendidikan' => 'Pendidikan', 'masakerja' => 'Masa Kerja', 'persentase' => 'Persentase Gaji'] as $type => $label)
                                <a href="{{ route('user.payroll.master.template', ['userId' => $userId, 'type' => $type]) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="ri-download-line me-1"></i>{{ $label }}
                                </a>
                            @endforeach
                        </div>
                        <hr>
                        <div class="small text-muted">
                            <div class="fw-semibold mb-1">Catatan format:</div>
                            <div>Guru/Tendik: <code>masa_kerja,level,golongan_1..golongan_5</code></div>
                            <div>Salary Scale: <code>pendidikan,masa_kerja,gaji</code></div>
                            <div>Pendidikan: <code>strata,golongan</code></div>
                            <div>Masa Kerja: <code>masa_kerja,level</code></div>
                            <div>Persentase: <code>persentase_golongan_1..4</code></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
