@extends('layouts.master')
@section('title', $record->title)

@section('css')
    <style>
        .ai-question { border-left: 4px solid #405189; }
        .ai-option { padding: 2px 0; }
        .ai-meta { font-size: 12px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $content = $record->content;
        $items = ($record->type === 'soal' && is_array($content)) ? ($content['items'] ?? []) : [];
        $canEditItems = $record->type === 'soal'
            && ! $record->saved_at
            && $record->status === 'draft'
            && ($isTeam || (string) $record->created_by === (string) auth()->id());
        $canSaveBank = $record->type === 'soal' && $record->isApproved() && ! $record->saved_bank_id && ! empty($items);
        $canSaveRpm = $record->type === 'rpm' && $record->isApproved() && ! $record->saved_perangkat_id && is_array($content) && ! empty($content);
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.ai.index', ['userId' => $userId]) }}">AI Assistant</a> @endslot
        @slot('li_2') Detail Draft @endslot
        @slot('title') {{ $record->title }} @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-1">{{ $record->title }}</h5>
                    <div class="d-flex flex-wrap gap-1 ai-meta">
                        <span class="badge {{ $record->type === 'soal' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">{{ strtoupper($record->type) }}</span>
                        @if($record->isApproved())
                            <span class="badge bg-success-subtle text-success">Disetujui {{ $record->approver?->name ? 'oleh '.$record->approver->name : '' }}</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Draft — belum disetujui</span>
                        @endif
                        <span class="badge bg-secondary-subtle text-secondary">{{ $record->subject?->name ?? '—' }}{{ $record->gradeLevel ? ' · '.$record->gradeLevel->name : '' }}</span>
                        @if($curriculumProfile ?? null)<span class="badge bg-success-subtle text-success">{{ $curriculumProfile->name }}</span>@endif
                        <span class="badge bg-secondary-subtle text-secondary">{{ $record->academicYear?->name }} · {{ ucfirst((string) $record->semester) }}</span>
                        @if($record->provider)
                            <span class="badge bg-dark-subtle text-dark">{{ $record->provider }} · {{ $record->model }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 no-print">
                    @if($isTeam && ! $record->isApproved())
                        <form method="POST" action="{{ route('user.kurikulum.ai.approve', ['userId' => $userId, 'id' => $record->id]) }}">
                            @csrf
                            <button class="btn btn-success"><i class="ri-check-double-line me-1"></i>Setujui Draft</button>
                        </form>
                    @endif

                    @if($record->saved_bank_id)
                        <a href="{{ route('user.bank-soal.show', ['userId' => $userId, 'id' => $record->saved_bank_id]) }}" class="btn btn-info">
                            <i class="ri-archive-line me-1"></i>Lihat di Bank Soal
                        </a>
                    @elseif($canSaveBank)
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saveBankModal">
                            <i class="ri-save-3-line me-1"></i>Simpan ke Bank Soal
                        </button>
                    @endif

                    @if($record->saved_perangkat_id)
                        <a href="{{ route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $record->saved_perangkat_id]) }}" class="btn btn-info">
                            <i class="ri-booklet-line me-1"></i>Lihat {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }}
                        </a>
                    @elseif($canSaveRpm)
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saveRpmModal">
                            <i class="ri-save-3-line me-1"></i>Simpan sebagai {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }}
                        </button>
                    @endif

                    <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="ri-printer-line me-1"></i>Cetak</button>
                    <a href="{{ route('user.kurikulum.ai.index', ['userId' => $userId]) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($record->type === 'rpm' && ! $record->saved_perangkat_id)
                <div class="alert alert-info small">
                    <i class="ri-information-line me-1"></i>
                    Draft AI disimpan sebagai dokumen baru. Perangkat yang dibuat manual tidak akan ditimpa; jika sudah ada perangkat manual untuk konteks yang sama, simpan lewat alur manual/versi baru.
                </div>
            @endif

            @if($canEditItems)
                <div class="alert alert-info small">
                    <i class="ri-edit-2-line me-1"></i>Draft belum disetujui — Anda dapat memperbaiki butir soal sebelum mengajukan persetujuan.
                </div>
                <form method="POST" action="{{ route('user.kurikulum.ai.items.update', ['userId' => $userId, 'id' => $record->id]) }}">
                    @csrf
                    @foreach($items as $index => $item)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-dark-subtle text-dark">Butir {{ $index + 1 }}</span>
                                <select name="items[{{ $index }}][bentuk]" class="form-select form-select-sm" style="width:140px">
                                    <option value="pg" {{ ($item['bentuk'] ?? 'pg') === 'pg' ? 'selected' : '' }}>Pilihan Ganda</option>
                                    <option value="essay" {{ ($item['bentuk'] ?? 'pg') === 'essay' ? 'selected' : '' }}>Essay</option>
                                </select>
                            </div>
                            <label class="form-label small fw-semibold">Pertanyaan</label>
                            <textarea name="items[{{ $index }}][pertanyaan]" class="form-control mb-2" rows="2" required>{{ $item['pertanyaan'] ?? '' }}</textarea>
                            <div class="row g-2">
                                @foreach(['A','B','C','D'] as $label)
                                    <div class="col-md-6">
                                        <label class="form-label small">Pilihan {{ $label }}</label>
                                        <input type="text" name="items[{{ $index }}][pilihan][{{ $label }}]" class="form-control form-control-sm"
                                               value="{{ data_get($item, 'pilihan.'.$label) }}">
                                    </div>
                                @endforeach
                                <div class="col-md-3">
                                    <label class="form-label small">Jawaban</label>
                                    <input type="text" name="items[{{ $index }}][jawaban]" class="form-control form-control-sm" value="{{ $item['jawaban'] ?? '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Level</label>
                                    <input type="text" name="items[{{ $index }}][level]" class="form-control form-control-sm" value="{{ $item['level'] ?? '' }}" maxlength="10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Indikator</label>
                                    <input type="text" name="items[{{ $index }}][indikator]" class="form-control form-control-sm" value="{{ $item['indikator'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Pembahasan</label>
                                    <input type="text" name="items[{{ $index }}][pembahasan]" class="form-control form-control-sm" value="{{ $item['pembahasan'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan Perubahan Butir</button>
                </form>
            @elseif($record->type === 'soal' && ! empty($items))
                <ol class="mb-0">
                    @foreach($items as $index => $item)
                        <li class="mb-4 ai-question ps-3">
                            <div class="d-flex flex-wrap gap-2 mb-2 ai-meta">
                                <span class="badge bg-dark-subtle text-dark">{{ strtoupper($item['bentuk'] ?? 'pg') }}</span>
                                @if(! empty($item['level']))
                                    <span class="badge bg-info-subtle text-info">{{ $item['level'] }}</span>
                                @endif
                                @if(! empty($item['indikator']))
                                    <span class="text-muted">Indikator: {{ $item['indikator'] }}</span>
                                @endif
                            </div>
                            <div class="fw-medium mb-2">{!! nl2br(e($item['pertanyaan'] ?? '')) !!}</div>

                            @if(! empty($item['pilihan']) && is_array($item['pilihan']))
                                <div class="mb-2">
                                    @foreach($item['pilihan'] as $key => $option)
                                        <div class="ai-option"><strong>{{ $key }}.</strong> {{ $option }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="small">
                                <span class="text-success fw-semibold">Jawaban:</span>
                                <span>{{ $item['jawaban'] ?? '—' }}</span>
                            </div>
                            @if(! empty($item['pembahasan']))
                                <div class="small text-muted mt-1"><strong>Pembahasan:</strong> {{ $item['pembahasan'] }}</div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @elseif($record->type === 'rpm' && is_array($content))
                @if(! empty($content['judul']))
                    <h4 class="mb-3">{{ $content['judul'] }}</h4>
                @endif

                @if(! empty($content['identitas']) && is_array($content['identitas']))
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                            @foreach($content['identitas'] as $key => $value)
                                <tr>
                                    <th class="text-capitalize" style="width:220px">{{ str_replace('_', ' ', $key) }}</th>
                                    <td>{{ is_array($value) ? implode(', ', $value) : $value }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(! empty($content['capaian']))
                    <h6 class="mt-3">Capaian</h6>
                    <p>{!! nl2br(e(is_array($content['capaian']) ? implode("\n", $content['capaian']) : $content['capaian'])) !!}</p>
                @endif

                @foreach(['tujuan' => 'Tujuan Pembelajaran', 'alur' => 'Alur Tujuan Pembelajaran', 'media' => 'Media'] as $key => $label)
                    @if(! empty($content[$key]) && is_array($content[$key]))
                        <h6 class="mt-3">{{ $label }}</h6>
                        <ul>
                            @foreach($content[$key] as $entry)
                                <li>{{ is_array($entry) ? implode(' — ', $entry) : $entry }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach

                @if(! empty($content['langkah']) && is_array($content['langkah']))
                    <h6 class="mt-3">Langkah Pembelajaran</h6>
                    @foreach($content['langkah'] as $step)
                        <div class="border rounded p-3 mb-2">
                            <div class="fw-semibold mb-1">Pertemuan {{ $step['pertemuan'] ?? $loop->iteration }}</div>
                            @foreach(['pembuka' => 'Pembuka', 'inti' => 'Inti', 'penutup' => 'Penutup'] as $key => $label)
                                @if(! empty($step[$key]))
                                    <div class="small mb-1"><strong>{{ $label }}:</strong> {{ is_array($step[$key]) ? implode(' ', $step[$key]) : $step[$key] }}</div>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                @endif

                @if(! empty($content['asesmen']) && is_array($content['asesmen']))
                    <h6 class="mt-3">Asesmen</h6>
                    <ul>
                        @foreach($content['asesmen'] as $key => $value)
                            <li><strong class="text-capitalize">{{ $key }}:</strong> {{ is_array($value) ? implode(' ', $value) : $value }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(! empty($content['refleksi']))
                    <h6 class="mt-3">Refleksi</h6>
                    <p>{!! nl2br(e(is_array($content['refleksi']) ? implode("\n", $content['refleksi']) : $content['refleksi'])) !!}</p>
                @endif

                @if(empty($content['judul']) && empty($content['langkah']) && empty($content['tujuan']))
                    <pre class="bg-light p-3 rounded" style="max-height:600px;overflow:auto;">{{ $record->raw_content }}</pre>
                @endif
            @else
                <div class="alert alert-info small">
                    Model tidak mengembalikan JSON terstruktur. Konten mentah ditampilkan di bawah — dapat disalin/diolah manual.
                </div>
                <pre class="bg-light p-3 rounded" style="max-height:600px;overflow:auto;">{{ $record->raw_content }}</pre>
            @endif
        </div>
    </div>
@endsection


@section('modal')
    @if($canSaveBank)
        <form id="saveBankForm" method="POST" action="{{ route('user.kurikulum.ai.save-bank', ['userId' => $userId, 'id' => $record->id]) }}">
            @csrf
        </form>
        <x-modal-confirm id="saveBankModal"
            icon="https://cdn.lordicon.com/lupuorrc.json"
            title="Simpan ke Bank Soal?"
            message="Draft soal yang disetujui akan disalin ke <strong>Bank Soal</strong> sebagai soal berstatus draft ({{ count($items) }} butir). Anda dapat menyunting/mengajukan review dari modul Bank Soal."
            submit-label="Ya, Simpan"
            submit-icon="ri-save-3-line"
            submit-class="btn-primary"
            form-id="saveBankForm" />
    @endif

    @if($canSaveRpm)
        <form id="saveRpmForm" method="POST" action="{{ route('user.kurikulum.ai.save-rpm', ['userId' => $userId, 'id' => $record->id]) }}">
            @csrf
        </form>
        <x-modal-confirm id="saveRpmModal"
            icon="https://cdn.lordicon.com/lupuorrc.json"
            title="Simpan sebagai {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }}?"
            message="Draft {{ $curriculumLabels['perangkat_short'] ?? 'RPM' }} yang disetujui akan dibuat sebagai <strong>{{ $curriculumLabels['perangkat_label'] ?? 'Perangkat Pembelajaran' }}</strong> berstatus draft. Bila sudah ada dokumen current untuk konteks yang sama, sistem membuat versi baru."
            submit-label="Ya, Simpan"
            submit-icon="ri-save-3-line"
            submit-class="btn-primary"
            form-id="saveRpmForm" />
    @endif
@endsection
