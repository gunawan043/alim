@extends('layouts.master')
@section('title') Detail Promosi Santri @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2')
            <a href="{{ route('user.student-promotions.index', ['userId' => $userId]) }}">Promosi Santri</a>
        @endslot
        @slot('title') Detail Promosi @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── INFO PROMOSI ─────────────────────────────────────── --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ri-arrow-up-line me-2"></i>Detail Promosi</h5>
                        <span class="badge bg-{{ $promotion->status_badge_color }}-subtle text-{{ $promotion->status_badge_color }}">
                            {{ $promotion->status_label }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Tahun Ajaran Asal</label>
                            <div class="fw-semibold">{{ $promotion->fromAcademicYear?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Tahun Ajaran Tujuan</label>
                            <div class="fw-semibold">{{ $promotion->toAcademicYear?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Tanggal Efektif</label>
                            <div class="fw-semibold">{{ $promotion->promotion_date?->format('d/m/Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Rombel Asal</label>
                            <div class="fw-semibold">{{ $promotion->fromStudyGroup?->full_name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Rombel Tujuan</label>
                            <div class="fw-semibold">{{ $promotion->toStudyGroup?->full_name ?? 'Auto-detect' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">Grade Shift</label>
                            <div class="fw-semibold">
                                @if($promotion->grade_shift > 0)
                                    Naik {{ $promotion->grade_shift }} level
                                @elseif($promotion->grade_shift < 0)
                                    Turun {{ abs($promotion->grade_shift) }} level
                                @else
                                    Tinggal kelas
                                @endif
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted small mb-1">Opsi</label>
                            <div>
                                @if($promotion->auto_enroll)
                                    <span class="badge bg-success-subtle text-success me-1">✓ Auto Enroll</span>
                                @endif
                                @if($promotion->skip_graduate)
                                    <span class="badge bg-success-subtle text-success me-1">✓ Skip Graduate</span>
                                @endif
                                @if($promotion->include_inactive)
                                    <span class="badge bg-warning-subtle text-warning me-1">✓ Siswa Non-Aktif</span>
                                @endif
                            </div>
                        </div>
                        @if($promotion->notes)
                            <div class="col-md-12">
                                <label class="text-muted small mb-1">Keterangan</label>
                                <div>{{ $promotion->notes }}</div>
                            </div>
                        @endif
                        @if($promotion->executed_at)
                            <div class="col-md-12">
                                <label class="text-muted small mb-1">Dieksekusi</label>
                                <div>
                                    {{ $promotion->executed_at->format('d/m/Y H:i') }}
                                    oleh {{ $promotion->executedBy?->name ?? '-' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        @if($promotion->status === 'draft')
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#executeModal">
                                <i class="ri-play-line me-1"></i>Eksekusi Promosi
                            </button>
                            <form method="POST"
                                  action="{{ route('user.student-promotions.cancel', ['userId' => $userId, 'id' => $promotion->id]) }}"
                                  onsubmit="return confirm('Batalkan promosi ini?')">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="ri-close-line me-1"></i>Batalkan
                                </button>
                            </form>
                            <form method="POST"
                                  action="{{ route('user.student-promotions.destroy', ['userId' => $userId, 'id' => $promotion->id]) }}"
                                  onsubmit="return confirm('Hapus promosi ini?')"
                                  class="ms-auto">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="ri-delete-bin-line me-1"></i>Hapus
                                </button>
                            </form>
                        @elseif($promotion->status === 'completed')
                            <span class="badge bg-success">
                                <i class="ri-checkbox-circle-line me-1"></i>Selesai dieksekusi
                            </span>
                        @elseif($promotion->status === 'cancelled')
                            <span class="badge bg-danger">
                                <i class="ri-close-circle-line me-1"></i>Dibatalkan
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-bar-chart-line me-1"></i>Ringkasan</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Siswa
                            <span class="badge bg-primary rounded-pill">{{ $promotion->details->count() }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-success"><i class="ri-checkbox-circle-line me-1"></i>Berhasil</span>
                            <span class="badge bg-success-subtle text-success rounded-pill">
                                {{ $promotion->details->where('status', 'success')->count() }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-danger"><i class="ri-close-circle-line me-1"></i>Gagal</span>
                            <span class="badge bg-danger-subtle text-danger rounded-pill">
                                {{ $promotion->details->where('status', 'failed')->count() }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-secondary"><i class="ri-time-line me-1"></i>Pending</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                {{ $promotion->details->where('status', 'pending')->count() }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ── DAFTAR SISWA ──────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Daftar Siswa</h5>
                @if($promotion->status === 'draft')
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="checkAllTable">
                        <label class="form-check-label" for="checkAllTable">Pilih Semua</label>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            @if($promotion->status === 'draft')
                                <th style="width:40px"></th>
                            @endif
                            <th>Nama</th>
                            <th>NISN</th>
                            <th>Aksi</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotion->details as $detail)
                            <tr>
                                @if($promotion->status === 'draft')
                                    <td class="text-center">
                                        <input class="form-check-input student-check" type="checkbox"
                                               name="student_ids[]" value="{{ $detail->student_id }}">
                                    </td>
                                @endif
                                <td class="fw-semibold">{{ $detail->student?->name ?? '-' }}</td>
                                <td><code>{{ $detail->student?->nisn ?? '-' }}</code></td>
                                <td>
                                    @if($promotion->status === 'draft')
                                        <form method="POST"
                                              action="{{ route('user.student-promotions.update-detail', [
                                                  'userId' => $userId,
                                                  'id' => $promotion->id,
                                                  'detailId' => $detail->id
                                              ]) }}"
                                              class="d-flex gap-2 align-items-center">
                                            @csrf
                                            @method('PUT')
                                            <select name="action" class="form-select form-select-sm w-auto">
                                                <option value="promote"    {{ $detail->action === 'promote'    ? 'selected' : '' }}>🚀 Naik</option>
                                                <option value="retain"     {{ $detail->action === 'retain'     ? 'selected' : '' }}>📌 Tinggal</option>
                                                <option value="graduate"   {{ $detail->action === 'graduate'   ? 'selected' : '' }}>🎓 Lulus</option>
                                                <option value="mutate_out" {{ $detail->action === 'mutate_out' ? 'selected' : '' }}>🚪 Mutasi</option>
                                                <option value="skip"       {{ $detail->action === 'skip'       ? 'selected' : '' }}>⏭ Skip</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $detail->action_label }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $detail->status_badge_color }}-subtle text-{{ $detail->status_badge_color }}">
                                        {{ $detail->status_label }}
                                    </span>
                                </td>
                                <td>
                                    @if($detail->error_message)
                                        <small class="text-danger">{{ $detail->error_message }}</small>
                                    @elseif($detail->notes)
                                        <small class="text-muted">{{ $detail->notes }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $promotion->status === 'draft' ? 6 : 5 }}"
                                    class="text-center py-4 text-muted">
                                    Tidak ada siswa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── MODAL EKSEKUSI ───────────────────────────────────── --}}
    @if($promotion->status === 'draft')
        <form method="POST"
              action="{{ route('user.student-promotions.execute', ['userId' => $userId, 'id' => $promotion->id]) }}"
              id="executeForm">
            @csrf
            <div class="modal fade" id="executeModal" tabindex="-1" aria-labelledby="executeModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning-subtle">
                            <h5 class="modal-title" id="executeModalLabel">
                                <i class="ri-alert-line me-1"></i>Konfirmasi Eksekusi Promosi
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning mb-3">
                                <strong>Perhatian!</strong> Eksekusi promosi akan mengubah data secara permanen:
                            </div>
                            <ul class="mb-0">
                                <li>History rombel siswa di tahun ajaran lama akan <strong>ditutup</strong></li>
                                <li>Siswa akan <strong>di-enroll</strong> ke rombel baru di tahun ajaran tujuan</li>
                                <li>Siswa tingkat akhir akan <strong>diluluskan</strong> ({{ $promotion->skip_graduate ? 'sesuai opsi' : 'periksa lagi' }})</li>
                                <li>Tindakan ini <strong>tidak dapat dibatalkan</strong> secara otomatis</li>
                            </ul>
                            <div class="mt-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="confirmedCheck" required>
                                    <label class="form-check-label" for="confirmedCheck">
                                        Saya yakin ingin melanjutkan eksekusi promosi
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" form="executeForm" class="btn btn-success" id="btnExecute" disabled>
                                <i class="ri-play-line me-1"></i>Eksekusi Sekarang
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
@endsection

@section('script')
<script>
document.getElementById('confirmedCheck')?.addEventListener('change', function () {
    document.getElementById('btnExecute').disabled = !this.checked;
});

document.getElementById('checkAllTable')?.addEventListener('change', function () {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = this.checked);
});
</script>
@endsection
