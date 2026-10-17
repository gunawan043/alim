@extends('layouts.master')
@section('title') Booking Ruangan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Booking Ruangan @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4">
                    <div class="col-sm"><h5 class="card-title mb-0">Booking Ruangan</h5></div>
                    <div class="col-sm-auto">
                        <a href="{{ route('sarpras.booking.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i> Request Booking</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            @foreach(['pending','approved','rejected','cancelled','completed'] as $s)
                                <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="room_id" class="form-control">
                            <option value="">Semua Ruang</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ request('room_id')==$r->id?'selected':'' }}>{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Ruang</th><th>Event</th><th>Tanggal</th><th>Waktu</th><th>Peserta</th><th>Requester</th><th>Status</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $b)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $b->room?->room_name ?? '-' }}</td>
                                <td>{{ $b->event_name ?? $b->purpose }}</td>
                                <td>{{ $b->booking_date?->format('d/m/Y') }}</td>
                                <td>{{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}</td>
                                <td>{{ $b->participants_count ?? '-' }}</td>
                                <td>{{ $b->user?->name ?? '-' }}</td>
                                <td>
                                    @php $c=['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary','completed'=>'info']; @endphp
                                    <span class="badge bg-{{ $c[$b->status] ?? 'secondary' }}-subtle text-{{ $c[$b->status] ?? 'secondary' }}">
                                        {{ ucfirst($b->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('sarpras.booking.show', ['id' => $b->id]) }}" class="btn btn-sm btn-soft-primary"><i class="ri-eye-line"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center py-4"><p class="text-muted mb-0">Belum ada booking.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('shared._pagination', ['paginator' => $bookings])
            </div>
        </div>
    </div>
</div>
@endsection
