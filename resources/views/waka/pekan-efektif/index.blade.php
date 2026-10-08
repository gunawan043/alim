@extends('waka.master')
@section('title') Pekan Efektif @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
    <style>
        .badge-soft-success { background: #d1fae5; color: #065f46; }
        .badge-soft-danger  { background: #fee2e2; color: #991b1b; }
        .badge-soft-warning { background: #fef3c7; color: #92400e; }
        .badge-soft-info    { background: #e0f2fe; color: #075985; }
        .badge-soft-secondary { background: #e2e8f0; color: #334155; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Pekan Efektif @endslot
        @slot('title') Daftar Pekan Efektif @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Daftar Pekan Efektif</h4>
            <a href="{{ route('waka.pekan-efektif.create') }}" class="btn btn-primary">
                <i class="ri-add-line align-middle me-1"></i> Tambah Pekan
            </a>
        </div>
    </div>

    {{-- Generate dari Kalender Pendidikan --}}
    <div class="card mb-3 border-primary-subtle">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <h6 class="mb-1"><i class="ri-calendar-event-line me-1 text-primary"></i> Generate dari Kalender Pendidikan</h6>
                    <p class="text-muted small mb-0">
                        Pekan efektif, hari efektif, minggu libur, dan minggu ujian dihitung otomatis
                        dari Kalender Pendidikan (tipe kegiatan <strong>Libur</strong>, <strong>Ujian</strong>, <strong>Kegiatan Sekolah</strong>).
                        Data lama pada tahun ajaran &amp; semester yang sama akan diganti.
                    </p>
                </div>
                <div class="col-md-7">
                    <form method="POST" action="{{ route('waka.pekan-efektif.generate') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-sm" required>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $selectedAyId == $ay->id ? 'selected' : '' }}>
                                        {{ $ay->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Semester</label>
                            <select name="semester" class="form-select form-select-sm" required>
                                <option value="1" {{ (int) $selectedSemester === 1 ? 'selected' : '' }}>Ganjil</option>
                                <option value="2" {{ (int) $selectedSemester === 2 ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="ri-magic-line me-1"></i> Generate Pekan Efektif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan turunan kalender --}}
    @if($ringkasan)
        @if(! ($ringkasan['is_persisted'] ?? false))
            <div class="alert alert-info py-2 small">
                <i class="ri-information-line me-1"></i>
                Pekan efektif untuk tahun ajaran &amp; semester ini belum digenerate.
                Ringkasan di bawah dihitung langsung dari Kalender Pendidikan ({{ $ringkasan['sumber'] ?? '-' }}).
            </div>
        @endif
        <div class="row mb-3">
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="text-muted small">Minggu Efektif</div>
                        <h4 class="mb-0 text-success">{{ $ringkasan['minggu_efektif'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="text-muted small">Hari Efektif</div>
                        <h4 class="mb-0 text-primary">{{ $ringkasan['total_hari_efektif'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="text-muted small">Minggu Libur</div>
                        <h4 class="mb-0 text-danger">{{ $ringkasan['minggu_libur'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="text-muted small">Minggu Ujian</div>
                        <h4 class="mb-0 text-warning">{{ $ringkasan['minggu_ujian'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Ringkasan semester --}}
    @if(isset($summary) && $summary->count())
        <div class="row mb-3">
            @foreach(['1'=>'Ganjil','2'=>'Genap'] as $key => $label)
                @if($summary->has($key))
                    @php $rows = $summary->get($key)->keyBy('jenis'); @endphp
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted small mb-2">Semester {{ $label }}</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($rows as $row)
                                        <span class="badge badge-soft-info fs-6 p-2">
                                            {{ \Illuminate\Support\Str::headline(str_replace('_',' ',$row->jenis)) }}:
                                            <strong>{{ $row->jumlah }}</strong>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('waka.pekan-efektif.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ request('academic_year_id')==$ay->id?'selected':'' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        <option value="1" {{ request('semester')=='1'?'selected':'' }}>Ganjil</option>
                        <option value="2" {{ request('semester')=='2'?'selected':'' }}>Genap</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Jenis</label>
                    <select name="jenis" class="form-select form-select-sm">
                        <option value="">— Semua —</option>
                        @foreach(['efektif'=>'Efektif','libur'=>'Libur','ujian'=>'Ujian','kegiatan_sekolah'=>'Kegiatan Sekolah','lainnya'=>'Lainnya'] as $v => $l)
                            <option value="{{ $v }}" {{ request('jenis')===$v?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary me-1 flex-grow-1"><i class="ri-search-line"></i> Filter</button>
                    <a href="{{ route('waka.pekan-efektif.index') }}" class="btn btn-sm btn-secondary"><i class="ri-refresh-line"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="text-muted small">Total: {{ $pekanList->total() }} pekan</p>
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Thn. Ajaran</th>
                        <th>Smt</th>
                        <th>Minggu</th>
                        <th>Periode</th>
                        <th>Hari Efektif</th>
                        <th>Jenis</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pekanList as $p)
                        <tr>
                            <td>{{ $p->academicYear?->name ?? '-' }}</td>
                            <td>{{ $p->semester == 1 ? 'Ganjil' : 'Genap' }}</td>
                            <td>
                                <strong>{{ $p->minggu_ke }}</strong>
                                @if($p->is_generated)
                                    <span class="badge bg-primary-subtle text-primary" style="font-size:0.6rem">Kaldik</span>
                                @endif
                            </td>
                            <td>{{ $p->tanggal_mulai?->format('d/m/Y') }} – {{ $p->tanggal_selesai?->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge {{ $p->hari_efektif > 0 ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                    {{ $p->hari_efektif }} hari
                                </span>
                            </td>
                            <td>
                                @php
                                    $map = ['efektif'=>'success','libur'=>'danger','ujian'=>'warning','kegiatan_sekolah'=>'info','lainnya'=>'secondary'];
                                    $cls = $map[$p->jenis] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $cls }}">{{ \Illuminate\Support\Str::headline(str_replace('_',' ',$p->jenis)) }}</span>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($p->keterangan, 40) ?: '—' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('waka.pekan-efektif.show', $p->id) }}" class="btn btn-outline-info"><i class="ri-eye-line"></i></a>
                                    <a href="{{ route('waka.pekan-efektif.edit', $p->id) }}" class="btn btn-outline-warning"><i class="ri-edit-line"></i></a>
                                    <form action="{{ route('waka.pekan-efektif.destroy', $p->id) }}" method="POST" class="d-inline form-delete">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-delete"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data. Gunakan tombol <strong>Generate Pekan Efektif</strong> di atas untuk membuat dari Kalender Pendidikan.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $pekanList->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const form = this.closest('form');
                Swal.fire({ title: 'Hapus pekan ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, hapus' })
                    .then((r) => { if (r.isConfirmed) form.submit(); });
            });
        });
    </script>
@endsection