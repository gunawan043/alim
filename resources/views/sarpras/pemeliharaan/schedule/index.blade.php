@extends('layouts.master')
@section('title') Jadwal Pemeliharaan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Jadwal Pemeliharaan @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4">
                    <div class="col-sm"><h5 class="card-title mb-0">Jadwal Pemeliharaan</h5></div>
                    <div class="col-sm-auto">
                        <a href="{{ route('sarpras.pemeliharaan.schedule.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i> Tambah Jadwal</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">Semua</option>
                            <option value="overdue" {{ request('status')=='overdue'?'selected':'' }}>Overdue</option>
                            <option value="upcoming" {{ request('status')=='upcoming'?'selected':'' }}>Upcoming</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Jenis</th><th>Target</th><th>Frekuensi</th><th>Jadwal Berikutnya</th><th>Penanggung Jawab</th><th>Biaya Estimasi</th><th>Status</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schedules as $s)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $s->maintenance_type }}</td>
                                <td>
                                    @if($s->asset)
                                        <span class="badge bg-primary-subtle text-primary">Aset: {{ $s->asset->asset_name }}</span>
                                    @elseif($s->room)
                                        <span class="badge bg-info-subtle text-info">Ruang: {{ $s->room->room_name }}</span>
                                    @elseif($s->building)
                                        <span class="badge bg-secondary-subtle text-secondary">Gedung: {{ $s->building->building_name }}</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst(str_replace('_',' ',$s->frequency)) }}</td>
                                <td>{{ $s->next_maintenance_date->format('d/m/Y') }}</td>
                                <td>{{ $s->responsibleUser?->name ?? '-' }}</td>
                                <td>{{ $s->estimated_cost ? 'Rp '.number_format($s->estimated_cost,0,',','.') : '-' }}</td>
                                <td>
                                    @if($s->next_maintenance_date->isPast())
                                        <span class="badge bg-danger">Overdue</span>
                                    @elseif($s->next_maintenance_date->diffInDays(now()) <= 7)
                                        <span class="badge bg-warning">Soon</span>
                                    @else
                                        <span class="badge bg-success">OK</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('sarpras.pemeliharaan.schedule.show', ['id' => $s->id]) }}" class="btn btn-sm btn-soft-primary"><i class="ri-eye-line"></i></a>
                                    <a href="{{ route('sarpras.pemeliharaan.schedule.edit', ['id' => $s->id]) }}" class="btn btn-sm btn-soft-warning"><i class="ri-pencil-line"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center py-4"><p class="text-muted mb-0">Belum ada jadwal pemeliharaan.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $schedules])
            </div>
        </div>
    </div>
</div>
@endsection
