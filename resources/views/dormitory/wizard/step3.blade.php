@extends('layouts.master')
@section('title') Wizard Izin — Langkah 3 @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permit-wizard.step1', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Izin Kepulangan</a> @endslot
        @slot('title') Waktu & Penjemput @endslot
    @endcomponent

    {{-- Wizard Progress --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center position-relative">
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="ri-check-line"></i></div><div class="mt-1 small fw-semibold text-success">1. Santri</div></div>
                <div class="progress flex-fill position-absolute" style="height:2px;top:18px;left:0;right:0"><div class="progress-bar bg-primary" style="width:75%"></div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="ri-check-line"></i></div><div class="mt-1 small fw-semibold text-success">2. Detail</div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px">3</div><div class="mt-1 small fw-semibold text-primary">3. Waktu</div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width:36px;height:36px">4</div><div class="mt-1 small text-muted">4. Konfirmasi</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ri-time-line me-2 text-primary"></i>Waktu Berangkat & Penjemput</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('user.asrama.permit-wizard.confirm', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                <input type="hidden" name="student_id" value="{{ $student->id ?? '' }}">
                @foreach(request()->except(['_token', 'student_id']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Waktu Berangkat <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="departure_datetime" class="form-control" required value="{{ old('departure_datetime', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Taksiran Kembali <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="expected_return_datetime" class="form-control" required value="{{ old('expected_return_datetime', now()->addDay()->format('Y-m-d\TH:i')) }}">
                    </div>

                    <div class="col-12 mt-4"><h6 class="text-muted mb-0">Data Penjemput (Opsional)</h6><hr class="my-2"></div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Penjemput</label>
                        <input type="text" name="companion_name" class="form-control" placeholder="Nama wali / penjemput" value="{{ old('companion_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hubungan</label>
                        <input type="text" name="companion_relation" class="form-control" placeholder="Ayah / Ibu / Paman / dll" value="{{ old('companion_relation') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. HP Penjemput</label>
                        <input type="text" name="companion_phone" class="form-control" placeholder="08xxx" value="{{ old('companion_phone') }}">
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('user.asrama.permit-wizard.step2', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Lanjut ke Konfirmasi <i class="ri-arrow-right-line ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
