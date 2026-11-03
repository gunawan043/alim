@extends('layouts.master')

@section('title', 'Detail Insiden')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2')
            <a href="{{ route('user.keamanan.incidents.index', ['userId' => $userId]) }}">Insiden Keamanan</a>
        @endslot
        @slot('title') {{ $incident->code }} @endslot
    @endcomponent

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex flex-wrap gap-2 align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">{{ $incident->title }}</h5>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-{{ $incident->severityColor() }}-subtle text-{{ $incident->severityColor() }}">
                                {{ $incident->severityLabel() }}
                            </span>
                            <span class="badge bg-{{ $incident->statusColor() }}-subtle text-{{ $incident->statusColor() }}">
                                {{ $incident->statusLabel() }}
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $incident->type->name ?? 'Tanpa jenis' }}</span>
                        </div>
                    </div>
                    <a href="{{ route('user.keamanan.incidents.index', ['userId' => $userId]) }}" class="btn btn-light btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Waktu Kejadian</div>
                            <div class="fw-semibold">{{ optional($incident->occurred_at)->translatedFormat('d F Y, H:i') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Lokasi</div>
                            <div class="fw-semibold">{{ $incident->location ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Pos Jaga</div>
                            <div class="fw-semibold">{{ $incident->post->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Zona Area</div>
                            <div class="fw-semibold">{{ $incident->zone->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Pelapor</div>
                            <div class="fw-semibold">{{ $incident->reporter->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Penanggung Jawab</div>
                            <div class="fw-semibold">{{ $incident->assignee->name ?? 'Belum ditugaskan' }}</div>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted small mb-2">Uraian Kejadian</h6>
                    <p class="mb-4" style="white-space: pre-line;">{{ $incident->description }}</p>

                    @if($incident->isClosed())
                        <div class="alert alert-success mb-0">
                            <h6 class="alert-heading"><i class="ri-checkbox-circle-line me-1"></i>Insiden Selesai</h6>
                            <p class="mb-1">{{ $incident->resolution }}</p>
                            <div class="small">
                                Ditutup oleh {{ $incident->closer->name ?? '—' }}
                                pada {{ optional($incident->closed_at)->translatedFormat('d F Y, H:i') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-1"></i> Riwayat Tindak Lanjut</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Waktu</th>
                                    <th>Tindakan</th>
                                    <th>Oleh</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($incident->actions as $action)
                                    <tr>
                                        <td class="text-nowrap">{{ optional($action->created_at)->format('d/m/Y H:i') }}</td>
                                        <td class="fw-semibold">{{ $action->action }}</td>
                                        <td>{{ $action->actor->name ?? '—' }}</td>
                                        <td>{{ $action->notes ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="4" title="Belum ada tindak lanjut" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @unless($incident->isClosed())
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0"><i class="ri-add-circle-line me-1 text-primary"></i> Catat Tindak Lanjut</h5>
                    </div>
                    <form method="POST" action="{{ route('user.keamanan.incidents.action', ['userId' => $userId, 'incident' => $incident->id]) }}">
                        @csrf
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Tindakan <span class="text-danger">*</span></label>
                                <input type="text" name="action" class="form-control" placeholder="Contoh: Olah TKP" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ubah Status</label>
                                <select name="status" class="form-select">
                                    <option value="">— Tetap —</option>
                                    <option value="diproses">Diproses</option>
                                    <option value="investigasi">Investigasi</option>
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-save-line me-1"></i> Simpan Tindak Lanjut
                            </button>
                        </div>
                    </form>
                </div>

                @if($canManage)
                    <div class="card mt-3">
                        <div class="card-header border-bottom-dashed">
                            <h5 class="card-title mb-0"><i class="ri-check-double-line me-1 text-success"></i> Tutup Insiden</h5>
                        </div>
                        <form method="POST" action="{{ route('user.keamanan.incidents.close', ['userId' => $userId, 'incident' => $incident->id]) }}"
                              data-confirm="Tutup insiden ini? Status tidak dapat dikembalikan.">
                            @csrf
                            <div class="card-body">
                                <label class="form-label">Uraian Penyelesaian <span class="text-danger">*</span></label>
                                <textarea name="resolution" class="form-control" rows="4" required
                                          placeholder="Apa yang sudah dilakukan dan bagaimana kondisi akhirnya..."></textarea>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="ri-check-double-line me-1"></i> Tutup Insiden
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            @endunless
        </div>
    </div>
@endsection
