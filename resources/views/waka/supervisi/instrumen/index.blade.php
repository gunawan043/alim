@extends('waka.master')
@section('title') Instrumen Supervisi @endsection

@section('css')
    @include('shared._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('waka.supervisi.index') }}">Supervisi</a> @endslot
        @slot('title') Instrumen Supervisi @endslot
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

    <div class="card border-0 bg-light mb-3">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center gap-2 small">
                <span class="fw-semibold text-muted me-1"><i class="ri-route-line me-1"></i>Alur instrumen:</span>
                <span class="badge bg-primary text-white">1. Tambah / Duplikat</span><i class="ri-arrow-right-s-line text-muted"></i>
                <span class="badge bg-white text-muted border">2. Atur Identitas</span><i class="ri-arrow-right-s-line text-muted"></i>
                <span class="badge bg-white text-muted border">3. Tambah Bagian</span><i class="ri-arrow-right-s-line text-muted"></i>
                <span class="badge bg-white text-muted border">4. Item &amp; Poin</span><i class="ri-arrow-right-s-line text-muted"></i>
                <span class="badge bg-white text-muted border">5. Aktif</span><i class="ri-arrow-right-s-line text-muted"></i>
                <span class="badge bg-white text-muted border">6. Nilai Supervisi</span>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                <div class="text-muted" style="font-size:.72rem">Instrumen bawaan tidak bisa diubah. Duplikat dulu ke satuan, lalu atur bagian/item/poin. Instrumen dipilih saat mengisi penilaian supervisi.</div>
                <a href="{{ route('waka.supervisi.create') }}" class="btn btn-sm btn-outline-primary"><i class="ri-add-line me-1"></i>Buat Supervisi</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0">Pengaturan Instrumen Supervisi</h5>
                    <p class="text-muted mb-0 small">Atur bagian, daftar item yang dinilai, dan poinnya. Instrumen bawaan bersifat baca-saja — duplikat ke satuan untuk menyesuaikan.</p>
                </div>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createInstrumenModal">
                    <i class="ri-add-line me-1"></i>Tambah Instrumen
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Instrumen</th>
                            <th style="width:130px">Jenis</th>
                            <th style="width:120px" class="text-center">Cakupan</th>
                            <th style="width:90px" class="text-center">Bagian</th>
                            <th style="width:90px" class="text-center">Item</th>
                            <th style="width:100px" class="text-center">Status</th>
                            <th style="width:180px" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($instrumen as $i)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $i->nama }}</span>
                                    @if($i->deskripsi)<small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($i->deskripsi, 90) }}</small>@endif
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">
                                        {{ \App\Models\SupervisiInstrumen::JENIS_OPTIONS[$i->jenis] ?? ucfirst($i->jenis) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($i->school_id)
                                        <span class="badge bg-primary-subtle text-primary">Satuan</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Bawaan</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $i->bagian_count ?? $i->bagian()->count() }}</td>
                                <td class="text-center">{{ $i->items_count ?? 0 }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $i->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $i->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('waka.supervisi-instrumen.edit', $i->id) }}" class="btn btn-sm btn-soft-primary me-1" title="Atur item & poin">
                                        <i class="ri-settings-3-line"></i> Atur
                                    </a>
                                    @if(! $i->school_id)
                                        <form action="{{ route('waka.supervisi-instrumen.duplikat', $i->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-soft-success" title="Duplikat ke satuan">
                                                <i class="ri-file-copy-line"></i> Duplikat
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7"
                                title="Belum ada instrumen"
                                message="Jalankan seeder instrumen bawaan atau tambahkan instrumen baru." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal tambah instrumen --}}
    <div class="modal fade" id="createInstrumenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('waka.supervisi-instrumen.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom-dashed">
                        <h5 class="modal-title"><i class="ri-add-line me-1"></i> Tambah Instrumen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Instrumen <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" maxlength="150" required placeholder="mis. Instrumen Supervisi — Guru Tahfidz">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis <span class="text-danger">*</span></label>
                            <select name="jenis" class="form-select" required>
                                @foreach(\App\Models\SupervisiInstrumen::JENIS_OPTIONS as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2" maxlength="1000"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
