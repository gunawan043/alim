@extends('layouts.master')

@section('title', 'Asuransi / BPJS')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Administrasi @endslot
        @slot('title') Asuransi / BPJS @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Total Kepesertaan', 'value' => number_format($stats['total']), 'icon' => 'ri-shield-check-line', 'color' => 'primary'],
            ['label' => 'Aktif', 'value' => number_format($stats['aktif']), 'icon' => 'ri-checkbox-circle-line', 'color' => 'success'],
            ['label' => 'Kedaluwarsa', 'value' => number_format($stats['kedaluwarsa']), 'icon' => 'ri-error-warning-line', 'color' => 'danger'],
            ['label' => 'Santri Terdaftar', 'value' => number_format($stats['santri']), 'icon' => 'ri-group-line', 'color' => 'info'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Kepesertaan Asuransi</h5>
                            <p class="text-muted mb-0 small">Data BPJS dan asuransi kesehatan santri.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap gap-2">
                                <form method="GET" class="d-flex gap-2"
                                      action="{{ route('user.uks.insurance.index', ['userId' => $userId]) }}">
                                    <input type="text" name="search" class="form-control" value="{{ $search }}"
                                           placeholder="Cari santri / no. kartu..." style="width: 230px;">
                                    <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                                </form>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#insuranceModal">
                                    <i class="ri-add-line me-1"></i> Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>
                        <a href="{{ route('user.uks.insurance.index', ['userId' => $userId]) }}"
                           class="filter-badge {{ ! $status ? 'active' : '' }}">Semua</a>
                        <a href="{{ route('user.uks.insurance.index', ['userId' => $userId, 'status' => 'aktif']) }}"
                           class="filter-badge {{ $status === 'aktif' ? 'active' : '' }}">Aktif</a>
                        <a href="{{ route('user.uks.insurance.index', ['userId' => $userId, 'status' => 'nonaktif']) }}"
                           class="filter-badge {{ $status === 'nonaktif' ? 'active' : '' }}">Nonaktif</a>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($insurances->isEmpty())
                        <x-empty-state icon="ri-shield-check-line" title="Belum ada data asuransi"
                                       message="Tambahkan kepesertaan BPJS atau asuransi santri." />
                    @else
                        <div class="table-container table-freeze">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Santri</th>
                                        <th>Provider</th>
                                        <th>No. Kartu</th>
                                        <th>Kelas</th>
                                        <th>Masa Berlaku</th>
                                        <th class="text-center" style="width: 110px;">Status</th>
                                        <th class="text-end" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($insurances as $insurance)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $insurance->student?->name ?? '-' }}</div>
                                                <div class="text-muted small">{{ $insurance->student?->nis ?: $insurance->student?->nisn ?: '-' }}</div>
                                            </td>
                                            <td>{{ $insurance->provider }}</td>
                                            <td class="font-monospace">{{ $insurance->card_number ?: '-' }}</td>
                                            <td>{{ $insurance->class_type ?: '-' }}</td>
                                            <td class="small">
                                                {{ $insurance->valid_from?->format('d/m/Y') ?? '-' }}
                                                s/d
                                                {{ $insurance->valid_until?->format('d/m/Y') ?? '-' }}
                                            </td>
                                            <td class="text-center">
                                                @php $expired = $insurance->isExpired(); @endphp
                                                <span class="badge {{ $expired ? 'bg-danger-subtle text-danger' : ($insurance->status === 'aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary') }}">
                                                    {{ $insurance->statusLabel() }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button type="button" class="btn btn-sm btn-soft-primary btn-edit-insurance"
                                                            data-bs-toggle="modal" data-bs-target="#editInsuranceModal"
                                                            data-id="{{ $insurance->id }}"
                                                            data-nama="{{ $insurance->student?->name }}"
                                                            data-provider="{{ $insurance->provider }}"
                                                            data-card="{{ $insurance->card_number }}"
                                                            data-class="{{ $insurance->class_type }}"
                                                            data-from="{{ $insurance->valid_from?->format('Y-m-d') }}"
                                                            data-until="{{ $insurance->valid_until?->format('Y-m-d') }}"
                                                            data-status="{{ $insurance->status }}"
                                                            data-notes="{{ $insurance->notes }}">
                                                        <i class="ri-edit-line"></i>
                                                    </button>
                                                    <form method="POST"
                                                          action="{{ route('user.uks.insurance.destroy', ['userId' => $userId, 'insuranceId' => $insurance->id]) }}"
                                                          data-confirm="Hapus data asuransi santri ini?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-soft-danger">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if($insurances->hasPages())
                    <div class="card-footer border-top-dashed">
                        {{ $insurances->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal tambah --}}
    <div class="modal fade" id="insuranceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.uks.insurance.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-shield-add-line me-1"></i> Tambah Asuransi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Santri <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">— Pilih santri —</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" @selected(old('student_id') === $student->id)>
                                            {{ $student->name }} ({{ $student->nis ?: $student->nisn ?: '-' }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Gunakan pencarian di atas untuk mempersempit daftar.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Provider <span class="text-danger">*</span></label>
                                <select name="provider" class="form-select" required>
                                    @foreach($providers as $value => $label)
                                        <option value="{{ $value }}" @selected(old('provider') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. Kartu</label>
                                <input type="text" name="card_number" class="form-control" maxlength="60" value="{{ old('card_number') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kelas</label>
                                <input type="text" name="class_type" class="form-control" maxlength="30" value="{{ old('class_type') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Berlaku Dari</label>
                                <input type="date" name="valid_from" class="form-control" value="{{ old('valid_from') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Berlaku Sampai</label>
                                <input type="date" name="valid_until" class="form-control" value="{{ old('valid_until') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', 'aktif') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-control" maxlength="255" value="{{ old('notes') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal edit --}}
    <div class="modal fade" id="editInsuranceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="editInsuranceForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-edit-line me-1"></i> Ubah Asuransi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Santri</label>
                            <input type="text" class="form-control" id="editNama" disabled>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Provider <span class="text-danger">*</span></label>
                                <select name="provider" class="form-select" id="editProvider" required>
                                    @foreach($providers as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. Kartu</label>
                                <input type="text" name="card_number" class="form-control" maxlength="60" id="editCard">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kelas</label>
                                <input type="text" name="class_type" class="form-control" maxlength="30" id="editClass">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Berlaku Dari</label>
                                <input type="date" name="valid_from" class="form-control" id="editFrom">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Berlaku Sampai</label>
                                <input type="date" name="valid_until" class="form-control" id="editUntil">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" id="editStatus" required>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-control" maxlength="255" id="editNotes">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('editInsuranceForm');
    var baseUrl = '{{ url('/'.$userId.'/uks/asuransi') }}';

    document.querySelectorAll('.btn-edit-insurance').forEach(function (button) {
        button.addEventListener('click', function () {
            form.action = baseUrl + '/' + this.dataset.id;
            document.getElementById('editNama').value = this.dataset.nama || '';
            document.getElementById('editProvider').value = this.dataset.provider || '';
            document.getElementById('editCard').value = this.dataset.card || '';
            document.getElementById('editClass').value = this.dataset.class || '';
            document.getElementById('editFrom').value = this.dataset.from || '';
            document.getElementById('editUntil').value = this.dataset.until || '';
            document.getElementById('editStatus').value = this.dataset.status || 'aktif';
            document.getElementById('editNotes').value = this.dataset.notes || '';
        });
    });
});
</script>
@endpush
