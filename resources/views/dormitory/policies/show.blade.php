@extends('layouts.master')

@section('title', $policy->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Detail: {{ $policy->name }}</h4>
                <a href="{{ route('user.boarding-policies.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Informasi Dasar</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4 text-muted">Kode</div>
                        <div class="col-sm-8"><code>{{ $policy->code }}</code></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4 text-muted">Nama</div>
                        <div class="col-sm-8">{{ $policy->name }}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4 text-muted">Deskripsi</div>
                        <div class="col-sm-8">{{ $policy->description ?: '-' }}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4 text-muted">Status</div>
                        <div class="col-sm-8">
                            @if($policy->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Non-aktif</span>@endif
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4 text-muted">Dibuat</div>
                        <div class="col-sm-8">{{ $policy->created_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Kebijakan Izin</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4 text-muted">Strategi</div>
                        <div class="col-sm-8">
                            @if($policy->leave_strategy === 'quota')<span class="badge bg-warning">Kuota: {{ $policy->leave_quota }}/{{ $policy->leave_quota_period }}</span>
                            @elseif($policy->leave_strategy === 'unrestricted')<span class="badge bg-success">Tanpa Batasan</span>
                            @else<span class="badge bg-danger">Dilarang</span>@endif
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4 text-muted">Curfew</div>
                        <div class="col-sm-8">{{ $policy->curfew_hour !== null ? $policy->curfew_hour . ':00' : '-' }}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4 text-muted">Izin Khusus</div>
                        <div class="col-sm-8">
                            @if($policy->special_permission_allowed)
                                {{ collect(json_decode($policy->special_permission_types, true))->map(fn($v) => ['medical' => 'Medis','emergency' => 'Darurat','family' => 'Keluarga','competition' => 'Lomba','school_activity' => 'Kegiatan Sekolah'][$v])->filter()->join(', ') }}
                            @else Tidak diizinkan @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Kebijakan Kunjungan</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4 text-muted">Strategi</div>
                        <div class="col-sm-8">
                            @if($policy->visit_strategy === 'quota')<span class="badge bg-warning">Kuota: {{ $policy->visit_quota }}/{{ $policy->visit_quota_period }}</span>
                            @elseif($policy->visit_strategy === 'unrestricted')<span class="badge bg-success">Bebas</span>
                            @else<span class="badge bg-danger">Dilarang</span>@endif
                        </div>
                    </div>
                    @if($policy->visit_strategy === 'quota' || $policy->max_visitors_per_visit)
                    <hr>
                    <div class="row">
                        <div class="col-sm-4 text-muted">Maks Pengunjung</div>
                        <div class="col-sm-8">{{ $policy->max_visitors_per_visit ?? '-' }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Log Kebijakan (10 Terakhir)</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr><th>Perubahan</th><th>Type</th><th>Asrama/Sekolah</th><th>Tanggal</th></tr>
                        </thead>
                        <tbody>
                            @forelse($policy->assignments as $assign)
                            <tr>
                                <td>{{ $assign->notes }}</td>
                                <td><code>{{ $assign->policy_assignment_type }}</code></td>
                                <td>
                                    @php $target = $assign->dormitory ?? $assign->sekolah; @endphp
                                    {{ $target->name ?? $assign->target_id }}
                                </td>
                                <td>{{ $assign->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center">Belum ada log</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Diterapkan di</h5></div>
                <div class="card-body">
                    @php $dorms = $policy->assignments->where('policy_assignment_type', 'dormitory'); @endphp
                    @if($dorms->count() > 0)
                        @foreach($dorms as $a)
                            <span class="badge bg-info me-1 mb-1">{{ $a->dormitory->name ?? '?' }}</span>
                        @endforeach
                    @else
                        <p class="text-muted">Belum diterapkan ke asrama mana pun.</p>
                    @endif
                </div>
            </div>

            <div class="d-grid gap-2">
                <a href="{{ route('user.boarding-policies.edit', $policy->id) }}" class="btn btn-primary">
                    <i class="ri-edit-line me-1"></i> Edit Kebijakan
                </a>
            </div>
        </div>
    </div>
</div>
@endsection