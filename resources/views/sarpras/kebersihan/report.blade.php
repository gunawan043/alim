@extends('layouts.master')
@section('title') Laporan Kebersihan — {{ $report->area?->name }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.kebersihan.index') }}">Kebersihan</a> @endslot
        @slot('title') Laporan {{ $report->report_date?->format('d/m/Y') }}
    @endcomponent

    @php
        $tone = ['submitted'=>'warning','verified'=>'success','rejected'=>'danger'][$report->status] ?? 'secondary';
        $photoUrl = function ($item, $type) {
            $url = $item->{$type.'_photo_url'} ?? null;
            if ($url) return $url;
            $path = $item->{$type.'_photo_path'} ?? null;
            if (! $path) return null;
            return config('sarpras.photo_disk', 'public') === 'public' ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
        };
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div><h4 class="mb-1">{{ $report->area?->name }}</h4><div class="text-muted small">{{ $report->area?->building?->building_name ?? '-' }} · {{ $report->report_date?->format('d/m/Y') }} · Petugas: {{ $report->officer?->name ?? '-' }}@if($report->substituteFor) (menggantikan {{ $report->substituteFor->name }})@endif</div></div>
                <div class="d-flex align-items-center gap-2"><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }} fs-6">{{ ucfirst(str_replace('_',' ', $report->status)) }}</span>@if($canVerify && $report->status === 'submitted')<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#verifyReportModal"><i class="ri-check-double-line me-1"></i>Verifikasi</button>@endif</div>
            </div>
            @if($report->absence_reason)<div class="alert alert-light border mt-3 mb-0 small"><strong>Alasan backup:</strong> {{ $report->absence_reason }}</div>@endif
            @if($report->coordinator_note)<div class="alert alert-{{ $report->status === 'rejected' ? 'danger' : 'success' }} mt-3 mb-0"><i class="ri-chat-1-line me-1"></i>{{ $report->coordinator_note }}</div>@endif
            <div class="row g-3 mt-1">
                <div class="col-md-3"><div class="text-muted small">Kondisi Keseluruhan</div><div class="fw-medium">{{ ucfirst(str_replace('_',' ', $report->overall_condition)) }}</div></div>
                <div class="col-md-3"><div class="text-muted small">Skor</div><div class="fw-medium">{{ $report->score ?? '-' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Catatan Petugas</div><div>{{ $report->notes ?? '-' }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-list-check-2 me-1"></i>Rincian Pekerjaan</h5></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Uraian</th><th>Hasil</th><th>Kondisi</th><th>Catatan</th><th>Foto Sebelum</th><th>Foto Sesudah</th></tr></thead><tbody>
            @forelse($report->items as $index => $item)
                @php $before = $photoUrl($item, 'before'); $after = $photoUrl($item, 'after'); @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><div class="fw-medium">{{ $item->task?->title }}</div><small class="text-muted">{{ $item->task?->standard }}</small></td>
                    <td><span class="badge bg-{{ $item->result === 'sudah' ? 'success' : ($item->result === 'belum' ? 'danger' : 'secondary') }}-subtle text-{{ $item->result === 'sudah' ? 'success' : ($item->result === 'belum' ? 'danger' : 'secondary') }}">{{ ucfirst(str_replace('_',' ', $item->result)) }}</span></td>
                    <td class="small">{{ ucfirst(str_replace('_',' ', $item->condition ?? '-')) }}</td>
                    <td class="small">{{ $item->notes ?? '-' }}</td>
                    <td>@if($before)<a href="{{ $before }}" target="_blank" class="btn btn-sm btn-outline-info"><i class="ri-image-line me-1"></i>Lihat</a>@else<span class="text-muted small">-</span>@endif</td>
                    <td>@if($after)<a href="{{ $after }}" target="_blank" class="btn btn-sm btn-outline-success"><i class="ri-image-line me-1"></i>Lihat</a>@else<span class="text-muted small">-</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada rincian pekerjaan.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>

    @if($canVerify && $report->status === 'submitted')
        <x-modal-confirm id="verifyReportModal" icon="https://cdn.lordicon.com/lupuorrc.json" title="Verifikasi Laporan Kebersihan?" submitLabel="Simpan Verifikasi" submitIcon="ri-check-double-line" submitClass="btn-success" formId="verifyReportForm">
            <p class="text-center text-muted">Pilih keputusan dan berikan catatan bila perlu.</p>
            <select name="decision" form="verifyReportForm" class="form-select mb-2"><option value="verified">Verifikasi — sesuai standar</option><option value="rejected">Kembalikan — perlu perbaikan</option></select>
            <textarea name="coordinator_note" form="verifyReportForm" class="form-control" rows="3" placeholder="Catatan koordinator…"></textarea>
        </x-modal-confirm>
        <form id="verifyReportForm" method="POST" action="{{ route('sarpras.kebersihan.report.verify', $report->id) }}" class="d-none">@csrf</form>
    @endif
@endsection
