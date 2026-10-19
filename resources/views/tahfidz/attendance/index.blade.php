@extends('layouts.master')
@section('title', 'Absensi Halaqah')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Operasional @endslot @slot('title') Absensi Halaqah @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row g-2 mb-3">
        @foreach([['1','Pilih Halaqah & Tanggal'],['2','Isi Presensi'],['3','Simpan & Rekap']] as [$no,$label])
            <div class="col-md-4"><div class="border rounded p-2 d-flex align-items-center gap-2 bg-primary-subtle"><span class="badge bg-primary">{{ $no }}</span><span class="fw-semibold small">{{ $label }}</span></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-calendar-check-line text-primary me-1"></i>Pilih Halaqah & Tanggal</h5></div>
        <div class="card-body"><form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label small">Halaqah</label><select name="group_id" class="form-select" onchange="this.form.submit()"><option value="">— Pilih Halaqah —</option>@foreach($groups as $g)<option value="{{ $g->id }}" @selected($group?->id===$g->id)>{{ $g->name }} — {{ $g->school?->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small">Tanggal</label><input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()"></div>
            <div class="col-md-3"><label class="form-label small">Jenis Sesi</label><select name="session_type" class="form-select" onchange="this.form.submit()">@foreach(['setoran'=>'Setoran','murajaah'=>'Murajaah','tasmi'=>'Tasmi’','evaluasi'=>'Evaluasi','tikror'=>'Tikror'] as $key=>$label)<option value="{{ $key }}" @selected($sessionType===$key)>{{ $label }}</option>@endforeach</select></div>
        </form></div>
    </div>

    @if($group)
        @if($holiday)<div class="alert alert-warning mt-2"><i class="ri-calendar-close-line me-1"></i><strong>{{ $holiday->event_name }}</strong> — tanggal ini termasuk {{ str_replace('_',' ', $holiday->event_type) }}. Presensi tetap dapat diisi jika kegiatan khusus.</div>@endif
        @if($schedule)<div class="alert alert-info mt-2"><i class="ri-time-line me-1"></i>Jadwal halaqah: <strong>{{ $schedule->day_name }}, {{ substr((string)$schedule->time_start,0,5) }}–{{ substr($schedule->time_end,0,5) }}</strong> · {{ $schedule->room ?? 'ruang belum diisi' }}</div>@endif

        <div class="row tahfidz-stat mt-1">
            @foreach([['Hadir',$stats['hadir'],'success'],['Izin',$stats['izin'],'info'],['Sakit',$stats['sakit'],'warning'],['Alpa',$stats['alpa'],'danger'],['Belum',$stats['belum'],'secondary']] as [$label,$value,$color])
                <div class="col-xl-2 col-md-4 col-6"><div class="card card-animate"><div class="card-body py-3"><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold text-{{ $color }}">{{ $value }}</div></div></div></div>
            @endforeach
        </div>

        <div class="card mt-1">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0">Presensi {{ $group->name }}</h5><p class="text-muted mb-0 small">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }} · {{ ucfirst($sessionType) }}</p></div>@unless($canInput)<span class="badge bg-warning-subtle text-warning">Hanya lihat</span>@endunless</div>
            <form method="POST" action="{{ route('user.tahfidz.attendance.store', ['userId'=>$userId]) }}">@csrf
                <input type="hidden" name="tahfidz_group_id" value="{{ $group->id }}"><input type="hidden" name="attendance_date" value="{{ $date }}"><input type="hidden" name="session_type" value="{{ $sessionType }}">
                <div class="table-responsive"><table class="table table-hover align-middle mb-0 tahfidz-table"><thead class="table-light"><tr><th>Santri</th><th>NIS</th><th class="text-center">Hadir</th><th class="text-center">Izin</th><th class="text-center">Sakit</th><th class="text-center">Alpa</th><th>Catatan</th></tr></thead><tbody>
                    @forelse($members as $member)
                        @php $record = $attendance->get($member->student_id); $status = $record?->status ?? 'hadir'; @endphp
                        <tr><td class="fw-medium">{{ $member->student?->name }}</td><td>{{ $member->student?->nis }}</td>
                            @foreach(['hadir'=>'Hadir','izin'=>'Izin','sakit'=>'Sakit','alpa'=>'Alpa'] as $value=>$label)
                                <td class="text-center"><input type="radio" class="form-check-input" name="statuses[{{ $member->student_id }}]" value="{{ $value }}" @checked($status===$value) @disabled(! $canInput)></td>
                            @endforeach
                            <td><input name="notes[{{ $member->student_id }}]" class="form-control form-control-sm" value="{{ $record?->notes }}" maxlength="1000" @disabled(! $canInput)></td>
                        </tr>
                    @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada anggota aktif.</td></tr>@endforelse
                </tbody></table></div>
                @if($canInput && $members->isNotEmpty())<div class="card-footer d-flex justify-content-end"><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Simpan Presensi</button></div>@endif
            </form>
        </div>
    @else
        <div class="alert alert-info mt-2">Pilih halaqah untuk mulai mengisi presensi.</div>
    @endif
@endsection
