@extends('layouts.master')
@section('title', 'Keluhan & Aduan')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('personalia._gtk-ui')
    @component('components.breadcrumb') @slot('li_1') Humas @endslot @slot('li_2') Layanan @endslot @slot('title') Keluhan &amp; Aduan @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-2 mb-3">
        @foreach([['Total Aduan', $stats['total'], 'ri-customer-service-2-line', 'primary'], ['Terbuka', $stats['open'], 'ri-time-line', 'warning'], ['Selesai', $stats['resolved'], 'ri-checkbox-circle-line', 'success']] as [$label, $value, $icon, $color])
            <div class="col-md-4"><div class="card card-animate"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><span class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded"><i class="{{ $icon }} text-{{ $color }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div></div></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-customer-service-2-line text-primary me-1"></i>Daftar Aduan</h5><p class="text-muted mb-0 small">Aduan wali/pegawai masuk ke Humas dan dapat ditindaklanjuti.</p></div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#complaintModal"><i class="ri-add-line me-1"></i>Kirim Aduan</button>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Semua status</option>@foreach(['open'=>'Terbuka','in_progress'=>'Diproses','resolved'=>'Selesai','rejected'=>'Ditolak'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select></div>
            </form>
            <div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Subjek</th><th>Kategori</th><th>Pengirim</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($complaints as $complaint)
                <tr><td><div class="fw-medium">{{ $complaint->subject }}</div><div class="text-muted small">{{ \Illuminate\Support\Str::limit($complaint->message, 100) }}</div></td><td><span class="badge bg-light text-dark">{{ ucfirst($complaint->category) }}</span></td><td>{{ $complaint->submitter?->name ?? '-' }}</td><td><span class="badge bg-{{ $complaint->status === 'resolved' ? 'success' : ($complaint->status === 'rejected' ? 'danger' : 'warning') }}-subtle text-{{ $complaint->status === 'resolved' ? 'success' : ($complaint->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst(str_replace('_',' ',$complaint->status)) }}</span></td><td>{{ $complaint->created_at?->format('d/m/Y') }}</td><td class="text-end">@if($isHumas)<button class="btn btn-sm btn-soft-primary" data-complaint="{{ json_encode(['id'=>$complaint->id,'subject'=>$complaint->subject,'response'=>$complaint->response,'status'=>$complaint->status], JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openComplaintResponse(this)"><i class="ri-reply-line me-1"></i>Tanggapi</button>@else<span class="text-muted small">—</span>@endif</td></tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada aduan.</td></tr>@endforelse
            </tbody></table></div>
            {{ $complaints->links() }}
        </div>
    </div>

    <div class="modal fade" id="complaintModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('user.humas.complaints.store', ['userId'=>$userId]) }}">@csrf<div class="modal-header"><h5 class="modal-title">Kirim Aduan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Kategori</label><select name="category" class="form-select" required><option value="umum">Umum</option><option value="akademik">Akademik</option><option value="asrama">Asrama</option><option value="keuangan">Keuangan</option><option value="layanan">Layanan</option></select></div><div class="mb-3"><label class="form-label">Subjek</label><input name="subject" class="form-control" required></div><div><label class="form-label">Aduan</label><textarea name="message" rows="4" class="form-control" required></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Kirim</button></div></form></div></div></div>
    @if($isHumas)<div class="modal fade" id="responseModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" id="responseForm" action="">@csrf<div class="modal-header"><h5 class="modal-title">Tanggapi Aduan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="fw-semibold mb-3" id="responseSubject"></div><div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="in_progress">Diproses</option><option value="resolved">Selesai</option><option value="rejected">Ditolak</option><option value="open">Terbuka</option></select></div><div><label class="form-label">Tanggapan</label><textarea name="response" rows="4" class="form-control" required></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Tanggapan</button></div></form></div></div></div>@endif
@endsection
@section('script')
<script>
@if($isHumas)
const responseUrl=@json(route('user.humas.complaints.respond',['userId'=>$userId,'id'=>'__ID__']));
function openComplaintResponse(btn){const d=JSON.parse(btn.dataset.complaint);document.getElementById('responseForm').action=responseUrl.replace('__ID__',d.id);document.getElementById('responseSubject').textContent=d.subject;document.querySelector('#responseForm [name=status]').value=d.status;document.querySelector('#responseForm [name=response]').value=d.response||'';new bootstrap.Modal(document.getElementById('responseModal')).show();}
@endif
</script>
@endsection
