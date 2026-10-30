@extends('layouts.master')
@section('title') Tinjau Pengajuan Penghargaan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2')
            <a href="{{ route('user.asrama.rewards.approvals.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">
                Persetujuan Penghargaan
            </a>
        @endslot
        @slot('title') Tinjau @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $snapshot = $rewardRequest->source_snapshot ?? [];
        $certificatePath = $rewardRequest->achievement?->certificate_path ?? ($snapshot['certificate_path'] ?? null);
        $isPending = $rewardRequest->status === \App\Models\DormitoryRewardRequest::STATUS_PENDING;
        $levelText = $rewardRequest->achievement?->level_label
            ?? [
                'internal' => 'Internal',
                'kecamatan' => 'Kecamatan',
                'kabupaten_kota' => 'Kabupaten/Kota',
                'provinsi' => 'Provinsi',
                'nasional' => 'Nasional',
                'internasional' => 'Internasional',
            ][$snapshot['level'] ?? ''] ?? '-';
        $positionText = $rewardRequest->achievement?->position_label
            ?? [
                'juara_1' => 'Juara 1',
                'juara_2' => 'Juara 2',
                'juara_3' => 'Juara 3',
                'harapan_1' => 'Harapan 1',
                'harapan_2' => 'Harapan 2',
                'harapan_3' => 'Harapan 3',
                'peserta' => 'Peserta',
                'mumtaz_murtafi' => 'Mumtaz Murtafi',
                'lainnya' => $snapshot['position_detail'] ?? 'Lainnya',
            ][$snapshot['position'] ?? ''] ?? '-';
    @endphp

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ri-trophy-line me-1"></i> Detail Prestasi</h5>
                    <span class="badge bg-{{ $rewardRequest->status_badge }}-subtle text-{{ $rewardRequest->status_badge }}">
                        {{ $rewardRequest->status_label }}
                    </span>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width:160px;">Santri</td>
                            <td>
                                <div class="fw-semibold">{{ $rewardRequest->student?->name ?? '-' }}</div>
                                <small class="text-muted">{{ $rewardRequest->student?->nisn ?? '' }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama Prestasi</td>
                            <td class="fw-semibold">{{ $rewardRequest->title }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tingkat</td>
                            <td>{{ $levelText }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Peringkat</td>
                            <td>{{ $positionText }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Penyelenggara</td>
                            <td>{{ $snapshot['organizer'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Prestasi</td>
                            <td>{{ !empty($snapshot['event_date']) ? \Illuminate\Support\Carbon::parse($snapshot['event_date'])->format('d/m/Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Lokasi</td>
                            <td>{{ $snapshot['event_location'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Diajukan</td>
                            <td>
                                {{ $rewardRequest->created_at?->format('d/m/Y H:i') }}
                                oleh {{ $rewardRequest->requestedBy?->name ?? '-' }}
                            </td>
                        </tr>
                        @if($rewardRequest->description)
                            <tr>
                                <td class="text-muted">Keterangan</td>
                                <td>{{ $rewardRequest->description }}</td>
                            </tr>
                        @endif
                        @if($certificatePath)
                            <tr>
                                <td class="text-muted">Bukti Prestasi</td>
                                <td>
                                    <a href="{{ asset('storage/'.$certificatePath) }}" target="_blank"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ri-file-download-line me-1"></i> Lihat Piagam/Sertifikat
                                    </a>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if($isPending)
                <div class="card border-primary">
                    <div class="card-header bg-primary-subtle">
                        <h5 class="mb-0 text-primary"><i class="ri-check-double-line me-1"></i> Keputusan Asrama</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST"
                              action="{{ route('user.asrama.rewards.approvals.approve', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'requestUuid' => $rewardRequest->id]) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Katalog Penghargaan <span class="text-danger">*</span></label>
                                <select name="point_catalog_id" id="approvalCatalog" class="form-select" required>
                                    <option value="">-- Pilih katalog --</option>
                                    @foreach($catalogs as $catalog)
                                        <option value="{{ $catalog->id }}"
                                                data-category="{{ $catalog->rewardCategoryValue() }}"
                                                data-level="{{ $catalog->rewardLevelValue() }}"
                                                data-points="{{ $catalog->points_min }}"
                                                @selected(old('point_catalog_id', $rewardRequest->point_catalog_id) === $catalog->id)>
                                            {{ $catalog->code }} — {{ $catalog->name }} ({{ $catalog->points_min }} poin)
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Sistem mengusulkan katalog dari tingkat dan peringkat. Anda dapat mengubahnya.</div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small text-muted">Kategori</label>
                                    <input type="text" id="approvalCategory" class="form-control form-control-sm" readonly
                                           value="{{ $rewardRequest->pointCatalog?->rewardCategoryValue() ?? $rewardRequest->category }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">Level</label>
                                    <input type="text" id="approvalLevel" class="form-control form-control-sm" readonly
                                           value="{{ $rewardRequest->pointCatalog?->rewardLevelValue() ?? $rewardRequest->level }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Poin</label>
                                <div class="input-group">
                                    <input type="text" id="approvalPoints" class="form-control" readonly
                                           value="{{ $rewardRequest->points }}">
                                    <span class="input-group-text">poin</span>
                                </div>
                                <div class="form-text">Poin mengikuti katalog yang dipilih.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tanggal Pemberian</label>
                                <input type="date" name="awarded_date" class="form-control"
                                       value="{{ old('awarded_date', now()->toDateString()) }}">
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="send_notification" value="0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="send_notification" name="send_notification" value="1" checked>
                                <label class="form-check-label" for="send_notification">
                                    Kirim notifikasi ke wali santri
                                </label>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="ri-check-line me-1"></i> Setujui &amp; Catat Poin
                            </button>
                        </form>

                        <hr>

                        <form method="POST"
                              action="{{ route('user.asrama.rewards.approvals.reject', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'requestUuid' => $rewardRequest->id]) }}">
                            @csrf
                            <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control mb-2" rows="2" required
                                      placeholder="Contoh: bukan kewenangan asrama / data prestasi perlu diperbaiki">{{ old('rejection_reason') }}</textarea>
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="ri-close-circle-line me-1"></i> Tolak Pengajuan
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Keputusan Asrama</h5></div>
                    <div class="card-body">
                        <p class="mb-2">
                            Status:
                            <span class="badge bg-{{ $rewardRequest->status_badge }}-subtle text-{{ $rewardRequest->status_badge }}">
                                {{ $rewardRequest->status_label }}
                            </span>
                        </p>
                        @if($rewardRequest->reviewedBy)
                            <p class="mb-2 small text-muted">
                                Diproses {{ $rewardRequest->reviewed_at?->format('d/m/Y H:i') }}
                                oleh {{ $rewardRequest->reviewedBy->name }}.
                            </p>
                        @endif
                        @if($rewardRequest->rejection_reason)
                            <div class="alert alert-warning mb-2">{{ $rewardRequest->rejection_reason }}</div>
                        @endif
                        @if($rewardRequest->reward)
                            <a href="{{ route('user.asrama.rewards.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'rewardUuid' => $rewardRequest->reward->id]) }}"
                               class="btn btn-primary w-100">
                                <i class="ri-medal-line me-1"></i> Lihat Penghargaan
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
<script>
(function () {
    const select = document.getElementById('approvalCatalog');
    if (!select) return;

    const category = document.getElementById('approvalCategory');
    const level = document.getElementById('approvalLevel');
    const points = document.getElementById('approvalPoints');

    function sync() {
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) return;
        category.value = option.dataset.category || '';
        level.value = option.dataset.level || '';
        points.value = option.dataset.points || '0';
    }

    select.addEventListener('change', sync);
    if (select.value) sync();
})();
</script>
@endsection
