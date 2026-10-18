@extends('layouts.master')
@section('title') Laporan DLH Pengelolaan Sampah @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.waste.index') }}">Pengelolaan Sampah</a> @endslot
        @slot('title') Laporan DLH
    @endcomponent

    @php $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'); @endphp
    <div class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-2">
        <form method="GET" action="{{ route('sarpras.waste.report') }}" class="d-flex gap-2 align-items-end"><div><label class="form-label small fw-semibold">Bulan Laporan</label><input type="month" name="month" class="form-control" value="{{ $month }}"></div><button class="btn btn-primary">Tampilkan</button></form>
        <div class="d-flex gap-2"><a href="{{ route('sarpras.waste.report.pdf', ['month' => $month]) }}" class="btn btn-danger"><i class="ri-file-pdf-line me-1"></i>Export PDF</a><a href="{{ route('sarpras.waste.index') }}" class="btn btn-light">Kembali</a></div>
    </div></div>

    <div class="card mb-3"><div class="card-header"><h5 class="card-title mb-0">Rekap Sampah — {{ $monthLabel }}</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Jenis Sampah</th><th class="text-end">Berat (kg)</th><th class="text-end">Jumlah Karung</th></tr></thead><tbody>
        @forelse($summary as $row)
            <tr><td>{{ ucfirst($row->waste_type) }}</td><td class="text-end">{{ number_format($row->weight, 2, ',', '.') }}</td><td class="text-end">{{ number_format($row->sacks) }}</td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">Belum ada data.</td></tr>
        @endforelse
    </tbody><tfoot class="table-light"><tr><th>Total</th><th class="text-end">{{ number_format($summary->sum('weight'), 2, ',', '.') }}</th><th class="text-end">{{ number_format($summary->sum('sacks')) }}</th></tr></tfoot></table></div></div></div>

    <div class="card mb-3"><div class="card-header"><h5 class="card-title mb-0">Rincian Catatan Sampah</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Sumber</th><th>Jenis</th><th class="text-end">Berat</th><th class="text-end">Karung</th><th>Pemilahan</th><th>Tujuan</th><th>Foto</th><th>Verifikasi</th></tr></thead><tbody>
        @forelse($logs as $log)
            @php $photo = $log->photo_url ?: ($log->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($log->photo_path) : null); @endphp
            <tr><td class="small">{{ $log->log_date?->format('d/m/Y') }}</td><td class="small">{{ $log->source?->name }}</td><td>{{ ucfirst($log->waste_type) }}</td><td class="text-end">{{ number_format($log->weight_kg,2,',','.') }}</td><td class="text-end">{{ number_format($log->sacks_count) }}</td><td class="small">{{ ucfirst($log->sorting_status) }}</td><td class="small">{{ ucfirst($log->destination) }}</td><td>@if($photo)<a href="{{ $photo }}" target="_blank">Lihat foto</a>@else - @endif</td><td class="small">{{ $log->verifier?->name ?? 'Belum' }}</td></tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted">Belum ada catatan sampah.</td></tr>
        @endforelse
    </tbody></table></div></div></div>

    <div class="card"><div class="card-header"><h5 class="card-title mb-0">Penyerahan ke DLH / Pihak Ketiga</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Tujuan</th><th>Jenis</th><th class="text-end">Berat</th><th class="text-end">Karung</th><th>No. Tanda Terima</th><th>Foto</th><th>Petugas</th></tr></thead><tbody>
        @forelse($handovers as $handover)
            @php $photo = $handover->photo_url ?: ($handover->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($handover->photo_path) : null); @endphp
            <tr><td class="small">{{ $handover->handover_date?->format('d/m/Y') }}</td><td class="small">{{ $handover->destination }}</td><td>{{ ucfirst($handover->waste_type ?? '-') }}</td><td class="text-end">{{ number_format($handover->total_weight_kg,2,',','.') }}</td><td class="text-end">{{ number_format($handover->total_sacks) }}</td><td>{{ $handover->receipt_number ?? '-' }}</td><td>@if($photo)<a href="{{ $photo }}" target="_blank">Lihat foto</a>@else - @endif</td><td>{{ $handover->officer?->name ?? '-' }}</td></tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">Belum ada penyerahan.</td></tr>
        @endforelse
    </tbody></table></div></div></div>
@endsection
