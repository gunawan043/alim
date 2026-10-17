@extends('layouts.master')
@section('title') Detail Request GTK @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') GTK @endslot
        @slot('li_2') Daftar Request GTK @endslot
        @slot('title') Detail Request @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">

            {{-- Header Card --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ $gtkRequest->type_text }}</h5>
                        <small class="text-muted">No. {{ $gtkRequest->letter_number ?? '-' }}</small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-{{ $gtkRequest->status_color }}-subtle text-{{ $gtkRequest->status_color }} fs-6">
                            {{ $gtkRequest->status_text }}
                        </span>
                        @if($gtkRequest->status === 'draft')
                            <form action="{{ route('user.gtk-requests.submit', $gtkRequest->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="ri-send-plane-line me-1"></i>Ajukan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted small mb-0">Satuan Kerja</label>
                            <p class="fw-semibold mb-0">{{ $gtkRequest->workUnit?->name ?? '-' }}</p>
                        </div>
                        @if($gtkRequest->type === 'procurement')
                            <div class="col-md-4">
                                <label class="text-muted small mb-0">Tahun Ajaran</label>
                                <p class="fw-semibold mb-0">{{ $gtkRequest->academicYear?->name ?? '-' }}</p>
                            </div>
                        @endif
                        @if($gtkRequest->type === 'trial')
                            <div class="col-md-4">
                                <label class="text-muted small mb-0">Lampiran / No. Surat</label>
                                <p class="fw-semibold mb-0">{{ $gtkRequest->letter_attachment ?? '-' }} / {{ $gtkRequest->letter_number ?? '-' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small mb-0">Perihal</label>
                                <p class="fw-semibold mb-0">{{ $gtkRequest->letter_subject ?? '-' }}</p>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="text-muted small mb-0">Pemohon</label>
                            <p class="fw-semibold mb-0">{{ $gtkRequest->requestedBy?->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-0">Tanggal Pengajuan</label>
                            <p class="fw-semibold mb-0">{{ $gtkRequest->created_at->format('d/m/Y') }}</p>
                        </div>
                        @if($gtkRequest->established_city || $gtkRequest->established_date)
                            <div class="col-md-4">
                                <label class="text-muted small mb-0">Ditetapkan</label>
                                <p class="fw-semibold mb-0">{{ $gtkRequest->established_city ?? '' }} {{ $gtkRequest->established_date ? ', ' . $gtkRequest->established_date->format('d/m/Y') : '' }}</p>
                            </div>
                        @endif
                        @if($gtkRequest->notes)
                            <div class="col-12">
                                <label class="text-muted small mb-0">Catatan</label>
                                <p class="mb-0">{{ $gtkRequest->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- TAB 1: Procurement — Analisis Kebutuhan GTK --}}
            @if($gtkRequest->type === 'procurement')
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="ri-file-chart-line me-1"></i>Analisis Kebutuhan GTK</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:40px">No</th>
                                        <th>Jabatan / Posisi</th>
                                        <th class="text-center">Kebutuhan Ideal</th>
                                        <th class="text-center">GTK yang Ada</th>
                                        <th>Kualifikasi Minimal</th>
                                        <th class="text-center">Kebutuhan Tambahan</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($gtkRequest->items as $i => $item)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td><strong>{{ $item->jabatan ?? '-' }}</strong></td>
                                            <td class="text-center">{{ $item->kebutuhan_ideal ?? 0 }}</td>
                                            <td class="text-center">{{ $item->gtk_yang_ada ?? 0 }}</td>
                                            <td>{{ $item->kualifikasi_minimal ?? '-' }}</td>
                                            <td class="text-center">
                                                @if(($item->kebutuhan_tambah ?? 0) > 0)
                                                    <span class="badge bg-danger-subtle text-danger">{{ $item->kebutuhan_tambahan }}</span>
                                                @else
                                                    {{ $item->kebutuhan_tambahan ?? 0 }}
                                                @endif
                                            </td>
                                            <td>{{ $item->keterangan ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-3">Tidak ada data.</td></tr>
                                    @endforelse
                                </tbody>
                                @if($gtkRequest->items->count())
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="2" class="fw-bold">Total Kebutuhan Tambahan</td>
                                            <td class="text-center fw-bold">{{ $gtkRequest->items->sum('kebutuhan_ideal') }}</td>
                                            <td class="text-center fw-bold">{{ $gtkRequest->items->sum('gtk_yang_ada') }}</td>
                                            <td></td>
                                            <td class="text-center fw-bold text-danger">{{ $gtkRequest->items->sum('kebutuhan_tambahan') }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 2: Trial — Pengangkatan GTK Percobaan --}}
            @if($gtkRequest->type === 'trial')
                <div class="card">
                    <div class="card-body">
                        <div class="mb-0">
                            <p class="mb-1"><strong>Tentang:</strong> {{ $gtkRequest->letter_subject ?? 'Pengangkatan Pegawai Percobaan' }}</p>
                            @if($gtkRequest->established_city && $gtkRequest->established_date)
                                <p class="mb-1"><strong>Ditetapkan di {{ $gtkRequest->established_city }}</strong>, pada tanggal {{ $gtkRequest->established_date->format('d F Y') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="card-header"><h6 class="mb-0"><i class="ri-user-add-line me-1"></i>Daftar GTK yang Diangkat</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:40px">No</th>
                                        <th>NUPY</th>
                                        <th>Nama Lengkap</th>
                                        <th>Tugas / Jabatan</th>
                                        <th>Lembaga</th>
                                        <th>Status GTK</th>
                                        <th>TMT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($gtkRequest->items as $i => $item)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td><code>{{ $item->nupy ?? '-' }}</code></td>
                                            <td><strong>{{ $item->nama ?? '-' }}</strong></td>
                                            <td>{{ $item->tugas ?? '-' }}</td>
                                            <td>{{ $item->lembaga ?? '-' }}</td>
                                            <td><span class="badge bg-warning-subtle text-warning">{{ $item->status_gtk ?? '-' }}</span></td>
                                            <td>{{ $item->tmt ? $item->tmt->format('d/m/Y') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-3">Tidak ada data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 3: Status Increase — Permohonan Kenaikan Status GTK --}}
            @if($gtkRequest->type === 'status_increase')
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="ri-arrow-up-line me-1"></i>Surat Permohonan Kenaikan Status GTK</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Nomor:</strong> {{ $gtkRequest->letter_number ?? '-' }}</p>
                        <p class="mb-1"><strong>Ditetapkan di:</strong> {{ $gtkRequest->established_city ?? '-' }}</p>
                        @if($gtkRequest->established_date)
                            <p class="mb-1"><strong>Pada Tanggal:</strong> {{ $gtkRequest->established_date->format('d F Y') }}</p>
                        @endif
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:40px">No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Tugas / Jabatan</th>
                                        <th>Lembaga</th>
                                        <th>Status GTK Saat Ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($gtkRequest->items as $i => $item)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td><strong>{{ $item->nama ?? '-' }}</strong></td>
                                            <td>{{ $item->tugas ?? '-' }}</td>
                                            <td>{{ $item->lembaga ?? '-' }}</td>
                                            <td><span class="badge bg-info-subtle text-info">{{ $item->status_gtk ?? '-' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($gtkRequest->notes)
                            <div class="mt-3">
                                <strong>Catatan:</strong>
                                <p class="mb-0">{{ $gtkRequest->notes }}</p>
                            </div>
                        @endif
                        <div class="row mt-4">
                            <div class="col-6 text-center">
                                <p class="mb-5">Mengetahui,<br><strong>Atasan Langsung</strong></p>
                                <p>(_________________________)</p>
                            </div>
                            <div class="col-6 text-center">
                                <p class="mb-1">Mataram, {{ now()->format('d F Y') }}</p>
                                <p class="mb-5"><strong>Pemohon</strong></p>
                                <p>(_________________________)</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-3">
                <a href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
@endsection
