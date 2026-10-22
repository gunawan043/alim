@extends('layouts.master')

@section('title', 'Rujukan Pasien UKS')

@section('css')
    @include('uks._ui-styles')
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? auth()->id();
        $hasFilter = request()->filled('status') || request()->filled('search');
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') UKS @endslot
        @slot('title') Rujukan Pasien @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach([
            ['label' => 'Total Rujukan', 'value' => $stats['total'], 'icon' => 'ri-hospital-line', 'color' => 'primary'],
            ['label' => 'Masih Dirujuk', 'value' => $stats['referred'], 'icon' => 'ri-ambulance-line', 'color' => 'danger'],
            ['label' => 'Sudah Kembali', 'value' => $stats['returned'], 'icon' => 'ri-home-heart-line', 'color' => 'success'],
            ['label' => 'Bulan Ini', 'value' => $stats['this_month'], 'icon' => 'ri-calendar-line', 'color' => 'info'],
        ] as $card)
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-sm rounded bg-{{ $card['color'] }}-subtle d-flex align-items-center justify-content-center">
                                <i class="{{ $card['icon'] }} text-{{ $card['color'] }} fs-5"></i>
                            </span>
                            <div>
                                <p class="text-muted text-uppercase fs-11 mb-1">{{ $card['label'] }}</p>
                                <h4 class="mb-0 fw-bold">{{ $card['value'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">Daftar Rujukan Pasien</h5>
                    <p class="text-muted mb-0 fs-12">Rujukan yang dibuat dari lembar pemeriksaan UKS.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <form method="GET" action="{{ route('user.uks.referrals.index', ['userId' => $userId]) }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari pasien / faskes / no. rujukan" style="min-width: 230px;">
                            <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                        </form>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#referralFilterModal">
                            <i class="bx bx-filter-alt align-bottom me-1"></i>Filter
                        </button>
                        <a href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}" class="btn btn-soft-primary">
                            <i class="ri-hospital-fill me-1"></i>Master Faskes
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($hasFilter)
            <div class="card-header py-2 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold"><i class="ri-filter-3-line me-1"></i>Filter Aktif:</span>
                    @if(request('status'))
                        <span class="filter-badge active">Status: {{ \App\Models\Uks\UksReferral::STATUSES[request('status')] ?? request('status') }}</span>
                    @endif
                    @if(request('search'))
                        <span class="filter-badge active">Cari: {{ request('search') }}</span>
                    @endif
                    <a href="{{ route('user.uks.referrals.index', ['userId' => $userId]) }}" class="btn btn-sm btn-link text-danger ms-auto">
                        <i class="ri-close-circle-line me-1"></i>Hapus Semua
                    </a>
                </div>
            </div>
        @endif

        <div class="card-header py-2 uks-quick-filter">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted me-1"><i class="ri-flashlight-line me-1"></i>Quick Filter:</span>
                <a class="filter-badge {{ ! request('status') ? 'active' : '' }}" href="{{ route('user.uks.referrals.index', ['userId' => $userId]) }}">Semua</a>
                @foreach(\App\Models\Uks\UksReferral::STATUSES as $key => $label)
                    <a class="filter-badge {{ request('status') === $key ? 'active' : '' }}"
                       href="{{ route('user.uks.referrals.index', ['userId' => $userId, 'status' => $key]) }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze" id="referralsTable">
                    <thead class="table-light">
                        <tr>
                            <th data-column="pasien">Pasien</th>
                            <th data-column="rujukan">No. Rujukan</th>
                            <th data-column="fasilitas">Fasilitas</th>
                            <th data-column="urgensi">Urgensi</th>
                            <th data-column="tanggal">Tanggal</th>
                            <th data-column="status">Status</th>
                            <th data-column="action" class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrals as $referral)
                            @php
                                $urgencyColor = $referral->urgency === 'darurat' ? 'danger' : ($referral->urgency === 'segera' ? 'warning' : 'secondary');
                                $statusColor = $referral->status === 'kembali' ? 'success' : ($referral->status === 'batal' ? 'secondary' : 'danger');
                            @endphp
                            <tr>
                                <td data-column="pasien">
                                    <div class="fw-semibold">{{ $referral->patient?->student?->name ?? '-' }}</div>
                                    <div class="text-muted fs-11">{{ $referral->patient?->medical_record_no ?? '-' }}</div>
                                </td>
                                <td data-column="rujukan">
                                    <span class="fw-semibold">{{ $referral->referral_no ?? '-' }}</span>
                                    <div class="text-muted fs-11">{{ $referral->referredBy?->name ?? '-' }}</div>
                                </td>
                                <td data-column="fasilitas">
                                    <div>{{ $referral->facility_name }}</div>
                                    <div class="text-muted fs-11">{{ ucfirst(str_replace('_', ' ', $referral->facility_type ?? '-')) }}</div>
                                </td>
                                <td data-column="urgensi"><span class="badge bg-{{ $urgencyColor }}-subtle text-{{ $urgencyColor }}">{{ $referral->urgency_label }}</span></td>
                                <td data-column="tanggal" class="fs-13">{{ $referral->referred_at?->format('d M Y H:i') }}</td>
                                <td data-column="status"><span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $referral->status_label }}</span></td>
                                <td data-column="action" class="text-end">
                                    <div class="dropdown uks-table-actions">
                                        <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.referrals.print', ['userId' => $userId, 'uuid' => $referral->id]) }}" target="_blank">
                                                    <i class="ri-printer-line text-secondary me-2"></i>Cetak Surat
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $referral->patient_id]) }}">
                                                    <i class="ri-file-user-line text-primary me-2"></i>Lembar Pemeriksaan
                                                </a>
                                            </li>
                                            @if($referral->status !== \App\Models\Uks\UksReferral::STATUS_RETURNED)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item btn-return-referral"
                                                            data-bs-toggle="modal" data-bs-target="#returnReferralModal"
                                                            data-referral-id="{{ $referral->id }}"
                                                            data-referral-no="{{ $referral->referral_no }}"
                                                            data-patient-name="{{ $referral->patient?->student?->name ?? '-' }}"
                                                            data-action="{{ route('user.uks.referrals.return', ['userId' => $userId, 'uuid' => $referral->id]) }}">
                                                        <i class="ri-home-heart-line text-success me-2"></i>Tandai Kembali
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="border-0 p-0">
                                    <div class="uks-empty">
                                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                        <h5 class="mt-2">Belum ada rujukan pasien</h5>
                                        <p class="text-muted mb-0">Rujukan dibuat dari lembar pemeriksaan pasien.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($referrals->hasPages())
            <div class="card-footer">
                @include('shared._pagination', ['paginator' => $referrals->withQueryString()])
            </div>
        @endif
    </div>

    <x-uks.filter-modal id="referralFilterModal" title="Filter Rujukan Pasien" :action="route('user.uks.referrals.index', ['userId' => $userId])">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-file-shield-2-line"></i>Status Rujukan</div>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\Uks\UksReferral::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="filter-group">
                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Pasien / faskes / no. rujukan">
                </div>
            </div>
        </div>
    </x-uks.filter-modal>

    {{-- RETURN MODAL --}}
    <div class="modal fade" id="returnReferralModal" tabindex="-1" aria-labelledby="returnReferralModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="returnReferralForm" action="#">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="returnReferralModalLabel"><i class="ri-home-heart-line me-2 text-success"></i>Tandai Kembali dari Rujukan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Rujukan <strong id="returnReferralNo">-</strong> untuk <strong id="returnPatientName">-</strong>.</p>
                        <div class="mb-2">
                            <label class="form-label">Status Kembali</label>
                            <select name="return_status" class="form-select" required>
                                <option value="kembali_ke_asrama">Kembali ke Asrama</option>
                                <option value="kembali_ke_sekolah">Kembali ke Sekolah</option>
                                <option value="dijemput_wali">Dijemput Wali</option>
                                <option value="pulang">Pulang</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Hasil dari Faskes</label>
                            <textarea name="outcome" rows="2" class="form-control" placeholder="cth: Sudah diperiksa, kembali ke asrama"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="ri-check-line me-1"></i>Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-return-referral').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('returnReferralNo').textContent = this.dataset.referralNo || '-';
            document.getElementById('returnPatientName').textContent = this.dataset.patientName || '-';
            document.getElementById('returnReferralForm').action = this.dataset.action;
        });
    });
});
</script>
@endpush
