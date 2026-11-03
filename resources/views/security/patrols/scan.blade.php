@extends('layouts.master')

@section('title', 'Pindai Checkpoint')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2')
            <a href="{{ route('user.keamanan.patrol.index', ['userId' => $userId]) }}">Patroli & Ronda</a>
        @endslot
        @slot('title') Pindai Checkpoint @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center border-bottom">
                    <span class="avatar-lg mx-auto mb-3">
                        <span class="avatar-title rounded-circle bg-success-subtle text-success fs-2">
                            <i class="ri-map-pin-line"></i>
                        </span>
                    </span>
                    <h4 class="mb-1">{{ $checkpoint->name }}</h4>
                    <p class="text-muted mb-1">{{ $checkpoint->code }}{{ $checkpoint->location ? ' · '.$checkpoint->location : '' }}</p>
                    <span class="badge bg-primary-subtle text-primary">{{ $checkpoint->post->name ?? 'Semua pos' }}</span>
                </div>

                <div class="card-body">
                    @if($schedules->isEmpty())
                        <div class="alert alert-warning mb-0">
                            <i class="ri-error-warning-line me-1"></i>
                            Tidak ada jadwal patroli aktif untuk hari ini. Buat jadwal terlebih dahulu.
                            <div class="mt-2">
                                <a href="{{ route('user.keamanan.patrol.index', ['userId' => $userId]) }}" class="btn btn-sm btn-warning">
                                    Buat Jadwal
                                </a>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('user.keamanan.patrol.scan.store', ['userId' => $userId]) }}">
                            @csrf
                            <input type="hidden" name="checkpoint_id" value="{{ $checkpoint->id }}">

                            <div class="mb-3">
                                <label class="form-label">Jadwal Patroli <span class="text-danger">*</span></label>
                                <select name="schedule_id" class="form-select" required>
                                    <option value="">— Pilih jadwal —</option>
                                    @foreach($schedules as $schedule)
                                        @php $already = in_array($schedule->id, $loggedScheduleIds, true); @endphp
                                        <option value="{{ $schedule->id }}" {{ $already ? 'disabled' : '' }}>
                                            {{ $schedule->code }} · {{ $schedule->team->name ?? 'Tanpa regu' }}
                                            {{ $schedule->shift ? ' · '.$schedule->shift->label() : '' }}
                                            {{ $already ? ' (sudah dipindai)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-block">Kondisi <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="condition" id="condAman" value="aman" checked>
                                    <label class="btn btn-outline-success" for="condAman"><i class="ri-shield-check-line me-1"></i> Aman</label>

                                    <input type="radio" class="btn-check" name="condition" id="condTemuan" value="temuan">
                                    <label class="btn btn-outline-warning" for="condTemuan"><i class="ri-error-warning-line me-1"></i> Ada Temuan</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Kondisi area, kejadian, atau temuan..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-check-double-line me-1"></i> Simpan Pemindaian
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
