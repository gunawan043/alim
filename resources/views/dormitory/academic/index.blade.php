@extends('layouts.master')

@section('title', 'Integrasi Akademik → Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Integrasi Akademik → Asrama</h4>
                    <small class="text-muted">Sinkronkan perubahan status akademik (Mutasi/Lulus/Nonaktif) ke sistem asrama.
                    @if($academicYear) TP: <strong>{{ $academicYear->name }}</strong> @endif
                    </small>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Nama / NIS">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Asrama</label>
                    <select name="dormitory_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($dormitories as $d)
                        <option value="{{ $d->id }}" {{ ($filters['dormitory_id'] ?? '') === $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="active"   {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="graduate" {{ ($filters['status'] ?? '') === 'graduate' ? 'selected' : '' }}>Lulus</option>
                        <option value="transfer" {{ ($filters['status'] ?? '') === 'transfer' ? 'selected' : '' }}>Pindah</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i> Filter</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Santri</th>
                            <th>Asrama</th>
                            <th>Kamar</th>
                            <th>Status</th>
                            <th width="320">Aksi Sinkronisasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $s)
                        <tr>
                            <td><strong>{{ $s->name }}</strong><br><small class="text-muted">{{ $s->nis ?? '-' }}</small></td>
                            <td>{{ $s->dormitory->name ?? '-' }}</td>
                            <td>{{ $s->room->kode ?? $s->room->nomor ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $s->status === 'active' ? 'success' : 'secondary' }}">{{ $s->status_text }}</span>
                            </td>
                            <td>
                                @if($s->status === 'active')
                                <form method="POST" action="{{ route('user.academic.sync', ['userId' => $userId]) }}" class="d-flex gap-1">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $s->id }}">
                                    <select name="new_status" class="form-select form-select-sm" required>
                                        <option value="">Pilih...</option>
                                        <option value="graduate">Lulus</option>
                                        <option value="inactive">Nonaktif</option>
                                        <option value="transfer_out">Pindah Keluar</option>
                                        <option value="dropped">Dropout</option>
                                    </select>
                                    <button class="btn btn-sm btn-warning"><i class="ri-refresh-line"></i></button>
                                </form>
                                @else
                                <em class="text-muted">Sudah nonaktif</em>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $students->links() }}
        </div>
    </div>

    @if($recentChanges->count() > 0)
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-history-line me-1"></i> Perubahan Terbaru (30 hari)</h5></div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                @foreach($recentChanges as $c)
                <li class="list-group-item d-flex justify-content-between">
                    <span>
                        <code>{{ $c->event_type }}</code>
                        · student {{ $c->student_id }}
                    </span>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($c->event_at)->diffForHumans() }}</small>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
@endsection