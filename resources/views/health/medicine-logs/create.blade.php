@extends('layouts.master')
@section('title') Catat Pemberian Obat @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') <a href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}">Pemberian Obat</a> @endslot
        @slot('title') Catat Pemberian Obat @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('user.uks.medicine-logs.store', ['userId' => $userId]) }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light"><h5 class="mb-0">Form Pemberian Obat</h5></div>
                    <div class="card-body">
                        @component('components.student-select', [
                            'label' => 'Nama Santri',
                            'inputId' => 'studentFilter',
                            'selectId' => 'studentSelect',
                            'selectName' => 'student_id',
                            'groupedStudents' => $groupedStudents,
                            'errorName' => 'student_id',
                        ])@endcomponent

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Obat <span class="text-danger">*</span></label>
                                    <select name="inventory_id" class="form-control @error('inventory_id') is-invalid @enderror" required>
                                        <option value="">-- Pilih Obat --</option>
                                        @foreach($inventories as $inv)
                                            <option value="{{ $inv->id }}" {{ old('inventory_id') == $inv->id ? 'selected' : '' }}>
                                                {{ $inv->medicine_name }} — Stok: {{ $inv->current_stock }} {{ $inv->unit }}
                                                @if($inv->is_low_stock) [STOK RENDAH] @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('inventory_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Diberikan <span class="text-danger">*</span></label>
                                    <input type="number" step="0.1" name="quantity_given" class="form-control @error('quantity_given') is-invalid @enderror" value="{{ old('quantity_given') }}" required>
                                    @error('quantity_given')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Waktu</label>
                                    <input type="time" name="time_given" class="form-control" value="{{ old('time_given', now()->format('H:i')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pemberian <span class="text-danger">*</span></label>
                                    <input type="date" name="log_date" class="form-control @error('log_date') is-invalid @enderror" value="{{ old('log_date', now()->format('Y-m-d')) }}" required>
                                    @error('log_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dosis / Aturan Pakai</label>
                                    <input type="text" name="dosage" class="form-control" value="{{ old('dosage') }}" placeholder="3x1, setelah makan">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tujuan / Indikasi</label>
                            <textarea name="purpose" class="form-control" rows="2" placeholder="Demam, batuk, dll">{{ old('purpose') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Follow-up</label>
                            <input type="date" name="follow_up_date" class="form-control" value="{{ old('follow_up_date') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> Simpan</button>
                            <a href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection