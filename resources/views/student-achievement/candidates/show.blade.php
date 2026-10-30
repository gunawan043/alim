@extends('layouts.master')
@section('title') Posting Mumtaz Murtafi' @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Kesiswaan @endslot
        @slot('li_2')
            <a href="{{ route('user.student-achievement.candidates.index', ['userId' => $userId]) }}">
                Kandidat Mumtaz Murtafi'
            </a>
        @endslot
        @slot('title') Posting Prestasi @endslot
    @endcomponent

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $candidateStudent = $candidate->student;
        $defaultEventName = "Mumtaz Murtafi' — Rapor ".ucfirst($candidate->semester).' '.($candidate->academicYear?->name ?? '');
        $isPending = $candidate->status === \App\Models\MumtazMurtafiCandidate::STATUS_PENDING;
    @endphp

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ri-award-line me-1"></i> Data Kandidat</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width:125px;">Santri</td>
                            <td>
                                <div class="fw-semibold">{{ $candidateStudent?->name ?? '-' }}</div>
                                <small class="text-muted">{{ $candidateStudent?->nisn ?: $candidateStudent?->nis ?: '-' }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kelas</td>
                            <td>{{ $candidate->studyGroup?->full_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tahun Ajaran</td>
                            <td>{{ $candidate->academicYear?->name ?? '-' }} — {{ $candidate->semester_label }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Rata-rata</td>
                            <td class="fw-semibold">{{ $candidate->average_score !== null ? number_format((float) $candidate->average_score, 1) : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Peringkat</td>
                            <td>{{ $candidate->class_rank ? '#'.$candidate->class_rank : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Predikat</td>
                            <td><span class="badge bg-success-subtle text-success">Mumtaz Murtafi'</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                <span class="badge bg-{{ $candidate->status_badge }}-subtle text-{{ $candidate->status_badge }}">
                                    {{ $candidate->status_label }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            @if($isPending)
                <div class="card border-primary">
                    <div class="card-header bg-primary-subtle">
                        <h5 class="mb-0 text-primary"><i class="ri-send-plane-line me-1"></i> Posting ke Prestasi Santri</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info small">
                            Bapak/Ibu dapat langsung memposting tanpa file, atau melampirkan piagam/sertifikat.
                            Setelah diposting, data otomatis menjadi Prestasi Santri dan pengajuan penghargaan
                            asrama masuk ke proses approval asrama.
                        </div>

                        <form method="POST"
                              action="{{ route('user.student-achievement.candidates.post', ['userId' => $userId, 'uuid' => $candidate->id]) }}"
                              enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Prestasi <span class="text-danger">*</span></label>
                                <input type="text" name="event_name" class="form-control" required maxlength="191"
                                       value="{{ old('event_name', $defaultEventName) }}">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal</label>
                                    <input type="date" name="event_date" class="form-control" required
                                           value="{{ old('event_date', now()->toDateString()) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Penyelenggara</label>
                                    <input type="text" name="organizer" class="form-control" maxlength="191"
                                           value="{{ old('organizer', $candidate->school?->name) }}">
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Piagam / Sertifikat <span class="text-muted">(opsional)</span></label>
                                <input type="file" name="certificate" class="form-control"
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <div class="form-text">Kosongkan jika ingin langsung posting tanpa file. Maksimal 5 MB.</div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2" maxlength="500"
                                          placeholder="Catatan tambahan...">{{ old('notes', "Ditetapkan dari rapor ".$candidate->semester_label.' '.($candidate->academicYear?->name ?? '')) }}</textarea>
                            </div>

                            <hr>

                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="notify_wali" value="0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="notify_wali" name="notify_wali" value="1"
                                       {{ old('notify_wali', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_wali">
                                    Kirim WhatsApp prestasi ke wali santri
                                </label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="notify_dormitory" value="0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="notify_dormitory" name="notify_dormitory" value="1"
                                       {{ old('notify_dormitory', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_dormitory">
                                    Kirim notifikasi in-app ke asrama
                                </label>
                                <div class="form-text">Tidak mengirim WhatsApp ke asrama.</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="ri-send-plane-line me-1"></i> Posting Prestasi
                                </button>
                                <a href="{{ route('user.student-achievement.candidates.index', ['userId' => $userId]) }}"
                                   class="btn btn-light">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Status Kandidat</h5></div>
                    <div class="card-body">
                        <p>
                            Kandidat ini sudah <strong>{{ $candidate->status_label }}</strong>.
                        </p>
                        @if($candidate->achievement)
                            <a href="{{ route('user.student-achievement.show', [
                                'userId' => $userId,
                                'id' => $candidate->achievement->id,
                                'type' => 'akademik',
                            ]) }}" class="btn btn-primary">
                                <i class="ri-eye-line me-1"></i> Lihat Prestasi
                            </a>
                        @endif
                        <a href="{{ route('user.student-achievement.candidates.index', ['userId' => $userId]) }}"
                           class="btn btn-light">Kembali</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
