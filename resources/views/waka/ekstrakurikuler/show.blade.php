@extends('waka.master')
@section('title') Detail Ekstrakurikuler @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
        .badge-soft-info    { background: #e0f2fe; color: #075985; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ekstrakurikuler @endslot
        @slot('li_2') <a href="{{ route('waka.ekstrakurikuler.index') }}">Daftar</a> @endslot
        @slot('title') {{ $ekskul->nama }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-5 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Informasi Kegiatan</h5>
                    <a href="{{ route('waka.ekstrakurikuler.edit', $ekskul->id) }}" class="btn btn-sm btn-warning"><i class="ri-edit-line"></i> Edit</a>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><th style="width:160px">Nama</th><td>{{ $ekskul->nama }}</td></tr>
                        <tr><th>Pembimbing</th><td>{{ $ekskul->gtk?->name ?? $ekskul->pembimbing ?? '-' }}</td></tr>
                        <tr><th>Hari / Jam</th><td>{{ $ekskul->hari ?? '-' }} {{ $ekskul->jam_mulai ? ' / '.$ekskul->jam_mulai.'–'.$ekskul->jam_selesai : '' }}</td></tr>
                        <tr><th>Lokasi</th><td>{{ $ekskul->lokasi ?? '-' }}</td></tr>
                        <tr><th>Periode</th><td>{{ $ekskul->tanggal_mulai?->format('d/m/Y') ?? '-' }} s/d {{ $ekskul->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td></tr>
                        <tr><th>Kuota</th><td>{{ $ekskul->kuota ?? '-' }}</td></tr>
                        <tr><th>Status</th>
                            <td>
                                @if($ekskul->status === 'aktif')
                                    <span class="badge badge-soft-success">Aktif</span>
                                @else
                                    <span class="badge badge-soft-danger">Berhenti</span>
                                @endif
                            </td>
                        </tr>
                        <tr><th>Deskripsi</th><td>{!! nl2br(e($ekskul->deskripsi)) ?: '<span class="text-muted">-</span>' !!}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Anggota ({{ $anggota->count() }})</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAnggotaModal">
                        <i class="ri-add-line"></i> Tambah Anggota
                    </button>
                </div>
                <div class="card-body">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Santri</th>
                                <th>NISN</th>
                                <th>Tgl. Gabung</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($anggota as $i => $a)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $a->student?->name ?? '-' }}</td>
                                    <td>{{ $a->student?->nisn ?? '-' }}</td>
                                    <td>{{ $a->tanggal_bergabung?->format('d/m/Y') ?? '-' }}</td>
                                    <td>
                                        @if($a->status === 'aktif')
                                            <span class="badge badge-soft-success">Aktif</span>
                                        @elseif($a->status === 'lulus')
                                            <span class="badge badge-soft-info">Lulus</span>
                                        @else
                                            <span class="badge badge-soft-danger">Keluar</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                onclick="editAnggota({{ $a->id }}, '{{ $a->tanggal_bergabung?->format('Y-m-d') }}', '{{ $a->tanggal_keluar?->format('Y-m-d') }}', '{{ $a->status }}', `{{ $a->keterangan }}`)">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form action="{{ route('waka.ekstrakurikuler-anggota.destroy', $a->id) }}" method="POST" class="d-inline form-delete-anggota">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-anggota"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada anggota.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Add anggota modal --}}
    <div class="modal fade" id="addAnggotaModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('waka.ekstrakurikuler-anggota.store', $ekskul->id) }}" method="POST" autocomplete="off">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Anggota</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Santri <span class="text-danger">*</span></label>
                            <input type="text" name="student_search" id="student_search" class="form-control" placeholder="Ketik nama / NISN..." autocomplete="off" required>
                            <input type="hidden" name="student_id" id="student_id" required>
                            <div id="student_results" class="list-group mt-1" style="max-height:200px;overflow:auto"></div>
                            @error('student_id')<span class="text-danger">{{ $message }}</span>@enderror
                            @error('student_id')<span class="text-danger">{{ session('errors')->first('student_id') }}</span>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bergabung</label>
                            <input type="date" name="tanggal_bergabung" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" selected>Aktif</option>
                                <option value="keluar">Keluar</option>
                                <option value="lulus">Lulus</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit anggota modal --}}
    <div class="modal fade" id="editAnggotaModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="form-edit-anggota" method="POST" autocomplete="off">
                @csrf @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Keanggotaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bergabung</label>
                            <input type="date" name="tanggal_bergabung" id="edit_tgl_gabung" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Keluar</label>
                            <input type="date" name="tanggal_keluar" id="edit_tgl_keluar" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="aktif">Aktif</option>
                                <option value="keluar">Keluar</option>
                                <option value="lulus">Lulus</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.btn-delete-anggota').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const form = this.closest('form');
                Swal.fire({ title: 'Hapus anggota ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, hapus' })
                    .then((r) => { if (r.isConfirmed) form.submit(); });
            });
        });

        function editAnggota(id, tglGabung, tglKeluar, status, keterangan) {
            const base = "{{ route('waka.ekstrakurikuler-anggota.update', ['ekskul'=>$ekskul->id, 'anggota'=>':id']) }}";
            document.getElementById('form-edit-anggota').action = base.replace(':id', id);
            document.getElementById('edit_tgl_gabung').value = tglGabung || '';
            document.getElementById('edit_tgl_keluar').value = tglKeluar || '';
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_keterangan').value = keterangan || '';
            new bootstrap.Modal(document.getElementById('editAnggotaModal')).show();
        }

        // student search (lightweight: hits an AJAX endpoint if exists)
        const sb = document.getElementById('student_search');
        const sh = document.getElementById('student_id');
        const list = document.getElementById('student_results');
        let searchTimer = null;

        sb && sb.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const q = sb.value.trim();
            if (q.length < 2) { list.innerHTML = ''; return; }
            searchTimer = setTimeout(async () => {
                try {
                    const url = "{{ url('/api/students/search') }}?q=" + encodeURIComponent(q);
                    const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' } });
                    if (!r.ok) { list.innerHTML = '<div class="list-group-item text-muted small">Tidak ada hasil.</div>'; return; }
                    const data = await r.json();
                    if (!data.length) { list.innerHTML = '<div class="list-group-item text-muted small">Tidak ada hasil.</div>'; return; }
                    list.innerHTML = data.map(s => `
                        <a href="#" class="list-group-item list-group-item-action" data-id="${s.id}">
                            <strong>${s.name}</strong> ${s.nisn ? `<small class="text-muted">— ${s.nisn}</small>` : ''}
                        </a>`).join('');
                    list.querySelectorAll('a').forEach(a => {
                        a.addEventListener('click', e => {
                            e.preventDefault();
                            sh.value = a.dataset.id;
                            sb.value = a.querySelector('strong').textContent;
                            list.innerHTML = '';
                        });
                    });
                } catch (err) {
                    list.innerHTML = '<div class="list-group-item text-danger small">Gagal memuat data.</div>';
                }
            }, 250);
        });

        @if($errors->any())
            new bootstrap.Modal(document.getElementById('addAnggotaModal')).show();
        @endif
    </script>
@endsection