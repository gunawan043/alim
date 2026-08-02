@extends('layouts.master')
@section('title') Tetapkan Wali Kamar @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Wali Kamar</a> @endslot
        @slot('title') Tetapkan Wali Kamar @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('user.asrama.room-supervisors.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
        @csrf
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ri-user-add-line me-1"></i> Form Penetapan Wali Kamar</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Wali Kamar (Pegawai GTK) <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select js-example-basic-single" required>
                            <option value="">-- Pilih Pegawai --</option>
                            @foreach($gtkUsers as $u)
                                <option value="{{ $u->id }}" {{ old('user_id') === $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} {{ $u->email ? " — {$u->email}" : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Data diambil dari master GTK/Pegawai yang sudah ada.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kamar <span class="text-danger">*</span></label>
                        <select name="room_id" class="form-select" required>
                            <option value="">-- Pilih Kamar --</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ old('room_id') === $r->id ? 'selected' : '' }}>
                                    {{ $r->code }} {{ $r->name ? "— {$r->name}" : '' }} {{ $r->wing ? "[{$r->wing->name}]" : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if($rooms->isEmpty())
                            <small class="text-danger">Belum ada kamar aktif di asrama ini.</small>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-select" required>
                            @foreach($academicYears as $y)
                                <option value="{{ $y->id }}" {{ old('academic_year_id', $academicYears->firstWhere('is_active', true)?->id) === $y->id ? 'selected' : '' }}>
                                    {{ $y->name ?? ($y->start_date?->format('Y').'/'.$y->end_date?->format('Y')) }} {{ $y->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                        <small class="text-muted">Kosongkan jika masih aktif.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">SK Penugasan (Opsional)</label>
                        <input type="text" name="decree_id" class="form-control" placeholder="UUID SK" value="{{ old('decree_id') }}">
                        <small class="text-muted">Nomor SK dari menu Institution Decree (opsional).</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Non-aktif</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan tentang penugasan ini">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-save-line me-1"></i> Simpan Penetapan
                </button>
            </div>
        </div>
    </form>
@endsection

@section('script')
    <style>
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 8px;
        }
        .select2-dropdown { border-radius: 8px; }
    </style>
    <script>
        $(function () {
            $('.js-example-basic-single').each(function () {
                const placeholder = '-- Pilih Pegawai --';
                $(this).select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    language: {
                        inputTooShort: function () { return 'Ketik nama pegawai...'; },
                        searching: function () { return 'Mencari...'; },
                        noResults: function () { return 'Tidak ada hasil.'; },
                        errorLoading: function () { return 'Gagal memuat.'; }
                    }
                });
            });
        });
    </script>
@endsection