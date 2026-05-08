@extends('layouts.master')
@section('title') Ajukan Request GTK @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('li_2') Daftar Request GTK @endslot
        @slot('title') Ajukan Request @endslot
    @endcomponent

    {{-- Tab navigation --}}
    <ul class="nav nav-tabs mb-3" id="requestTypeTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $type === 'procurement' ? 'active' : '' }}"
                id="tab-procurement" data-bs-toggle="tab" data-bs-target="#procurement"
                type="button" role="tab">
                <i class="ri-file-add-line me-1"></i>Pengajuan GTK
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $type === 'trial' ? 'active' : '' }}"
                id="tab-trial" data-bs-toggle="tab" data-bs-target="#trial"
                type="button" role="tab">
                <i class="ri-user-add-line me-1"></i>Pengangkatan Percobaan
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $type === 'status_increase' ? 'active' : '' }}"
                id="tab-status_increase" data-bs-toggle="tab" data-bs-target="#status_increase"
                type="button" role="tab">
                <i class="ri-arrow-up-line me-1"></i>Kenaikan Status GTK
            </button>
        </li>
    </ul>

    <div class="tab-content" id="requestTypeTabContent">

        {{-- ── TAB 1: Pengadaan GTK ─────────────────────────────── --}}
        <div class="tab-pane fade {{ $type === 'procurement' ? 'show active' : '' }}"
            id="procurement" role="tabpanel">
            <form method="POST" action="{{ route('user.gtk-requests.store', ['userId' => $userId]) }}" id="form-procurement">
                @csrf
                <input type="hidden" name="type" value="procurement">

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Formulir Pengajuan GTK</h5>
                        <small class="text-muted">Analisis Kebutuhan GTK per Satuan Kerja</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Satuan Kerja <span class="text-danger">*</span></label>
                                <select name="work_unit_id" class="form-control" required>
                                    <option value="">Pilih Satuan Kerja</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-control" required>
                                    <option value="">Pilih Tahun Ajaran</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}">{{ $ay->name }} ({{ strtoupper($ay->semester) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan / Keterangan</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Keterangan tambahan jika ada..."></textarea>
                            </div>
                        </div>

                        {{-- Analisis Kebutuhan GTK table --}}
                        <h6 class="mb-3 border-bottom pb-2">Analisis Kebutuhan GTK</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="analisis-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:40px">No</th>
                                        <th>Jabatan / Posisi</th>
                                        <th class="text-center" style="width:90px">Kebutuhan Ideal</th>
                                        <th class="text-center" style="width:90px">GTK yang Ada</th>
                                        <th>Kualifikasi Minimal</th>
                                        <th class="text-center" style="width:90px">Kebutuhan Tambahan</th>
                                        <th>Keterangan</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="analisis-tbody">
                                    <tr class="analisis-row">
                                        <td class="text-center align-middle row-num">1</td>
                                        <td><input type="text" name="items[0][jabatan]" class="form-control form-control-sm" placeholder="cth: Guru Matematika"></td>
                                        <td><input type="number" name="items[0][kebutuhan_ideal]" class="form-control form-control-sm text-center" value="0" min="0"></td>
                                        <td><input type="number" name="items[0][gtk_yang_ada]" class="form-control form-control-sm text-center" value="0" min="0"></td>
                                        <td><input type="text" name="items[0][kualifikasi_minimal]" class="form-control form-control-sm" placeholder="S1 Pendidikan..."></td>
                                        <td><input type="number" name="items[0][kebutuhan_tambahan]" class="form-control form-control-sm text-center" value="0" min="0"></td>
                                        <td><input type="text" name="items[0][keterangan]" class="form-control form-control-sm" placeholder="Opsional"></td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-soft-danger remove-row"><i class="ri-delete-bin-line"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-light mt-2" id="add-analisis-row">
                            <i class="ri-add-line me-1"></i>Tambah Baris
                        </button>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Draft</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── TAB 2: Pengangkatan GTK Percobaan ──────────────── --}}
        <div class="tab-pane fade {{ $type === 'trial' ? 'show active' : '' }}"
            id="trial" role="tabpanel">
            <form method="POST" action="{{ route('user.gtk-requests.store', ['userId' => $userId]) }}" id="form-trial">
                @csrf
                <input type="hidden" name="type" value="trial">

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Surat Pengangkatan GTK Percobaan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Satuan Kerja <span class="text-danger">*</span></label>
                                <select name="work_unit_id" class="form-control" required>
                                    <option value="">Pilih Satuan Kerja</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. Lampiran</label>
                                <input type="text" name="letter_attachment" class="form-control" placeholder="001">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. Surat</label>
                                <input type="text" name="letter_number" class="form-control" placeholder="XX/YYYY">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Perihal</label>
                                <input type="text" name="letter_subject" class="form-control" value="Pengangkatan Pegawai Percobaan" placeholder="Pengangkatan Pegawai Percobaan">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ditetapkan di</label>
                                <input type="text" name="established_city" class="form-control" placeholder="Mataram">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Pada Tanggal</label>
                                <input type="date" name="established_date" class="form-control">
                            </div>
                        </div>

                        {{-- GTK Trial table --}}
                        <h6 class="mb-3 border-bottom pb-2">Daftar GTK yang Diangkat</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="trial-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:40px">No</th>
                                        <th style="width:120px">NUPY</th>
                                        <th>Nama Lengkap</th>
                                        <th>Tugas / Jabatan</th>
                                        <th>Lembaga</th>
                                        <th>Status GTK</th>
                                        <th style="width:110px">TMT</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="trial-tbody">
                                    <tr class="trial-row">
                                        <td class="text-center align-middle row-num">1</td>
                                        <td><input type="text" name="items[0][nupy]" class="form-control form-control-sm" placeholder="-"></td>
                                        <td>
                                            <select name="items[0][gtk_profile_id]" class="form-select form-select-sm gtk-profile-select">
                                                <option value="">-- pilih --</option>
                                                @foreach($gtkProfiles as $gp)
                                                    <option value="{{ $gp->id }}" data-name="{{ $gp->user?->name ?? '-' }}">{{ $gp->user?->name ?? '-' }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="items[0][nama]" class="nama-input" placeholder="Ketik nama manual">
                                        </td>
                                        <td><input type="text" name="items[0][tugas]" class="form-control form-control-sm" placeholder="cth: Guru Matematika"></td>
                                        <td><input type="text" name="items[0][lembaga]" class="form-control form-control-sm" placeholder="Nama lembaga"></td>
                                        <td>
                                            <select name="items[0][status_gtk]" class="form-select form-select-sm">
                                                <option value="Percobaan">Percobaan</option>
                                                <option value="Kontrak">Kontrak</option>
                                                <option value="Khusus">Khusus</option>
                                                <option value="Tidak Tetap">Tidak Tetap</option>
                                            </select>
                                        </td>
                                        <td><input type="date" name="items[0][tmt]" class="form-control form-control-sm"></td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-soft-danger remove-row"><i class="ri-delete-bin-line"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-light mt-2" id="add-trial-row">
                            <i class="ri-add-line me-1"></i>Tambah Baris
                        </button>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Draft</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── TAB 3: Kenaikan Status GTK ─────────────────────── --}}
        <div class="tab-pane fade {{ $type === 'status_increase' ? 'show active' : '' }}"
            id="status_increase" role="tabpanel">
            <form method="POST" action="{{ route('user.gtk-requests.store', ['userId' => $userId]) }}" id="form-status_increase">
                @csrf
                <input type="hidden" name="type" value="status_increase">

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Permohonan Kenaikan Status GTK</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Satuan Kerja <span class="text-danger">*</span></label>
                                <select name="work_unit_id" class="form-control" required>
                                    <option value="">Pilih Satuan Kerja</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. Surat</label>
                                <input type="text" name="letter_number" class="form-control" placeholder="XX/YYYY">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ditetapkan di</label>
                                <input type="text" name="established_city" class="form-control" placeholder="Mataram">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Pada Tanggal</label>
                                <input type="date" name="established_date" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Keterangan tambahan..."></textarea>
                            </div>
                        </div>

                        {{-- GTK Status Increase table --}}
                        <h6 class="mb-3 border-bottom pb-2">Daftar GTK yang Dimohonkan Kenaikan Status</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="status-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:40px">No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Tugas / Jabatan</th>
                                        <th>Lembaga</th>
                                        <th>Status Saat Ini</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="status-tbody">
                                    <tr class="status-row">
                                        <td class="text-center align-middle row-num">1</td>
                                        <td>
                                            <select name="items[0][gtk_profile_id]" class="form-select form-select-sm gtk-profile-select">
                                                <option value="">-- pilih --</option>
                                                @foreach($gtkProfiles as $gp)
                                                    <option value="{{ $gp->id }}" data-name="{{ $gp->user?->name ?? '-' }}">{{ $gp->user?->name ?? '-' }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="items[0][nama]" class="nama-input">
                                        </td>
                                        <td><input type="text" name="items[0][tugas]" class="form-control form-control-sm" placeholder="Jabatan"></td>
                                        <td><input type="text" name="items[0][lembaga]" class="form-control form-control-sm" placeholder="Lembaga"></td>
                                        <td>
                                            <select name="items[0][status_gtk]" class="form-select form-select-sm">
                                                <option value="Percobaan">Percobaan</option>
                                                <option value="Kontrak">Kontrak</option>
                                                <option value="Khusus">Khusus</option>
                                                <option value="Tidak Tetap">Tidak Tetap</option>
                                                <option value="Per-jam">Per-jam</option>
                                            </select>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-soft-danger remove-row"><i class="ri-delete-bin-line"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-light mt-2" id="add-status-row">
                            <i class="ri-add-line me-1"></i>Tambah Baris
                        </button>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan Draft</button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    <div class="mt-3">
        <a href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}" class="btn btn-light">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Dynamic row helpers ────────────────────────────────────────
    function cloneRow(tbodyId, template) {
        const tbody = document.getElementById(tbodyId);
        const rows = tbody.querySelectorAll('tr');
        const newIdx = rows.length;
        const newRow = document.createElement('tr');
        newRow.innerHTML = template.replace(/\[0\]/g, '[' + newIdx + ']')
                                     .replace(/row-num\">1</g, 'row-num\">' + (newIdx + 1) + '<');
        // Fix row number
        newRow.querySelector('.row-num').textContent = newIdx + 1;
        tbody.appendChild(newRow);
    }

    function renumberRows(tbody) {
        tbody.querySelectorAll('.row-num').forEach((el, i) => el.textContent = i + 1);
    }

    // ── Analisis table (Pengadaan GTK) ────────────────────────────
    const analisisTbody = document.getElementById('analisis-tbody');
    const analisisTemplate = analisisTbody.querySelector('tr').outerHTML;
    document.getElementById('add-analisis-row').addEventListener('click', () => {
        cloneRow('analisis-tbody', analisisTemplate);
    });
    analisisTbody.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            const rows = this.querySelectorAll('tr');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                renumberRows(this);
            }
        }
    });

    // ── Trial table (Pengangkatan Percobaan) ───────────────────────
    const trialTbody = document.getElementById('trial-tbody');
    const trialTemplate = trialTbody.querySelector('tr').outerHTML;
    document.getElementById('add-trial-row').addEventListener('click', () => {
        cloneRow('trial-tbody', trialTemplate);
    });
    trialTbody.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            const rows = this.querySelectorAll('tr');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                renumberRows(this);
            }
        }
    });

    // ── Status table (Kenaikan Status) ────────────────────────────
    const statusTbody = document.getElementById('status-tbody');
    const statusTemplate = statusTbody.querySelector('tr').outerHTML;
    document.getElementById('add-status-row').addEventListener('click', () => {
        cloneRow('status-tbody', statusTemplate);
    });
    statusTbody.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            const rows = this.querySelectorAll('tr');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                renumberRows(this);
            }
        }
    });

    // ── Sync GTK profile name ──────────────────────────────────────
    document.addEventListener('change', function (e) {
        if (e.target.matches('[name$="[gtk_profile_id]"]')) {
            const selected = e.target.options[e.target.selectedIndex];
            const namaInput = e.target.closest('tr').querySelector('.nama-input');
            if (namaInput) {
                // Use data-name attribute for clean GTK name
                namaInput.value = selected.dataset.name || selected.textContent.trim() || '';
            }
        }
    });

    // Also sync on load for pre-selected values
    document.querySelectorAll('.gtk-profile-select').forEach(function (sel) {
        if (sel.value) {
            const selected = sel.options[sel.selectedIndex];
            const namaInput = sel.closest('tr').querySelector('.nama-input');
            if (namaInput) namaInput.value = selected.dataset.name || selected.textContent.trim();
        }
    });
});
</script>
@endsection
