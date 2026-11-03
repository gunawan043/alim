@extends('layouts.master')

@section('title', 'Konsumsi Asrama')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') Operasional @endslot
        @slot('title') Konsumsi Asrama @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Porsi Santri', 'value' => number_format($summary['students']), 'icon' => 'ri-restaurant-line', 'color' => 'primary'],
            ['label' => 'Porsi Tamu', 'value' => number_format($summary['guests']), 'icon' => 'ri-user-shared-line', 'color' => 'info'],
            ['label' => 'Total Porsi', 'value' => number_format($summary['portions']), 'icon' => 'ri-bowl-line', 'color' => 'success'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Konsumsi Harian</h5>
                            <p class="text-muted mb-0 small">Pencatatan porsi makan per asrama, tanggal, dan waktu makan.</p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" class="d-flex gap-2 align-items-center"
                                  action="{{ route('user.meals.index', ['userId' => $userId]) }}">
                                <select name="dormitory_id" class="form-select" style="width: 200px;">
                                    @foreach($dormitories as $dormitory)
                                        <option value="{{ $dormitory->id }}" @selected($dormitoryId === $dormitory->id)>{{ $dormitory->name }}</option>
                                    @endforeach
                                </select>
                                <input type="date" name="date" class="form-control" value="{{ $date }}">
                                <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        @foreach($mealTypes as $type => $label)
                            @php $record = $records->get($type); @endphp
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="text-muted small text-uppercase">{{ $label }}</div>
                                            <div class="fs-3 fw-semibold">{{ number_format($record?->totalPortions() ?? 0) }}</div>
                                            <div class="text-muted small">porsi</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-soft-primary btn-meal"
                                                data-bs-toggle="modal" data-bs-target="#mealModal"
                                                data-type="{{ $type }}"
                                                data-label="{{ $label }}"
                                                data-students="{{ $record?->students_count ?? 0 }}"
                                                data-guests="{{ $record?->guests_count ?? 0 }}"
                                                data-notes="{{ $record?->notes }}">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Santri: {{ number_format($record?->students_count ?? 0) }}</span>
                                        <span>Tamu: {{ number_format($record?->guests_count ?? 0) }}</span>
                                    </div>
                                    @if($record?->notes)
                                        <div class="small text-muted mt-2">{{ $record->notes }}</div>
                                    @endif
                                    @if($record)
                                        <form method="POST" class="mt-2"
                                              action="{{ route('user.meals.destroy', ['userId' => $userId, 'mealId' => $record->id]) }}"
                                              data-confirm="Hapus catatan konsumsi ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">Hapus catatan</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">Riwayat Sebelumnya</h5>
                </div>
                <div class="card-body p-0">
                    @if($history->isEmpty())
                        <x-empty-state icon="ri-history-line" title="Belum ada riwayat"
                                       message="Catatan konsumsi akan muncul di sini setelah beberapa hari." />
                    @else
                        <div class="table-container table-freeze">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 130px;">Tanggal</th>
                                        <th>Asrama</th>
                                        <th>Waktu Makan</th>
                                        <th class="text-end">Santri</th>
                                        <th class="text-end">Tamu</th>
                                        <th class="text-end">Total Porsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($history as $row)
                                        <tr>
                                            <td>{{ $row->consumption_date->format('d/m/Y') }}</td>
                                            <td>{{ $row->dormitory?->name ?? '-' }}</td>
                                            <td>{{ $row->mealLabel() }}</td>
                                            <td class="text-end">{{ number_format($row->students_count) }}</td>
                                            <td class="text-end">{{ number_format($row->guests_count) }}</td>
                                            <td class="text-end fw-semibold">{{ number_format($row->totalPortions()) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mealModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.meals.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="dormitory_id" value="{{ $dormitoryId }}">
                    <input type="hidden" name="consumption_date" value="{{ $date }}">
                    <input type="hidden" name="meal_type" id="mealType">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-restaurant-line me-1"></i> Catat <span id="mealLabel">Konsumsi</span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Porsi Santri <span class="text-danger">*</span></label>
                                <input type="number" name="students_count" id="mealStudents" class="form-control" min="0" max="5000" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Porsi Tamu</label>
                                <input type="number" name="guests_count" id="mealGuests" class="form-control" min="0" max="5000" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" id="mealNotes" class="form-control" maxlength="255">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-meal').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('mealType').value = this.dataset.type;
            document.getElementById('mealLabel').textContent = this.dataset.label;
            document.getElementById('mealStudents').value = this.dataset.students || 0;
            document.getElementById('mealGuests').value = this.dataset.guests || 0;
            document.getElementById('mealNotes').value = this.dataset.notes || '';
        });
    });
});
</script>
@endpush
