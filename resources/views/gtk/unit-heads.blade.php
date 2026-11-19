@extends('layouts.master')
@section('title')Kepala & Wakil Satuan Kerja @endsection

@section('css')
<style>
    .select2-container { width: 100% !important; }
    .head-table td { vertical-align: middle; }
    .head-name { font-weight: 600; }
    .head-empty { color: #98a2b3; font-style: italic; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') GTK @endslot
    @slot('title') Kepala &amp; Wakil Satuan Kerja @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i><strong>Gagal menyimpan:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="alert alert-info py-2 px-3 small">
    <i class="ri-information-line me-1"></i>
    <strong>Kepala dan wakil tidak bisa diubah lewat import Excel.</strong>
    Gunakan halaman ini untuk menetapkan atau mengganti mereka — jabatan dan role GTK
    ikut diperbarui otomatis. Data pribadi kepala/wakil tetap dapat diperbarui lewat
    tombol <em>Edit data</em>.
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-0">
                <i class="ri-user-star-line me-1 text-primary"></i>Kepala &amp; Wakil Satuan Kerja
            </h5>
            <p class="text-muted mb-0 small">Satu kepala dan satu wakil untuk setiap satuan kerja.</p>
        </div>
        <div class="col-sm-auto" style="min-width:260px;">
            <input type="text" id="unitSearch" class="form-control form-control-sm"
                   placeholder="Cari satuan kerja...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover head-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:220px;">Satuan Kerja</th>
                        <th style="min-width:220px;">Kepala</th>
                        <th style="min-width:220px;">Wakil</th>
                        <th style="width:210px;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                        <tr data-unit-name="{{ strtolower($unit->name) }}">
                            <td>
                                <div class="head-name">{{ $unit->name }}</div>
                                @if($unit->code)
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $unit->code }}</span>
                                @endif
                            </td>
                            <td>
                                @if($unit->head)
                                    <div class="head-name">{{ $unit->head->name }}</div>
                                @elseif($unit->fallback_head)
                                    <div class="head-name">{{ $unit->fallback_head->name }}</div>
                                    <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">dari jabatan — belum ditetapkan</span>
                                @else
                                    <span class="head-empty">Belum ditetapkan</span>
                                @endif
                            </td>
                            <td>
                                @if($unit->vice)
                                    <div class="head-name">{{ $unit->vice->name }}</div>
                                @elseif($unit->fallback_vice)
                                    <div class="head-name">{{ $unit->fallback_vice->name }}</div>
                                    <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">dari jabatan — belum ditetapkan</span>
                                @else
                                    <span class="head-empty">Belum ditetapkan</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-atur"
                                        data-bs-toggle="modal"
                                        data-bs-target="#headModal"
                                        data-unit-id="{{ $unit->id }}"
                                        data-unit-name="{{ $unit->name }}"
                                        data-head-id="{{ ($unit->head ?? $unit->fallback_head)?->id }}"
                                        data-vice-id="{{ ($unit->vice ?? $unit->fallback_vice)?->id }}">
                                    <i class="ri-edit-line me-1"></i>Atur
                                </button>
                                @if($unit->head || $unit->fallback_head)
                                    @php $headUser = $unit->head ?? $unit->fallback_head; @endphp
                                    <a href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $headUser->id]) }}"
                                       class="btn btn-sm btn-light" title="Edit data kepala">
                                        Edit kepala
                                    </a>
                                @endif
                                @if($unit->vice || $unit->fallback_vice)
                                    @php $viceUser = $unit->vice ?? $unit->fallback_vice; @endphp
                                    <a href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $viceUser->id]) }}"
                                       class="btn btn-sm btn-light" title="Edit data wakil">
                                        Edit wakil
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada satuan kerja aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL ATUR KEPALA & WAKIL --}}
<div class="modal fade" id="headModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="headForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">
                            <i class="ri-user-star-line me-1 text-primary"></i>Atur Kepala &amp; Wakil
                        </h5>
                        <small class="text-muted" id="modalUnitName">-</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kepala Satuan Kerja</label>
                        <select name="head_user_id" id="modalHead" class="form-select">
                            <option value="">— Tidak ada —</option>
                            @foreach($gtkOptions as $gtk)
                                <option value="{{ $gtk->id }}">
                                    {{ $gtk->name }}@if($gtk->employment?->nupy) — {{ $gtk->employment->nupy }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Wakil Kepala</label>
                        <select name="vice_head_user_id" id="modalVice" class="form-select">
                            <option value="">— Tidak ada —</option>
                            @foreach($gtkOptions as $gtk)
                                <option value="{{ $gtk->id }}">
                                    {{ $gtk->name }}@if($gtk->employment?->nupy) — {{ $gtk->employment->nupy }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-warning py-2 px-3 small mb-0 mt-3">
                        <i class="ri-error-warning-line me-1"></i>
                        Menyimpan di sini juga memperbarui <strong>jabatan</strong> dan
                        <strong>role</strong> GTK yang bersangkutan, supaya menu kepala/wakil
                        langsung muncul saat mereka login. Jabatan lama dipulihkan bila
                        penetapan dilepas.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-3-line me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const form = document.getElementById('headForm');
    const modalEl = document.getElementById('headModal');
    let currentHead = '';
    let currentVice = '';

    modalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        currentHead = button.dataset.headId || '';
        currentVice = button.dataset.viceId || '';
        document.getElementById('modalUnitName').textContent = button.dataset.unitName || '-';
        form.action = '{{ route('user.gtk.kepala-wakil.update', ['userId' => $userId, 'workUnitId' => '__UNIT__']) }}'
            .replace('__UNIT__', button.dataset.unitId);
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        $('#modalHead, #modalVice').select2({ dropdownParent: $('#headModal'), width: '100%' });
        $('#modalHead').val(currentHead).trigger('change');
        $('#modalVice').val(currentVice).trigger('change');
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if ($('#modalHead').hasClass('select2-hidden-accessible')) {
            $('#modalHead, #modalVice').select2('destroy');
        }
    });

    document.getElementById('unitSearch').addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.head-table tbody tr[data-unit-name]').forEach(function (row) {
            row.style.display = row.dataset.unitName.includes(term) ? '' : 'none';
        });
    });
})();
</script>
@endsection
