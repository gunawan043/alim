@extends('layouts.master')

@section('title', 'Notifikasi Wali Santri')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Komunikasi @endslot
        @slot('title') Notifikasi Wali Santri @endslot
    @endcomponent

    @component('components.hrd-page-header', [
        'stats' => [
            ['label' => 'Pasien Hari Ini', 'value' => number_format($patients->count()), 'icon' => 'ri-hospital-line', 'color' => 'primary'],
            ['label' => 'Masih Dirawat', 'value' => number_format($patients->whereIn('status', ['sedang_ditangani', 'observasi', 'rawat_uks', 'istirahat_di_uks'])->count()), 'icon' => 'ri-heart-pulse-line', 'color' => 'danger'],
            ['label' => 'Punya Kontak Wali', 'value' => number_format($patients->filter(fn ($p) => filled($p->student?->mobile_phone) || filled($p->student?->phone))->count()), 'icon' => 'ri-contacts-line', 'color' => 'success'],
        ],
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Pasien UKS</h5>
                            <p class="text-muted mb-0 small">Pilih pasien lalu kirim pemberitahuan ke wali santri.</p>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" class="d-flex gap-2"
                                  action="{{ route('user.uks.guardian-notifications.index', ['userId' => $userId]) }}">
                                <input type="text" name="search" class="form-control" value="{{ $search }}"
                                       placeholder="Cari nama santri..." style="width: 220px;">
                                <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('user.uks.guardian-notifications.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="card-body p-0">
                        @if($patients->isEmpty())
                            <x-empty-state icon="ri-hospital-line" title="Tidak ada pasien"
                                           message="Belum ada kunjungan UKS pada periode ini." />
                        @else
                            <div class="table-container table-freeze">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 45px;" class="text-center">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                            </th>
                                            <th>Santri</th>
                                            <th>Keluhan</th>
                                            <th>Status</th>
                                            <th>Wali</th>
                                            <th>Kontak</th>
                                            <th style="width: 120px;">Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($patients as $patient)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input patient-check"
                                                           name="patient_ids[]" value="{{ $patient->id }}">
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $patient->student?->name ?? '-' }}</div>
                                                    <div class="text-muted small">{{ $patient->student?->nis ?: $patient->student?->nisn ?: '-' }}</div>
                                                </td>
                                                <td>{{ \Illuminate\Support\Str::limit($patient->chief_complaint ?: '-', 60) }}</td>
                                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $patient->status }}</span></td>
                                                <td>{{ $patient->student?->guardian_name ?: '-' }}</td>
                                                <td class="small">
                                                    {{ $patient->student?->mobile_phone ?: ($patient->student?->phone ?: '-') }}
                                                </td>
                                                <td class="small">{{ $patient->queue_date ? \Illuminate\Support\Carbon::parse($patient->queue_date)->format('d/m/Y') : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    @if($patients->isNotEmpty())
                        <div class="card-body border-top">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Judul Notifikasi <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" required maxlength="120"
                                           value="{{ old('title', 'Pemberitahuan UKS') }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Pesan <span class="text-danger">*</span></label>
                                    <input type="text" name="message" class="form-control" required maxlength="500"
                                           value="{{ old('message') }}"
                                           placeholder="Mis. Ananda sedang dirawat di UKS dan telah ditangani petugas.">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer border-top-dashed d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-send-plane-2-line me-1"></i> Kirim Notifikasi
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var all = document.getElementById('checkAll');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.patient-check').forEach(function (cb) {
                cb.checked = all.checked;
            });
        });
    }
});
</script>
@endpush
