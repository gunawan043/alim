@extends('waka.master')
@section('title') Atur Instrumen Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @php $readOnly = $instrumen->school_id === null; @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('waka.supervisi-instrumen.index') }}">Instrumen Supervisi</a> @endslot
        @slot('title') {{ $instrumen->nama }} @endslot
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

    @if($readOnly)
        <div class="alert alert-warning py-2 px-3 small" role="alert">
            <i class="ri-lock-line me-1"></i>
            Instrumen bawaan bersifat <strong>baca-saja</strong>. Duplikat ke satuan untuk mengubah item &amp; poinnya.
            <form action="{{ route('waka.supervisi-instrumen.duplikat', $instrumen->id) }}" method="POST" class="d-inline ms-1">
                @csrf
                <button class="btn btn-sm btn-success"><i class="ri-file-copy-line me-1"></i>Duplikat ke Satuan</button>
            </form>
        </div>
    @endif

    {{-- Identitas instrumen --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="ri-information-line text-primary me-1"></i> Identitas Instrumen</h5>
            <a href="{{ route('waka.supervisi-instrumen.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
        </div>
        <div class="card-body">
            @if($readOnly)
                <div class="row g-3 small">
                    <div class="col-md-6"><span class="text-muted">Nama:</span> <strong>{{ $instrumen->nama }}</strong></div>
                    <div class="col-md-3"><span class="text-muted">Jenis:</span> {{ \App\Models\SupervisiInstrumen::JENIS_OPTIONS[$instrumen->jenis] ?? $instrumen->jenis }}</div>
                    <div class="col-md-3"><span class="text-muted">Status:</span> {{ $instrumen->is_active ? 'Aktif' : 'Nonaktif' }}</div>
                    @if($instrumen->deskripsi)<div class="col-12"><span class="text-muted">Deskripsi:</span> {{ $instrumen->deskripsi }}</div>@endif
                </div>
            @else
                <form action="{{ route('waka.supervisi-instrumen.update', $instrumen->id) }}" method="POST" class="row g-2 align-items-end">
                    @csrf @method('PUT')
                    <div class="col-md-5">
                        <label class="form-label small text-muted mb-1">Nama Instrumen</label>
                        <input type="text" name="nama" class="form-control form-control-sm" value="{{ $instrumen->nama }}" maxlength="150" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Jenis</label>
                        <select name="jenis" class="form-select form-select-sm">
                            @foreach(\App\Models\SupervisiInstrumen::JENIS_OPTIONS as $k => $v)
                                <option value="{{ $k }}" {{ $instrumen->jenis === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                            <option value="lainnya" {{ $instrumen->jenis === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Deskripsi</label>
                        <input type="text" name="deskripsi" class="form-control form-control-sm" value="{{ $instrumen->deskripsi }}" maxlength="1000">
                    </div>
                    <div class="col-md-1">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $instrumen->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small">Aktif</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i>Simpan Identitas</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- Bagian & item --}}
    @foreach($instrumen->bagian as $bagian)
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="card-title mb-0">
                        <span class="badge bg-primary-subtle text-primary me-1">{{ $bagian->kode ?: '-' }}</span>
                        {{ $bagian->nama }}
                        <span class="text-muted small ms-1">({{ $bagian->items->count() }} item)</span>
                    </h5>
                    @unless($readOnly)
                        <div class="d-flex align-items-center gap-2">
                            <form action="{{ route('waka.supervisi-instrumen.bagian.update', $bagian->id) }}" method="POST" class="d-flex align-items-center gap-1">
                                @csrf @method('PUT')
                                <input type="text" name="kode" class="form-control form-control-sm" value="{{ $bagian->kode }}" style="width:70px" placeholder="Kode">
                                <input type="text" name="nama" class="form-control form-control-sm" value="{{ $bagian->nama }}" style="width:220px" required>
                                <input type="number" name="urutan" class="form-control form-control-sm" value="{{ $bagian->urutan }}" style="width:70px" min="0" max="99">
                                <input type="hidden" name="is_active" value="1">
                                <button class="btn btn-sm btn-soft-primary" title="Simpan bagian"><i class="ri-save-line"></i></button>
                            </form>
                            <button type="button" class="btn btn-sm btn-soft-danger"
                                    data-bs-toggle="modal" data-bs-target="#deleteBagianModal"
                                    data-action="{{ route('waka.supervisi-instrumen.bagian.destroy', $bagian->id) }}"
                                    data-name="{{ $bagian->kode }} {{ $bagian->nama }}">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    @endunless
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px" class="text-center">No</th>
                                <th>Item yang Dinilai</th>
                                <th style="width:110px" class="text-center">Poin</th>
                                <th style="width:100px" class="text-center">Ya/Tidak</th>
                                <th style="width:90px" class="text-center">Aktif</th>
                                <th style="width:190px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bagian->items as $idx => $item)
                                <tr>
                                    <td class="text-center">{{ $idx + 1 }}</td>
                                    <td>
                                        @if($readOnly)
                                            {{ $item->teks }}
                                        @else
                                            <input form="itemForm{{ $item->id }}" type="text" name="teks" value="{{ $item->teks }}" class="form-control form-control-sm" maxlength="500" required>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($readOnly)
                                            {{ rtrim(rtrim(number_format((float) $item->bobot, 2, ',', '.'), '0'), ',') }}
                                        @else
                                            <input form="itemForm{{ $item->id }}" type="number" name="bobot" value="{{ $item->bobot }}" min="0" max="100" step="0.5" class="form-control form-control-sm text-center">
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($readOnly)
                                            {{ $item->butuh_kondisi ? 'Ya' : '—' }}
                                        @else
                                            <input form="itemForm{{ $item->id }}" type="checkbox" name="butuh_kondisi" value="1" class="form-check-input" {{ $item->butuh_kondisi ? 'checked' : '' }}>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($readOnly)
                                            {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                        @else
                                            <input form="itemForm{{ $item->id }}" type="checkbox" name="is_active" value="1" class="form-check-input" {{ $item->is_active ? 'checked' : '' }}>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @unless($readOnly)
                                            <form id="itemForm{{ $item->id }}" action="{{ route('waka.supervisi-instrumen.item.update', $item->id) }}" method="POST" class="d-none">
                                                @csrf @method('PUT')
                                            </form>
                                            <button form="itemForm{{ $item->id }}" type="submit" class="btn btn-sm btn-soft-primary me-1" title="Simpan item"><i class="ri-save-line"></i></button>

                                            <form action="{{ route('waka.supervisi-instrumen.item.move', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="direction" value="up">
                                                <button class="btn btn-sm btn-light" title="Naik"><i class="ri-arrow-up-line"></i></button>
                                            </form>
                                            <form action="{{ route('waka.supervisi-instrumen.item.move', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="direction" value="down">
                                                <button class="btn btn-sm btn-light" title="Turun"><i class="ri-arrow-down-line"></i></button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-soft-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteItemModal"
                                                    data-action="{{ route('waka.supervisi-instrumen.item.destroy', $item->id) }}"
                                                    data-name="{{ \Illuminate\Support\Str::limit($item->teks, 60) }}">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        @endunless
                                    </td>
                                </tr>

                                {{-- Sub item --}}
                                @foreach($item->subItems as $subIdx => $sub)
                                    <tr>
                                        <td class="text-center text-muted">{{ chr(97 + $subIdx) }}.</td>
                                        <td class="ps-4">
                                            @if($readOnly)
                                                {{ $sub->teks }}
                                            @else
                                                <input form="itemForm{{ $sub->id }}" type="text" name="teks" value="{{ $sub->teks }}" class="form-control form-control-sm" maxlength="500" required>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($readOnly)
                                                {{ rtrim(rtrim(number_format((float) $sub->bobot, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <input form="itemForm{{ $sub->id }}" type="number" name="bobot" value="{{ $sub->bobot }}" min="0" max="100" step="0.5" class="form-control form-control-sm text-center">
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($readOnly)
                                                {{ $sub->butuh_kondisi ? 'Ya' : '—' }}
                                            @else
                                                <input form="itemForm{{ $sub->id }}" type="checkbox" name="butuh_kondisi" value="1" class="form-check-input" {{ $sub->butuh_kondisi ? 'checked' : '' }}>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($readOnly)
                                                {{ $sub->is_active ? 'Aktif' : 'Nonaktif' }}
                                            @else
                                                <input form="itemForm{{ $sub->id }}" type="checkbox" name="is_active" value="1" class="form-check-input" {{ $sub->is_active ? 'checked' : '' }}>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @unless($readOnly)
                                                <form id="itemForm{{ $sub->id }}" action="{{ route('waka.supervisi-instrumen.item.update', $sub->id) }}" method="POST" class="d-none">
                                                    @csrf @method('PUT')
                                                </form>
                                                <button form="itemForm{{ $sub->id }}" type="submit" class="btn btn-sm btn-soft-primary me-1" title="Simpan sub-item"><i class="ri-save-line"></i></button>

                                                <form action="{{ route('waka.supervisi-instrumen.item.move', $sub->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="direction" value="up">
                                                    <button class="btn btn-sm btn-light" title="Naik"><i class="ri-arrow-up-line"></i></button>
                                                </form>
                                                <form action="{{ route('waka.supervisi-instrumen.item.move', $sub->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="direction" value="down">
                                                    <button class="btn btn-sm btn-light" title="Turun"><i class="ri-arrow-down-line"></i></button>
                                                </form>

                                                <button type="button" class="btn btn-sm btn-soft-danger"
                                                        data-bs-toggle="modal" data-bs-target="#deleteItemModal"
                                                        data-action="{{ route('waka.supervisi-instrumen.item.destroy', $sub->id) }}"
                                                        data-name="{{ \Illuminate\Support\Str::limit($sub->teks, 60) }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach

                                @unless($readOnly)
                                    {{-- Tambah sub-item --}}
                                    <tr class="table-light">
                                        <td class="text-center text-muted"><i class="ri-corner-down-left-line"></i></td>
                                        <td colspan="5">
                                            <form action="{{ route('waka.supervisi-instrumen.item.store', $bagian->id) }}" method="POST" class="row g-1 align-items-center">
                                                @csrf
                                                <input type="hidden" name="parent_item_id" value="{{ $item->id }}">
                                                <div class="col-md-6">
                                                    <input type="text" name="teks" class="form-control form-control-sm" placeholder="Tambah sub-item untuk: {{ \Illuminate\Support\Str::limit($item->teks, 50) }}" maxlength="500" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" name="bobot" value="4" min="0" max="100" step="0.5" class="form-control form-control-sm text-center" title="Poin">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="butuh_kondisi" value="1" checked class="form-check-input">
                                                        <label class="form-check-label small">Ya/Tidak</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <button class="btn btn-sm btn-soft-success w-100"><i class="ri-add-line me-1"></i>Sub</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endunless
                            @endforeach
                        </tbody>
                        @unless($readOnly)
                            <tfoot class="table-light">
                                <tr>
                                    <td class="text-center text-muted"><i class="ri-add-line"></i></td>
                                    <td colspan="5">
                                        <form action="{{ route('waka.supervisi-instrumen.item.store', $bagian->id) }}" method="POST" class="row g-1 align-items-center">
                                            @csrf
                                            <div class="col-md-6">
                                                <input type="text" name="teks" class="form-control form-control-sm" placeholder="Tambah item baru pada bagian {{ $bagian->kode ?: $bagian->nama }}..." maxlength="500" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="bobot" value="4" min="0" max="100" step="0.5" class="form-control form-control-sm text-center" title="Poin">
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-check">
                                                    <input type="checkbox" name="butuh_kondisi" value="1" checked class="form-check-input">
                                                    <label class="form-check-label small">Ya/Tidak</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-sm btn-success w-100"><i class="ri-add-line me-1"></i>Tambah</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            </tfoot>
                        @endunless
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Tambah bagian --}}
    @unless($readOnly)
        <div class="card mb-3">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-layout-grid-line text-primary me-1"></i> Tambah Bagian</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('waka.supervisi-instrumen.bagian.store', $instrumen->id) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Kode</label>
                        <input type="text" name="kode" class="form-control form-control-sm" maxlength="10" placeholder="mis. IIC">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-muted mb-1">Nama Bagian <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control form-control-sm" maxlength="190" required placeholder="mis. PENUTUP">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Urutan</label>
                        <input type="number" name="urutan" class="form-control form-control-sm" min="0" max="99">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success btn-sm w-100"><i class="ri-add-line me-1"></i>Tambah Bagian</button>
                    </div>
                </form>
            </div>
        </div>
    @endunless

    {{-- Modal hapus item & bagian (template lord-icon) --}}
    <form id="deleteItemForm" method="POST" action="" class="d-none">@csrf @method('DELETE')</form>
    <x-modal-confirm id="deleteItemModal"
        icon="https://cdn.lordicon.com/tdrtiskw.json"
        primary-color="#f7b84b" secondary-color="#005981"
        title="Hapus Item?"
        message="Item <strong id=&quot;deleteItemLabel&quot;>ini</strong> beserta sub-itemnya akan dihapus dari instrumen."
        form-id="deleteItemForm"
        submit-label="Ya, Hapus"
        submit-icon="ri-delete-bin-line"
        submit-class="btn-danger" />

    <form id="deleteBagianForm" method="POST" action="" class="d-none">@csrf @method('DELETE')</form>
    <x-modal-confirm id="deleteBagianModal"
        icon="https://cdn.lordicon.com/tdrtiskw.json"
        primary-color="#f7b84b" secondary-color="#005981"
        title="Hapus Bagian?"
        message="Bagian <strong id=&quot;deleteBagianLabel&quot;>ini</strong> beserta seluruh itemnya akan dihapus."
        form-id="deleteBagianForm"
        submit-label="Ya, Hapus"
        submit-icon="ri-delete-bin-line"
        submit-class="btn-danger" />

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function bindDelete(modalId, formId, labelId) {
                var modal = document.getElementById(modalId);
                if (!modal) return;
                modal.addEventListener('show.bs.modal', function (event) {
                    var trigger = event.relatedTarget;
                    if (!trigger) return;
                    var form = document.getElementById(formId);
                    if (form) form.setAttribute('action', trigger.getAttribute('data-action') || '');
                    var label = document.getElementById(labelId);
                    if (label) label.textContent = trigger.getAttribute('data-name') || 'ini';
                });
            }

            bindDelete('deleteItemModal', 'deleteItemForm', 'deleteItemLabel');
            bindDelete('deleteBagianModal', 'deleteBagianForm', 'deleteBagianLabel');
        });
    </script>
    @endpush
@endsection
