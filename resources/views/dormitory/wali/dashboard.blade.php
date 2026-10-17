@extends('layouts.master')

@section('title', 'Portal Wali Santri')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="mb-sm-0">Portal Wali Santri</h4>
                <small class="text-muted">Pantau aktivitas asrama dan ajukan izin untuk putra/putri Anda.</small>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($students->count() === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="ri-user-search-line" style="font-size:3rem" class="text-muted"></i>
            <p class="mt-3 text-muted">Belum ada data santri yang terhubung dengan akun Anda. Hubungi admin untuk verifikasi.</p>
        </div>
    </div>
    @else
    <div class="row">
        @if($students->count() > 1)
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <label>Pilih Santri:</label>
                        <select name="student_id" class="form-select" onchange="this.form.submit()">
                            @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ $selected?->id === $s->id ? 'selected' : '' }}>
                                {{ $s->name }} — {{ $s->nis ?? '-' }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
        @endif

        @if($selected)
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-2">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-1">
                            {{ strtoupper(substr($selected->name, 0, 1)) }}
                        </span>
                    </div>
                    <h5>{{ $selected->name }}</h5>
                    <p class="text-muted mb-1">{{ $selected->nis ?? '-' }}</p>
                    <p class="text-muted mb-1">{{ $selected->dormitory->name ?? '-' }}</p>
                    <p class="text-muted">Kamar {{ $selected->room->kode ?? $selected->room->nomor ?? '-' }}</p>

                    <a href="{{ route('user.students.health', ['userId' => $userId, 'studentId' => $selected->id]) }}"
                       class="btn btn-sm btn-outline-info mt-2">
                        <i class="ri-heart-pulse-line me-1"></i> Lihat Rekam Kesehatan
                    </a>
                </div>
            </div>

            @if($policy)
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-information-line me-1"></i> Kebijakan Asrama</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $policy->title }}</strong></p>
                    <small class="text-muted">Kuota izin: {{ $policy->permit_quota ?? '∞' }}/bulan</small><br>
                    <small class="text-muted">Kuota kunjungan: {{ $policy->visit_quota ?? '∞' }}/bulan</small>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-add-line me-1"></i> Ajukan Izin Baru</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.wali.request-permit', ['userId' => $userId]) }}">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $selected->id }}">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Berangkat</label>
                                <input type="date" name="departure_date" class="form-control" min="{{ $today->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rencana Kembali</label>
                                <input type="date" name="expected_return_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tujuan</label>
                                <input type="text" name="destination" class="form-control" placeholder="Kota / alamat" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pendamping</label>
                                <input type="text" name="companion" class="form-control" placeholder="Nama wali/keluarga">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alasan</label>
                                <textarea name="reason" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary"><i class="ri-send-plane-line me-1"></i> Kirim Pengajuan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-list-3-line me-1"></i> Riwayat Izin</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr><th>Berangkat</th><th>Tujuan</th><th>Alasan</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $p)
                                <tr>
                                    <td>{{ $p->departure_date->format('d M Y') }}</td>
                                    <td>{{ $p->destination }}</td>
                                    <td><small>{{ Str::limit($p->reason, 60) }}</small></td>
                                    <td>
                                        <span class="badge bg-{{ $p->status === 'approved' ? 'success' : ($p->status === 'pending' ? 'warning' : ($p->status === 'rejected' ? 'danger' : 'secondary')) }}">
                                            {{ ucfirst($p->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-5">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Belum Ada Izin</h6>
                                    <p class="text-muted mb-3 small">Data perizinan anak akan muncul di sini.</p>
                                </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection