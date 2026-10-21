@extends('layouts.master')
@section('title', 'Denda & Bebas Pustaka')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $quickBase = ['userId' => $userId, 'unit_id' => $unit->id];
        $filterAction = route('user.perpustakaan.fines.index');
        $filterHidden = ['unit_id' => $unit->id, 'tab' => $tab];
        $filterFields = [
            ['name' => 'search', 'label' => 'Pencarian', 'type' => 'text', 'placeholder' => 'Nama anggota atau nomor transaksi'],
            ['name' => 'status', 'label' => 'Status Denda', 'type' => 'select', 'options' => ['' => 'Semua denda', 'unpaid' => 'Belum dibayar', 'paid' => 'Sudah dibayar', 'waived' => 'Dibebaskan']],
        ];
        $filterControls = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Pencarian', 'value' => $search, 'placeholder' => 'Nama anggota atau nomor transaksi'],
            ['type' => 'select', 'name' => 'status', 'label' => 'Status Denda', 'options' => ['' => 'Semua denda', 'unpaid' => 'Belum dibayar', 'paid' => 'Sudah dibayar', 'waived' => 'Dibebaskan']],
        ];
        $quickFilters = [
            ['label' => 'Daftar Denda', 'url' => route('user.perpustakaan.fines.index', $quickBase + ['tab' => 'fines']), 'icon' => 'ri-hand-coin-line', 'active' => $tab !== 'free' && ! request('status')],
            ['label' => 'Belum Dibayar', 'url' => route('user.perpustakaan.fines.index', $quickBase + ['tab' => 'fines', 'status' => 'unpaid']), 'icon' => 'ri-time-line', 'active' => request('status') === 'unpaid'],
            ['label' => 'Sudah Dibayar', 'url' => route('user.perpustakaan.fines.index', $quickBase + ['tab' => 'fines', 'status' => 'paid']), 'icon' => 'ri-checkbox-circle-line', 'active' => request('status') === 'paid'],
            ['label' => 'Dibebaskan', 'url' => route('user.perpustakaan.fines.index', $quickBase + ['tab' => 'fines', 'status' => 'waived']), 'icon' => 'ri-shield-check-line', 'active' => request('status') === 'waived'],
            ['label' => 'Bebas Pustaka', 'url' => route('user.perpustakaan.fines.index', $quickBase + ['tab' => 'free']), 'icon' => 'ri-file-paper-2-line', 'active' => $tab === 'free'],
        ];
    @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Sirkulasi @endslot @slot('title') Denda &amp; Bebas Pustaka @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-2 mb-3 library-stats">
        @foreach([['Belum Dibayar','Rp '.number_format($stats['outstanding'],0,',','.'),'ri-hand-coin-line','danger'],['Sudah Dibayar','Rp '.number_format($stats['paid'],0,',','.'),'ri-checkbox-circle-line','success'],['Dibebaskan','Rp '.number_format($stats['waived'],0,',','.'),'ri-shield-check-line','secondary'],['Terlambat',$stats['overdue'],'ri-timer-line','warning']] as [$l,$v,$i,$c])
            <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="d-flex gap-2 align-items-center"><span class="avatar-sm"><span class="avatar-title bg-{{ $c }}-subtle rounded"><i class="{{ $i }} text-{{ $c }}"></i></span></span><div><div class="text-muted small">{{ $l }}</div><div class="fs-5 fw-bold">{{ $v }}</div></div></div></div></div></div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line text-primary me-1"></i>Denda &amp; Bebas Pustaka {{ $unit->name }}</h5><p class="text-muted mb-0 small">Denda mengikuti pengaturan unit. Pemutihan wajib disertai catatan.</p></div>
            <span class="badge bg-info-subtle text-info"><i class="ri-information-line me-1"></i>{{ $tab === 'free' ? 'Bebas Pustaka' : 'Daftar Denda' }}</span>
        </div>
        @include('perpustakaan._filter-panel')
        @include('perpustakaan._quick-filters')

        @if($tab === 'free')
            <div class="table-container"><table class="table table-hover library-table align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Nomor Anggota</th><th>Nama</th><th>Pinjaman Aktif</th><th>Denda Belum Lunas</th><th>Status Bebas Pustaka</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($members as $member)
                @php $eligibility = $member->active_loans_count ? 'Belum bebas: masih ada pinjaman' : ($member->unpaid_fine > 0 ? 'Belum bebas: denda belum lunas' : 'Bebas pustaka'); @endphp
                <tr><td><code>{{ $member->member_number }}</code></td><td><div class="fw-medium">{{ $member->display_name }}</div><div class="text-muted small">{{ $member->student?->nis ?? $member->user?->name }}</div></td><td class="text-center">{{ $member->active_loans_count }}</td><td>Rp {{ number_format($member->unpaid_fine,0,',','.') }}</td><td><span class="badge {{ $eligibility === 'Bebas pustaka' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $eligibility }}</span></td><td class="text-end">@if($eligibility === 'Bebas pustaka')<button class="btn btn-sm btn-soft-primary" data-member="{{ json_encode(['name'=>$member->display_name,'number'=>$member->member_number], JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openLetter(this)"><i class="ri-printer-line me-1"></i>Surat</button>@else<span class="text-muted small">—</span>@endif</td></tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada anggota.</td></tr>@endforelse
            </tbody></table></div>
        @else
            <div class="table-container"><table class="table table-hover library-table align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Transaksi</th><th>Anggota</th><th>Jatuh Tempo</th><th>Terlambat</th><th>Denda</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($loans as $loan)
                @php $finePaid = $loan->fine_paid_at; $fineWaived = $loan->fine_waived_at; $displayFine = $loan->estimated_fine; @endphp
                <tr><td><code>{{ $loan->loan_number }}</code><div class="text-muted small">{{ $loan->items->pluck('item.title.title')->filter()->implode(', ') }}</div></td><td>{{ $loan->member?->display_name }}</td><td>{{ $loan->due_date?->format('d/m/Y') }}</td><td>{{ $loan->late_days }} hari</td><td>Rp {{ number_format($displayFine,0,',','.') }}</td><td>@if($finePaid)<span class="badge bg-success-subtle text-success">Lunas</span>@elseif($fineWaived)<span class="badge bg-secondary-subtle text-secondary">Dibebaskan</span>@elseif($loan->status==='active')<span class="badge bg-warning-subtle text-warning">Berjalan</span>@else<span class="badge bg-danger-subtle text-danger">Belum dibayar</span>@endif</td><td class="text-end">@if(!$finePaid && !$fineWaived && $loan->fine_amount > 0)<div class="btn-group btn-group-sm"><button class="btn btn-soft-success" data-fine="{{ json_encode(['id'=>$loan->id,'action'=>'pay','title'=>'Pembayaran Denda','amount'=>$loan->fine_amount], JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openFine(this)"><i class="ri-hand-coin-line"></i></button><button class="btn btn-soft-warning" data-fine="{{ json_encode(['id'=>$loan->id,'action'=>'waive','title'=>'Pembebasan Denda','amount'=>$loan->fine_amount], JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openFine(this)"><i class="ri-shield-check-line"></i></button></div>@else<span class="text-muted small">—</span>@endif</td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-5">Belum ada denda.</td></tr>@endforelse
            </tbody></table></div>
        @endif
    </div>

    <div class="modal fade library-modal" id="fineModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" id="fineForm" action="">@csrf<div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="fineTitle">Denda</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-light border mb-3">Nominal: <strong id="fineAmount"></strong></div><label class="form-label">Catatan</label><textarea name="fine_notes" rows="3" class="form-control" placeholder="Wajib untuk pembebasan"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
    <div class="modal fade" id="letterModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Surat Bebas Pustaka</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="letterBody"></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary" onclick="window.print()"><i class="ri-printer-line me-1"></i>Cetak</button></div></div></div></div>
    @include('perpustakaan._filter-modal')
@endsection
@section('script')
<script>
const finePay=@json(route('user.perpustakaan.fines.pay',['userId'=>$userId,'id'=>'__ID__']));
const fineWaive=@json(route('user.perpustakaan.fines.waive',['userId'=>$userId,'id'=>'__ID__']));
function openFine(btn){const d=JSON.parse(btn.dataset.fine);document.getElementById('fineForm').action=(d.action==='pay'?finePay:fineWaive).replace('__ID__',d.id);document.getElementById('fineTitle').textContent=d.title;document.getElementById('fineAmount').textContent='Rp '+Number(d.amount).toLocaleString('id-ID');document.getElementById('fineForm').querySelector('[name="fine_notes"]').value='';new bootstrap.Modal(document.getElementById('fineModal')).show();}
function openLetter(btn){const d=JSON.parse(btn.dataset.member);document.getElementById('letterBody').innerHTML='<div class="text-center"><div class="fw-bold">SURAT BEBAS PUSTAKA</div><div class="text-muted small mb-3">Perpustakaan {{ $unit->name }}</div></div><p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p><table class="table table-sm table-freeze"><tr><td>Nama</td><td class="fw-semibold">'+d.name+'</td></tr><tr><td>Nomor Anggota</td><td>'+d.number+'</td></tr></table><p class="mb-0">Tidak memiliki pinjaman aktif dan denda yang belum diselesaikan pada Perpustakaan {{ $unit->name }}.</p><div class="text-end mt-4">Petugas Perpustakaan<br><br><br>____________________</div>';new bootstrap.Modal(document.getElementById('letterModal')).show();}
</script>
@endsection
