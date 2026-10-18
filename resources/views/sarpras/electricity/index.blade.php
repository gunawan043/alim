@extends('layouts.master')
@section('title') Listrik, Token & Penanganan Padam @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Listrik & Token
    @endcomponent

    <div class="row g-3 mb-3">
        @foreach([
            ['Meter Listrik', $stats['meters'], 'primary', 'ri-flashlight-line'],
            ['Sudah Diisi Bulan Ini', $stats['purchased'], 'success', 'ri-check-double-line'],
            ['Nominal Bulan Ini', 'Rp '.number_format($stats['amount'], 0, ',', '.'), 'info', 'ri-money-dollar-circle-line'],
            ['Padam Belum Selesai', $stats['open_outages'], 'danger', 'ri-alarm-warning-line'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-3 d-flex align-items-center gap-3"><span class="avatar-sm"><span class="avatar-title rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} fs-4"><i class="{{ $icon }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-5 fw-bold">{{ is_numeric($value) ? number_format($value) : $value }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="alert alert-warning d-flex align-items-center gap-2"><i class="ri-alarm-warning-line fs-5"></i><span>Pengisian token listrik paling lambat <strong>tanggal 2</strong> setiap bulan. Keterlambatan dapat menyebabkan meter listrik padam.</span></div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-flashlight-line me-2 text-primary"></i>Meter & Pengisian Token</h5><p class="text-muted small mb-0">Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</p></div>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('sarpras.electricity.index') }}" class="d-flex gap-2"><input type="month" name="period" class="form-control form-control-sm" value="{{ $period }}"><button class="btn btn-sm btn-primary">Tampilkan</button></form>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addMeterModal"><i class="ri-add-line me-1"></i>Tambah Meter</button>
            </div>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Nama Meter</th><th>Jenis / No. Meter</th><th>Lokasi</th><th class="text-end">Default</th><th>Status Bulan Ini</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($meters as $i => $meter)
                @php $purchase = $meter->purchases->first(); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><div class="fw-medium">{{ $meter->name }}</div><small class="text-muted">{{ $meter->notes }}</small></td>
                    <td class="small">{{ strtoupper($meter->meter_type) }}<br><code>{{ $meter->meter_number }}</code></td>
                    <td class="small">{{ $meter->location ?? '-' }}</td>
                    <td class="text-end small">{{ $meter->default_amount ? number_format($meter->default_amount, 0, ',', '.') : '-' }}</td>
                    <td>@if($purchase)<span class="badge bg-success-subtle text-success">Rp {{ number_format($purchase->amount, 0, ',', '.') }}</span><div class="small text-muted">{{ $purchase->purchased_at?->format('d/m/Y') }}</div>@else<span class="badge bg-warning-subtle text-warning">Belum diisi</span>@endif</td>
                    <td class="text-end"><div class="d-inline-flex gap-1"><button class="btn btn-sm btn-outline-success purchase-meter" data-bs-toggle="modal" data-bs-target="#purchaseModal" data-action="{{ route('sarpras.electricity.purchase.store', ['meterId' => $meter->id]) }}" data-name="{{ $meter->name }}" data-amount="{{ $meter->default_amount }}"><i class="ri-add-circle-line"></i></button><form method="POST" action="{{ route('sarpras.electricity.meter.toggle', ['meterId' => $meter->id]) }}">@csrf<button class="btn btn-sm btn-outline-danger"><i class="ri-power-line"></i></button></form></div></td>
                </tr>
            @empty
                <x-empty-state colspan="7" title="Belum ada meter listrik" message="Tambahkan meter atau jalankan seeder listrik." />
            @endforelse
        </tbody></table></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-alarm-warning-line me-2 text-danger"></i>Penanganan Listrik Padam</h5><p class="text-muted small mb-0">Catat kejadian, tindakan petugas, waktu menyala kembali, dan foto sebelum/sesudah.</p></div><button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addOutageModal"><i class="ri-add-line me-1"></i>Catat Kejadian</button></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Waktu</th><th>Lokasi</th><th>Jenis</th><th>Penyebab</th><th>Petugas</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($outages as $outage)
                @php $tone = $outage->status === 'resolved' ? 'success' : 'danger'; @endphp
                <tr><td class="small">{{ $outage->occurred_at?->format('d/m/Y H:i') }}</td><td class="small">{{ $outage->location }}</td><td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_',' ',$outage->outage_type)) }}</span></td><td class="small">{{ \Illuminate\Support\Str::limit($outage->cause, 50) ?: '-' }}</td><td class="small">{{ $outage->actions->first()?->officer?->name ?? '-' }}</td><td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ $outage->status === 'resolved' ? 'Menyala' : 'Padam' }}</span></td><td class="text-end"><a href="{{ route('sarpras.electricity.outage.show', ['outageId' => $outage->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a></td></tr>
            @empty
                <x-empty-state colspan="7" title="Belum ada kejadian listrik padam" message="Catat kejadian untuk memulai laporan penanganan." />
            @endforelse
        </tbody></table></div></div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2"></i>Riwayat Pengisian Bulan Ini</h5></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Meter</th><th>Lokasi</th><th class="text-end">Nominal</th><th>Bukti</th><th>Dicatat</th></tr></thead><tbody>
            @forelse($purchases as $purchase)
                <tr><td class="small">{{ $purchase->purchased_at?->format('d/m/Y') }}</td><td class="small">{{ $purchase->meter?->name }}</td><td class="small">{{ $purchase->meter?->location }}</td><td class="text-end">Rp {{ number_format($purchase->amount,0,',','.') }}</td><td>@if($purchase->receipt_url)<a href="{{ $purchase->receipt_url }}" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a>@elseif($purchase->receipt_path)<a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($purchase->receipt_path) }}" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a>@else - @endif</td><td class="small">{{ $purchase->recorder?->name ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada pengisian pada periode ini.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>

    <div class="modal fade" id="addMeterModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.electricity.meter.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Meter Listrik</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Nama Meter</label><input name="name" class="form-control" required></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Jenis</label><select name="meter_type" class="form-select"><option value="prabayar">Prabayar</option><option value="pascabayar">Pascabayar</option></select></div><div class="col-md-6"><label class="form-label">No. Meter</label><input name="meter_number" class="form-control" required></div><div class="col-md-6"><label class="form-label">Lokasi</label><input name="location" class="form-control"></div><div class="col-md-3"><label class="form-label">Default (Rp)</label><input type="number" name="default_amount" class="form-control" min="0"></div><div class="col-md-3"><label class="form-label">Jatuh Tempo</label><input type="number" name="payment_due_day" class="form-control" min="1" max="31"></div></div><div class="mt-3"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan Meter</button></div></form></div></div>

    <div class="modal fade" id="purchaseModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" id="purchaseForm" enctype="multipart/form-data" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Catat Pengisian Token</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="fw-medium" id="purchaseName">Meter</p><div class="mb-3"><label class="form-label">Periode</label><input type="month" name="period" class="form-control" value="{{ $period }}" required></div><div class="mb-3"><label class="form-label">Nominal (Rp)</label><input type="number" name="amount" id="purchaseAmount" class="form-control" min="1" required></div><div class="mb-3"><label class="form-label">Tanggal Isi</label><input type="date" name="purchased_at" class="form-control" value="{{ date('Y-m-d') }}"></div><div class="mb-3"><label class="form-label">Link Bukti Google Drive</label><input type="url" name="receipt_url" class="form-control" placeholder="https://drive.google.com/..."></div><div><label class="form-label">Atau Upload Bukti</label><input type="file" name="receipt" class="form-control" accept="image/*"></div></div><div class="modal-footer"><button class="btn btn-success">Simpan</button></div></form></div></div>

    @include('sarpras.partials.camera-modal')

    <div class="modal fade" id="addOutageModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('sarpras.electricity.outage.store') }}" enctype="multipart/form-data" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Catat Listrik Padam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Waktu Kejadian</label><input type="datetime-local" name="occurred_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div><div class="mb-3"><label class="form-label">Lokasi</label><input name="location" class="form-control" required></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Jenis Padam</label><select name="outage_type" class="form-select"><option value="tidak_terencana">Tidak Terencana</option><option value="terencana">Terencana</option></select></div><div class="col-md-6"><label class="form-label">Pelapor</label><input name="reporter_name" class="form-control" value="{{ auth()->user()->name }}"></div></div><div class="mt-3"><label class="form-label">Penyebab Singkat</label><textarea name="cause" class="form-control" rows="2"></textarea></div><div class="mt-3"><label class="form-label">Foto Sebelum / Link Drive</label><input type="url" name="before_url" class="form-control mb-1" placeholder="Link Google Drive"><input type="file" id="outage_before_file" name="before_photo" class="form-control" accept="image/*"><button type="button" class="btn btn-outline-danger btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#alimCameraModal" data-target="outage_before_file" data-preview="outage_before_preview"><i class="ri-camera-line me-1"></i>Kamera + watermark</button><div id="outage_before_preview"></div></div><div class="mt-3"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-danger">Simpan Kejadian</button></div></form></div></div>
@endsection

@push('script')
<script>
    document.querySelectorAll('.purchase-meter').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('purchaseForm').action = button.dataset.action;
            document.getElementById('purchaseName').textContent = button.dataset.name;
            document.getElementById('purchaseAmount').value = button.dataset.amount || '';
        });
    });
</script>
@endpush
