@extends('layouts.master')
@section('title') Wizard Izin — Langkah 2 @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permit-wizard.step1', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Izin Kepulangan</a> @endslot
        @slot('title') Detail Izin @endslot
    @endcomponent

    {{-- Wizard Progress --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center position-relative">
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="ri-check-line"></i></div><div class="mt-1 small fw-semibold text-success">1. Pilih Santri</div></div>
                <div class="progress flex-fill position-absolute" style="height:2px;top:18px;left:0;right:0"><div class="progress-bar bg-primary" style="width:50%"></div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px">2</div><div class="mt-1 small fw-semibold text-primary">2. Detail Izin</div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width:36px;height:36px">3</div><div class="mt-1 small text-muted">3. Waktu</div></div>
                <div class="text-center flex-fill" style="z-index:2"><div class="avatar-sm mx-auto rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width:36px;height:36px">4</div><div class="mt-1 small text-muted">4. Konfirmasi</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ri-file-list-line me-2 text-primary"></i>Detail Izin</h5>
            <span class="badge bg-primary-subtle text-primary"><i class="ri-user-line me-1"></i>{{ $student->name ?? '-' }}</span>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('user.asrama.permit-wizard.step3', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                <input type="hidden" name="student_id" value="{{ $student->id ?? '' }}">
                <input type="hidden" name="room_id" value="{{ $resident->room_id ?? '' }}">

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Jenis Izin <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-2">
                            @php
                                $types = [
                                    'pulang' => ['Pulang', 'ri-home-line', 'success'],
                                    'keluar_kota' => ['Keluar Kota', 'ri-roadster-line', 'info'],
                                    'berobat' => ['Berobat', 'ri-hospital-line', 'warning'],
                                    'keperluan_keluarga' => ['Keperluan Keluarga', 'ri-team-line', 'primary'],
                                    'sakit' => ['Sakit', 'ri-medicine-bottle-line', 'danger'],
                                    'lainnya' => ['Lainnya', 'ri-more-line', 'secondary'],
                                ];
                            @endphp
                            @foreach($types as $key => [$label, $icon, $color])
                                <input type="radio" class="btn-check" name="permit_type" id="pt-{{ $key }}" value="{{ $key }}" required>
                                <label class="btn btn-outline-{{ $color }}" for="pt-{{ $key }}">
                                    <i class="{{ $icon }} me-1"></i>{{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Tujuan <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" required placeholder="Alamat / kota / tempat tujuan..." value="{{ old('destination') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Keperluan / Tujuan Izin</label>
                        <textarea name="purpose" class="form-control" rows="2" placeholder="Detail keperluan izin">{{ old('purpose') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Mahrom yang Menemanai (opsional)</label>
                        <select name="mahrom_id" class="form-select">
                            <option value="">— Tidak ada —</option>
                            @if($student && $student->mahroms)
                                @foreach($student->mahroms as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->relationship ?? 'mahrom' }})</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan/keterangan tambahan (opsional)">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('user.asrama.permit-wizard.step1', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Lanjut <i class="ri-arrow-right-line ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
