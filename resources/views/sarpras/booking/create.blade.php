@extends('layouts.master')
@section('title') Request Booking Ruangan @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('li_2') <a href="{{ route('sarpras.booking.index') }}">Booking</a> @endslot
    @slot('title') Request @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Request Booking Ruangan</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sarpras.booking.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Ruangan <span class="text-danger">*</span></label>
                            <select name="room_id" class="form-select" required>
                                <option value="">Pilih Ruangan</option>
                                @foreach($rooms as $r)
                                    <option value="{{ $r->id }}">{{ $r->room_name }} ({{ $r->room_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Event / Kegiatan</label>
                            <input type="text" name="event_name" class="form-control" value="{{ old('event_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Peserta</label>
                            <input type="number" name="participants_count" class="form-control" min="1" value="{{ old('participants_count') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tujuan <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="2" required>{{ old('purpose') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Booking <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" class="form-control" value="{{ old('booking_date') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="hstack gap-2 mt-4">
                        <button type="submit" class="btn btn-success"><i class="ri-send-plane-line me-1"></i> Ajukan</button>
                        <a href="{{ route('sarpras.booking.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
