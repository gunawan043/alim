@extends('layouts.master')
@section('title') Timeline Kegiatan — {{ $asset->asset_name }} @endsection

@push('css')
<style>
    .xl-wrap { overflow: auto; max-height: 66vh; border: 1px solid #e5e7eb; border-radius: .5rem; }
    .xl-table { border-collapse: separate; border-spacing: 0; min-width: 1180px; margin-bottom: 0; }
    .xl-table thead th {
        position: sticky; top: 0; z-index: 3; background: #f1f5f9; border: 1px solid #e2e8f0;
        font-size: .7rem; letter-spacing: .03em; text-transform: uppercase; color: #475569;
        padding: .45rem .5rem; white-space: nowrap;
    }
    .xl-table tbody td { border: 1px solid #e5e7eb; padding: 0; vertical-align: middle; background: #fff; }
    .xl-table tbody td.row-no { background: #f8fafc; text-align: center; color: #94a3b8; font-size: .75rem; width: 42px; }
    .xl-cell {
        border: 0; border-radius: 0; width: 100%; min-width: 104px; padding: .38rem .5rem;
        font-size: .83rem; background: transparent; color: #1f2937;
    }
    .xl-cell:focus { outline: 2px solid #3b82f6; outline-offset: -2px; background: #eff6ff; }
    .xl-cell[type="date"] { min-width: 128px; }
    .xl-cell.is-text { min-width: 260px; }
    .xl-table tr.row-new td { background: #fffbeb; }
    .xl-table tr.row-del td { background: #fef2f2; }
    .xl-table tr.row-del .xl-cell { color: #b91c1c; text-decoration: line-through; }
    .xl-table tr.row-del td.row-no { color: #b91c1c; font-weight: 700; }
    .xl-actions { width: 44px; text-align: center; }
    .xl-hint { font-size: .75rem; color: #64748b; }
    .kbd { display: inline-block; border: 1px solid #cbd5e1; border-bottom-width: 2px; border-radius: 4px; padding: 0 4px; font-size: .7rem; background: #f8fafc; }
    .dirty-badge { display: none; }
    .dirty-badge.show { display: inline-block; }
</style>
@endpush

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.aset.index') }}">Aset</a> @endslot
    @slot('li_3') <a href="{{ route('sarpras.aset.show', ['id' => $asset->id]) }}">{{ Str::limit($asset->asset_name, 30) }}</a> @endslot
    @slot('title') Timeline Kegiatan @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Timeline belum tersimpan:</div>
        <ul class="mb-0 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-1">{{ $asset->asset_name }}</h5>
            <div class="text-muted small">
                <code>{{ $asset->asset_code ?? '-' }}</code>
                &middot; {{ $asset->room?->room_name ?? 'Tanpa ruang' }}
                &middot; {{ $asset->category?->name ?? 'Tanpa kategori' }}
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('sarpras.aset.show', ['id' => $asset->id]) }}" class="btn btn-sm btn-light">
                <i class="ri-arrow-left-line me-1"></i> Detail Aset
            </a>
            <a href="{{ route('sarpras.assets.passport', ['uuid' => $asset->id]) }}" class="btn btn-sm btn-outline-primary">
                <i class="ri-passport-line me-1"></i> Asset Passport
            </a>
            <a href="{{ route('sarpras.aset.timeline.export', ['id' => $asset->id]) }}" class="btn btn-sm btn-outline-success">
                <i class="ri-file-excel-2-line me-1"></i> Ekspor CSV
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="xl-hint">
                Cara pakai: klik sel lalu ketik seperti Excel. <span class="kbd">Enter</span> turun satu baris,
                <span class="kbd">Tab</span> ke kolom kanan. Tempel blok dari Excel dengan
                <span class="kbd">Ctrl/Cmd + V</span>.
                <span class="badge bg-warning-subtle text-warning dirty-badge ms-1">Ada perubahan belum disimpan</span>
            </div>
            @if($canEdit)
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnPaste">
                        <i class="ri-clipboard-line me-1"></i> Tempel dari Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnAddRow">
                        <i class="ri-add-line me-1"></i> Tambah Baris
                    </button>
                </div>
            @endif
        </div>

        <form id="timelineForm" method="POST" action="{{ route('sarpras.aset.timeline.save', ['id' => $asset->id]) }}">
            @csrf

            <div class="xl-wrap">
                <table class="table xl-table" id="timelineTable">
                    <thead>
                        <tr>
                            <th style="width:42px">#</th>
                            <th>Tanggal <span class="text-danger">*</span></th>
                            <th>Jenis <span class="text-danger">*</span></th>
                            <th>Uraian Pekerjaan</th>
                            <th>Pelaksana</th>
                            <th>Biaya (Rp)</th>
                            <th>Kondisi Sebelum</th>
                            <th>Kondisi Sesudah</th>
                            <th>Jadwal Berikutnya</th>
                            <th>Catatan</th>
                            <th class="xl-actions"></th>
                        </tr>
                    </thead>
                    <tbody id="timelineBody">
                        @forelse($rows as $index => $row)
                            <tr data-id="{{ $row->id }}">
                                <td class="row-no">{{ $index + 1 }}</td>
                                <td>
                                    <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $row->id }}">
                                    <input type="date" class="xl-cell" data-col="performed_date"
                                           name="rows[{{ $index }}][performed_date]"
                                           value="{{ optional($row->performed_date)->format('Y-m-d') }}" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <select class="xl-cell" data-col="maintenance_type" name="rows[{{ $index }}][maintenance_type]" {{ $canEdit ? '' : 'disabled' }}>
                                        @foreach($typeOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($row->maintenance_type === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="xl-cell is-text" data-col="work_description"
                                           name="rows[{{ $index }}][work_description]" value="{{ $row->work_description }}"
                                           placeholder="Contoh: ganti lampu TL 36W" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="text" class="xl-cell" data-col="performed_by_name"
                                           name="rows[{{ $index }}][performed_by_name]" value="{{ $row->performed_by_name }}"
                                           placeholder="Nama petugas" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="number" min="0" step="1000" class="xl-cell" data-col="cost"
                                           name="rows[{{ $index }}][cost]" value="{{ $row->cost !== null ? (int) $row->cost : '' }}"
                                           placeholder="0" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <select class="xl-cell" data-col="condition_before" name="rows[{{ $index }}][condition_before]" {{ $canEdit ? '' : 'disabled' }}>
                                        <option value="">-</option>
                                        @foreach($conditionOptions as $condition)
                                            <option value="{{ $condition }}" @selected($row->condition_before === $condition)>{{ ucfirst(str_replace('_', ' ', $condition)) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="xl-cell" data-col="condition_after" name="rows[{{ $index }}][condition_after]" {{ $canEdit ? '' : 'disabled' }}>
                                        <option value="">-</option>
                                        @foreach($conditionOptions as $condition)
                                            <option value="{{ $condition }}" @selected($row->condition_after === $condition)>{{ ucfirst(str_replace('_', ' ', $condition)) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="date" class="xl-cell" data-col="next_due_date"
                                           name="rows[{{ $index }}][next_due_date]"
                                           value="{{ optional($row->next_due_date)->format('Y-m-d') }}" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="text" class="xl-cell is-text" data-col="notes"
                                           name="rows[{{ $index }}][notes]" value="{{ $row->notes }}"
                                           placeholder="Kendala / keterangan" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td class="xl-actions">
                                    @if($canEdit)
                                        <button type="button" class="btn btn-sm btn-link text-danger js-del-row" title="Hapus baris">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="11" class="text-center text-muted py-4">
                                    Belum ada riwayat. Klik <strong>Tambah Baris</strong> atau tempel data dari Excel.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($canEdit)
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('sarpras.aset.show', ['id' => $asset->id]) }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-success">
                        <i class="ri-save-3-line me-1"></i> Simpan Timeline
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ri-tools-line me-1"></i> Riwayat Perbaikan (dari penugasan)</h5>
        <span class="badge bg-secondary-subtle text-secondary">{{ $repairs->count() }} tiket</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No. Tiket</th>
                        <th>Judul</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th>Diajukan</th>
                        <th>Selesai</th>
                        <th>Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($repairs as $repair)
                        <tr>
                            <td><a href="{{ route('sarpras.repair.show', ['repairId' => $repair->id]) }}">{{ $repair->request_number }}</a></td>
                            <td>{{ $repair->title }}</td>
                            <td><span class="badge bg-light text-dark">{{ ucfirst($repair->priority) }}</span></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $repair->status)) }}</td>
                            <td>{{ optional($repair->created_at)->format('d/m/Y') }}</td>
                            <td>{{ optional($repair->completed_at)->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $repair->labor_cost ? 'Rp '.number_format((float) $repair->labor_cost, 0, ',', '.') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">Belum ada tiket perbaikan untuk aset ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canEdit)
<div class="modal fade" id="pasteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tempel Data dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">
                    Salin blok sel dari Excel/Google Sheets lalu tempel di bawah. Urutan kolom:
                    <strong>Tanggal, Jenis, Uraian, Pelaksana, Biaya, Kondisi Sebelum, Kondisi Sesudah, Jadwal Berikutnya, Catatan</strong>.
                    Tanggal sebaiknya format <code>YYYY-MM-DD</code>.
                </p>
                <textarea id="pasteArea" class="form-control font-monospace" rows="10" placeholder="2026-11-01	Perbaikan	Ganti lampu TL	Pak Andi	150000	rusak_ringan	baik	2027-05-01	"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnApplyPaste">Tambahkan ke Grid</button>
            </div>
        </div>
    </div>
</div>
@endif

<template id="rowTemplate">
    <tr class="row-new">
        <td class="row-no"></td>
        <td><input type="date" class="xl-cell" data-col="performed_date"></td>
        <td>
            <select class="xl-cell" data-col="maintenance_type">
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" class="xl-cell is-text" data-col="work_description" placeholder="Uraian pekerjaan"></td>
        <td><input type="text" class="xl-cell" data-col="performed_by_name" placeholder="Nama petugas"></td>
        <td><input type="number" min="0" step="1000" class="xl-cell" data-col="cost" placeholder="0"></td>
        <td>
            <select class="xl-cell" data-col="condition_before">
                <option value="">-</option>
                @foreach($conditionOptions as $condition)
                    <option value="{{ $condition }}">{{ ucfirst(str_replace('_', ' ', $condition)) }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="xl-cell" data-col="condition_after">
                <option value="">-</option>
                @foreach($conditionOptions as $condition)
                    <option value="{{ $condition }}">{{ ucfirst(str_replace('_', ' ', $condition)) }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="date" class="xl-cell" data-col="next_due_date"></td>
        <td><input type="text" class="xl-cell is-text" data-col="notes" placeholder="Kendala / keterangan"></td>
        <td class="xl-actions">
            <button type="button" class="btn btn-sm btn-link text-danger js-del-row" title="Hapus baris">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('script')
<script>
(function () {
    const canEdit = @json($canEdit);
    const form = document.getElementById('timelineForm');
    const body = document.getElementById('timelineBody');
    const template = document.getElementById('rowTemplate');
    const table = document.getElementById('timelineTable');
    const cols = ['performed_date', 'maintenance_type', 'work_description', 'performed_by_name', 'cost',
                  'condition_before', 'condition_after', 'next_due_date', 'notes'];

    let rowCounter = {{ $rows->count() }};
    let dirty = false;

    if (!canEdit) return;

    function markDirty() {
        dirty = true;
        document.querySelector('.dirty-badge')?.classList.add('show');
    }

    function emptyRowNotice() {
        const notice = document.getElementById('emptyRow');
        if (notice) notice.remove();
    }

    function renumber() {
        [...body.querySelectorAll('tr')].forEach((tr, i) => {
            const cell = tr.querySelector('.row-no');
            if (cell) cell.textContent = i + 1;
        });
    }

    function bindNames(tr, index) {
        tr.dataset.index = index;
        tr.querySelectorAll('[data-col]').forEach((input) => {
            input.name = 'rows[' + index + '][' + input.dataset.col + ']';
        });
    }

    function addRow(values) {
        emptyRowNotice();
        const tr = template.content.firstElementChild.cloneNode(true);
        bindNames(tr, rowCounter++);
        body.appendChild(tr);

        if (values) {
            cols.forEach((col, i) => {
                const cell = tr.querySelector('[data-col="' + col + '"]');
                if (cell && values[i] !== undefined) setCellValue(cell, values[i]);
            });
        }

        renumber();
        markDirty();

        return tr;
    }

    function setCellValue(cell, raw) {
        const value = (raw === undefined || raw === null) ? '' : String(raw).trim();

        if (cell.tagName === 'SELECT') {
            const match = [...cell.options].find((opt) =>
                opt.value.toLowerCase() === value.toLowerCase() ||
                opt.textContent.trim().toLowerCase() === value.toLowerCase()
            );
            if (match) cell.value = match.value;

            return;
        }

        if (cell.type === 'date') {
            cell.value = normalizeDate(value);

            return;
        }

        cell.value = value;
    }

    function normalizeDate(value) {
        if (!value) return '';
        if (/^d{4}-d{2}-d{2}$/.test(value)) return value;

        const dmy = value.match(/^(d{1,2})[/-.](d{1,2})[/-.](d{4})$/);
        if (dmy) {
            return dmy[3] + '-' + dmy[2].padStart(2, '0') + '-' + dmy[1].padStart(2, '0');
        }

        const parsed = new Date(value);
        if (!isNaN(parsed)) return parsed.toISOString().slice(0, 10);

        return '';
    }

    function fillGrid(startCell, grid) {
        const startRow = startCell.closest('tr');
        const startColIndex = cols.indexOf(startCell.dataset.col);
        if (startColIndex < 0) return;

        let currentRow = startRow;
        let added = 0;

        grid.forEach((line, rowOffset) => {
            if (rowOffset > 0) {
                currentRow = currentRow.nextElementSibling;
                if (!currentRow) {
                    currentRow = addRow();
                    added++;
                }
            }

            line.forEach((value, colOffset) => {
                const col = cols[startColIndex + colOffset];
                if (!col) return;
                const cell = currentRow.querySelector('[data-col="' + col + '"]');
                if (cell) setCellValue(cell, value);
            });
        });

        markDirty();
        renumber();
    }

    function parseClipboard(text) {
        return text.replace(//g, '').split('
')
            .filter((line, i, arr) => !(i === arr.length - 1 && line.trim() === ''))
            .map((line) => line.split('	'));
    }

    document.getElementById('btnAddRow')?.addEventListener('click', () => {
        const tr = addRow();
        tr.querySelector('[data-col="performed_date"]')?.focus();
    });

    body.addEventListener('click', (event) => {
        const btn = event.target.closest('.js-del-row');
        if (!btn) return;

        const tr = btn.closest('tr');
        if (tr.dataset.id) {
            tr.classList.toggle('row-del');
            let flag = tr.querySelector('input[name$="[delete]"]');
            if (!flag) {
                flag = document.createElement('input');
                flag.type = 'hidden';
                flag.name = 'rows[' + tr.dataset.index + '][delete]';
                flag.value = '1';
                tr.appendChild(flag);
            } else {
                flag.value = flag.value === '1' ? '0' : '1';
            }
            markDirty();
        } else {
            tr.remove();
            renumber();
            markDirty();
        }
    });

    table.addEventListener('paste', (event) => {
        const cell = event.target.closest('[data-col]');
        if (!cell) return;

        const text = (event.clipboardData || window.clipboardData).getData('text');
        if (!text || (!text.includes('	') && !text.includes('
'))) return;

        event.preventDefault();
        fillGrid(cell, parseClipboard(text));
    });

    table.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        const cell = event.target.closest('[data-col]');
        if (!cell) return;

        event.preventDefault();
        const col = cell.dataset.col;
        let next = cell.closest('tr').nextElementSibling;

        if (!next) {
            next = addRow();
        }

        next.querySelector('[data-col="' + col + '"]')?.focus();
    });

    form?.addEventListener('input', markDirty);
    form?.addEventListener('change', markDirty);

    form?.addEventListener('submit', () => {
        // Buang baris baru yang benar-benar kosong agar tidak kena validasi tanggal.
        body.querySelectorAll('tr').forEach((tr) => {
            if (tr.dataset.id || tr.classList.contains('row-del')) return;
            const hasValue = [...tr.querySelectorAll('[data-col]')].some((cell) => String(cell.value).trim() !== '');
            if (!hasValue) tr.remove();
        });

        dirty = false;
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    const pasteModalEl = document.getElementById('pasteModal');
    const pasteModal = pasteModalEl ? new bootstrap.Modal(pasteModalEl) : null;

    document.getElementById('btnPaste')?.addEventListener('click', () => pasteModal?.show());

    document.getElementById('btnApplyPaste')?.addEventListener('click', () => {
        const text = document.getElementById('pasteArea').value;
        if (!text.trim()) return;

        parseClipboard(text).forEach((line) => addRow(line));

        document.getElementById('pasteArea').value = '';
        pasteModal?.hide();
    });
})();
</script>
@endpush
