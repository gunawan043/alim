@extends('layouts.master')
@section('title', $session->session_name)
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $scored = $session->participants->filter(fn ($participant) => $participant->score !== null)->count();
        $certified = $session->participants->filter(fn ($participant) => $participant->certificate !== null)->count();
        $verified = $session->participants->filter(fn ($participant) => $participant->verification_status === 'verified')->count();
    @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') <a href="{{ route('user.tahfidz.tasmi.index', ['userId'=>$userId]) }}">Tasmi’</a> @endslot @slot('li_2') Detail @endslot @slot('title') {{ $session->session_name }} @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><strong>Periksa data.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-1">{{ $session->session_name }}</h5>
                <div class="text-muted small">{{ $session->session_date?->format('d M Y') }} · {{ $session->session_time_start ? substr($session->session_time_start,0,5) : '—' }}{{ $session->session_time_end ? '–'.substr($session->session_time_end,0,5) : '' }} · {{ $session->location ?? 'Tempat belum ditentukan' }}</div>
                <div class="small mt-1">{{ $session->session_type_label }} · {{ $session->school?->name ?? 'Departemen / semua sekolah' }} · {{ $session->academicYear?->name }}</div>
            </div>
            <div class="d-flex gap-2 align-items-start">
                <span class="badge {{ $session->status==='selesai' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $session->status_label }}</span>
                <a href="{{ route('user.tahfidz.tasmi.certificates', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-certificate-line me-1"></i>Syahadah</a>
                <a href="{{ route('user.tahfidz.tasmi.index', ['userId'=>$userId]) }}" class="btn btn-light">Kembali</a>
            </div>
        </div>
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-4"><div class="border rounded p-3"><div class="text-muted small">Peserta</div><div class="fs-4 fw-bold">{{ $session->participants->count() }}</div></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><div class="text-muted small">Sudah Dinilai</div><div class="fs-4 fw-bold text-success">{{ $scored }}</div></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><div class="text-muted small">Syahadah Terbit</div><div class="fs-4 fw-bold text-primary">{{ $certified }}</div></div></div>
            </div>
        </div>
        <div class="card-body border-bottom">
            <div class="row g-2 text-center">
                @foreach([
                    ['1', 'Kesiapan & Jadwal', $session->participants->isNotEmpty(), 'Peserta terdaftar'],
                    ['2', 'Penilaian Guru', $scored > 0, 'Nilai mustami’'],
                    ['3', 'Verifikasi Koor.', $verified > 0, 'Validasi nilai'],
                    ['4', 'Syahadah', $certified > 0, 'Sertifikat terbit'],
                ] as [$no, $label, $done, $note])
                    <div class="col-md-3"><div class="border rounded p-2 h-100"><span class="badge {{ $done ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} mb-1">Langkah {{ $no }}</span><div class="fw-semibold small">{{ $label }}</div><div class="text-muted" style="font-size:10px;">{{ $note }}</div></div></div>
                @endforeach
            </div>
            <div class="small text-muted mt-2"><i class="ri-information-line me-1"></i>Guru/mustami menilai, koordinator memverifikasi, lalu syahadah diterbitkan dan dicetak.</div>
        </div>
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="card-title mb-0">Daftar Peserta</h6>
            <div class="d-flex gap-2">
                @if($canManage)<button type="button" class="btn btn-success btn-sm" onclick="openParticipantModal()"><i class="ri-user-add-line me-1"></i>Tambah Peserta</button>@endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>No</th><th>Santri</th><th>Kelas</th><th>Juz</th><th>Mustami’</th><th>Waktu</th><th>Status</th><th>Verifikasi</th><th>Nilai</th><th>Syahadah</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($session->participants as $participant)
                        @php
                            $canScoreThis = ($canManage || (string) $participant->mustami_id === (string) $userId)
                                && $participant->verification_status !== 'verified';
                            $scorePayload = ['id'=>$participant->id,'student'=>$participant->student?->name,'tahfizh'=>$participant->score?->tahfizh_score_raw,'tajwid'=>$participant->score?->tajwid_score_raw,'fashohah'=>$participant->score?->fashohah_score_raw,'notes'=>$participant->score?->catatan_penilai];
                        @endphp
                        <tr>
                            <td>{{ $participant->nomor_urut ?? $loop->iteration }}</td>
                            <td><div class="fw-medium">{{ $participant->student?->name }}</div><div class="text-muted small">{{ $participant->student?->nis }}</div></td>
                            <td>{{ $participant->student?->currentClassHistory?->studyGroup?->name ?? '—' }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $participant->juz_label }}</span>@if($participant->materi_detail)<div class="text-muted small">{{ $participant->materi_detail }}</div>@endif</td>
                            <td>{{ $participant->mustami?->name ?? '—' }}</td>
                            <td class="small">{{ $participant->waktu_mulai ? substr($participant->waktu_mulai,0,5) : '—' }}{{ $participant->waktu_selesai ? '–'.substr($participant->waktu_selesai,0,5) : '' }}<div class="text-muted">{{ $participant->tempat }}</div></td>
                            <td><span class="badge {{ $participant->status==='selesai' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst(str_replace('_',' ', $participant->status)) }}</span></td>
                            <td><span class="badge {{ $participant->verification_status==='verified' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $participant->verification_label }}</span>@if($participant->verified_at)<div class="text-muted" style="font-size:10px;">{{ $participant->verified_at->format('d/m/Y') }}</div>@endif</td>
                            <td>@if($participant->score)<div class="fw-bold">{{ number_format((float) $participant->score->nilai_akhir, 2) }}</div><div class="small text-muted">{{ $participant->score->predikat_label }} · {{ $participant->score->predikat_arabic }}</div>@else<span class="text-muted">—</span>@endif</td>
                            <td>@if($participant->certificate)<a href="{{ route('user.tahfidz.tasmi.certificate.print', ['userId'=>$userId,'certificateId'=>$participant->certificate->id]) }}" target="_blank" class="badge bg-success-subtle text-success text-decoration-none">{{ $participant->certificate->certificate_number }}</a>@else<span class="text-muted small">Belum</span>@endif</td>
                            <td class="text-end text-nowrap">
                                @if($canScoreThis)<button type="button" class="btn btn-sm btn-outline-success" title="Nilai" data-score="{{ json_encode($scorePayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openScoreModal(JSON.parse(this.dataset.score))"><i class="ri-edit-2-line"></i></button>@endif
                                <a href="{{ route('user.tahfidz.tasmi.participant.instrument', ['userId'=>$userId,'participantId'=>$participant->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="Instrumen"><i class="ri-printer-line"></i></a>
                                @if($canManage && $participant->score && $participant->verification_status !== 'verified')
                                    <form method="POST" action="{{ route('user.tahfidz.tasmi.participant.verify', ['userId'=>$userId,'participantId'=>$participant->id]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success" title="Verifikasi nilai"><i class="ri-check-double-line"></i></button></form>
                                @endif
                                @if($canManage && $participant->score && $participant->verification_status === 'verified' && ! $participant->certificate)
                                    <form method="POST" action="{{ route('user.tahfidz.tasmi.participant.certificate', ['userId'=>$userId,'participantId'=>$participant->id]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary" title="Terbitkan Syahadah"><i class="ri-certificate-line"></i></button></form>
                                @endif
                                @if($canManage)<button type="button" class="btn btn-sm btn-outline-danger remove-participant" title="Keluarkan" data-action="{{ route('user.tahfidz.tasmi.participants.destroy', ['userId'=>$userId,'id'=>$session->id,'participantId'=>$participant->id]) }}" data-name="{{ $participant->student?->name }}"><i class="ri-user-unfollow-line"></i></button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-5"><i class="ri-user-star-line fs-3 d-block mb-2"></i>Belum ada peserta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <div class="modal fade" id="participantModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.tasmi.participants.store', ['userId'=>$userId,'id'=>$session->id]) }}">
                        @csrf
                        <div class="modal-header bg-primary-subtle"><h5 class="modal-title"><i class="ri-user-add-line me-1"></i>Daftarkan Peserta Tasmi’</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            @unless($canManage)<div class="alert alert-info small"><i class="ri-information-line me-1"></i>Anda mendaftarkan santri dari halaqah yang Anda ampu. Koordinator akan menjadwalkan dan memverifikasi.</div>@endunless
                            <div class="row g-3">
                            <div class="col-md-7"><label class="form-label">Santri <span class="text-danger">*</span></label><select name="student_id" class="form-select" required><option value="">—</option>@foreach($availableStudents as $student)<option value="{{ $student->id }}">{{ $student->name }} ({{ $student->nis }})</option>@endforeach</select></div>
                            <div class="col-md-2"><label class="form-label">Juz Utama</label><input type="number" name="juz_tasmi" min="1" max="30" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Daftar Juz</label><input name="juz_detail" class="form-control" placeholder="28,29,30"></div>
                            <div class="col-md-7"><label class="form-label">Materi</label><input name="materi_detail" class="form-control" maxlength="191" placeholder="Juz 30 halaman 580–604"></div>
                            <div class="col-md-5"><label class="form-label">Mustami’</label><select name="mustami_id" class="form-select"><option value="">—</option>@foreach($mustamis as $mustami)<option value="{{ $mustami->id }}">{{ $mustami->name }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Mulai</label><input type="time" name="waktu_mulai" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Selesai</label><input type="time" name="waktu_selesai" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Tempat</label><input name="tempat" class="form-control" maxlength="100" value="{{ $session->location }}"></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Daftarkan Peserta</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canManage || $session->participants->contains(fn ($participant) => (string) $participant->mustami_id === (string) $userId))
        <div class="modal fade" id="scoreModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="" id="scoreForm">
                        @csrf
                        <div class="modal-header bg-success-subtle"><h5 class="modal-title"><i class="ri-edit-2-line me-1"></i>Penilaian Tasmi’ — <span id="scoreStudentName">Peserta</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Tahfizh <span class="text-danger">*</span></label><input type="number" step="0.01" min="0" max="100" name="tahfizh_score_raw" id="score_tahfizh" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label">Tajwid <span class="text-danger">*</span></label><input type="number" step="0.01" min="0" max="100" name="tajwid_score_raw" id="score_tajwid" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label">Fashohah <span class="text-danger">*</span></label><input type="number" step="0.01" min="0" max="100" name="fashohah_score_raw" id="score_fashohah" class="form-control" required></div>
                            <div class="col-12"><div class="alert alert-info mb-0 small"><i class="ri-information-line me-1"></i>Nilai akhir dihitung dari rata-rata tiga komponen. Predikat: ≥90 Mumtaz, ≥80 Jayyid Jiddan, ≥70 Jayyid, ≥60 Maqbul, &lt;60 Rasib.</div></div>
                            <div class="col-12"><label class="form-label">Catatan Penilai</label><textarea name="catatan_penilai" id="score_notes" rows="3" class="form-control"></textarea></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Nilai</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canManage)
        <form id="removeParticipantForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="removeParticipantModal" title="Keluarkan Peserta?" message="<strong id='removeParticipantName'>Peserta</strong> akan dikeluarkan dari sesi ini." submit-label="Ya, Keluarkan" submit-icon="ri-user-unfollow-line" submit-class="btn-danger" form-id="removeParticipantForm" />
    @endif
@endsection

@push('scripts')
<script>
    @php
        $scoreUrlTemplate = route('user.tahfidz.tasmi.participant.score', ['userId'=>$userId,'participantId'=>'__ID__']);
    @endphp
    const scoreUrlTemplate = @json($scoreUrlTemplate);
    function openParticipantModal() {
        if (! window.bootstrap) return;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('participantModal')).show();
    }
    function openScoreModal(data) {
        const form = document.getElementById('scoreForm');
        if (! form || ! data || ! window.bootstrap) return;
        form.action = scoreUrlTemplate.replace('__ID__', data.id);
        document.getElementById('scoreStudentName').textContent = data.student || 'Peserta';
        document.getElementById('score_tahfizh').value = data.tahfizh || '';
        document.getElementById('score_tajwid').value = data.tajwid || '';
        document.getElementById('score_fashohah').value = data.fashohah || '';
        document.getElementById('score_notes').value = data.notes || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('scoreModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any() && $canManage)
            openParticipantModal();
        @endif
        document.querySelectorAll('.remove-participant').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('removeParticipantForm').action = this.dataset.action;
                document.getElementById('removeParticipantName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('removeParticipantModal')).show();
            });
        });
    });
</script>
@endpush
