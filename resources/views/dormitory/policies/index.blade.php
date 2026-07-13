@extends('layouts.master')

@section('title', 'Kebijakan Asrama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Kebijakan Asrama</h4>
                <div>
                    <a href="{{ route('user.boarding-policies.create') }}" class="btn btn-primary">
                        <i class="ri-add-line me-1"></i> Buat Kebijakan Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-2 col-6">
            <div class="card stats-card">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Kebijakan</p>
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card stats-card">
                <div class="card-body">
                    <p class="text-muted mb-1">Aktif</p>
                    <h3 class="mb-0 text-success">{{ $stats['active'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card stats-card">
                <div class="card-body">
                    <p class="text-muted mb-1">Dengan Kuota</p>
                    <h3 class="mb-0 text-warning">{{ $stats['quota'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stats-card">
                <div class="card-body">
                    <p class="text-muted mb-1">Tanpa Batasan</p>
                    <h3 class="mb-0 text-info">{{ $stats['unrestricted'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stats-card">
                <div class="card-body">
                    <p class="text-muted mb-1">Dilarang</p>
                    <h3 class="mb-0 text-danger">{{ $stats['banned'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Kebijakan</th>
                                    <th>Strategi Izin</th>
                                    <th>Strategi Kunjungan</th>
                                    <th>Diterapkan di</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($policies as $policy)
                                <tr>
                                    <td><code>{{ $policy->code }}</code></td>
                                    <td>
                                        <strong>{{ $policy->name }}</strong>
                                        @if($policy->description)
                                            <br><small class="text-muted">{{ Str::limit($policy->description, 60) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->leave_strategy === 'quota')
                                            <span class="badge bg-warning-subtle text-warning">
                                                Kuota: {{ $policy->leave_quota }}/{{ $policy->leave_quota_period }}
                                            </span>
                                        @elseif($policy->leave_strategy === 'unrestricted')
                                            <span class="badge bg-success-subtle text-success">Tanpa Batasan</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Dilarang</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->visit_strategy === 'quota')
                                            <span class="badge bg-warning-subtle text-warning">
                                                Kuota: {{ $policy->visit_quota }}/{{ $policy->visit_quota_period }}
                                            </span>
                                        @elseif($policy->visit_strategy === 'unrestricted')
                                            <span class="badge bg-success-subtle text-success">Bebas</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Dilarang</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $dorms = $policy->assignments->where('policy_assignment_type', 'dormitory')->pluck('dormitory.name')->filter();
                                        @endphp
                                        @if($dorms->count() > 0)
                                            @foreach($dorms->take(2) as $dn)
                                                <span class="badge bg-info-subtle text-info">{{ $dn }}</span>
                                            @endforeach
                                            @if($dorms->count() > 2)
                                                <span class="badge bg-secondary">+{{ $dorms->count() - 2 }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">Belum diterapkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Non-aktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('user.boarding-policies.show', $policy->id) }}" class="btn btn-info">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('user.boarding-policies.edit', $policy->id) }}" class="btn btn-warning">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('user.boarding-policies.destroy', $policy->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kebijakan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Belum ada kebijakan asrama. <a href="{{ route('user.boarding-policies.create') }}">Buat yang pertama</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $policies->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection