<div class="modal fade" id="supervisorSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0"><i class="ri-team-line me-1"></i>Supervisor untuk Setiap Guru</h5>
                    <div class="small text-muted">Bagian dari pengaturan jadwal: tentukan supervisor yang akan menilai setiap guru.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small">
                    Daftar ini yang muncul saat membuat jadwal. Supervisor hanya dapat menilai guru yang sudah ditentukan.
                </div>
                @if($supervisorGtks->isEmpty())
                    <div class="alert alert-warning small">
                        <i class="ri-alert-line me-1"></i>Belum ada anggota SK untuk semester {{ $workflowSemester }}. Isi <strong>Tim &amp; SK</strong> terlebih dahulu; supervisor hanya boleh dipilih dari anggota SK.
                    </div>
                @endif

                <form method="POST" action="{{ route('waka.supervisi-pengaturan.store') }}" class="row g-2 align-items-end border rounded p-3 mb-3">
                    @csrf
                    <input type="hidden" name="semester" value="{{ $workflowSemester }}">
                    <div class="col-md-3">
                        <label class="form-label small">Tahun Ajaran</label>
                        <select name="academic_year_id" class="form-select form-select-sm" required>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ (string) $workflowYearId === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Supervisor</label>
                        <select name="supervisor_gtk_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih —</option>
                            @foreach($supervisorGtks as $gtk)
                                <option value="{{ $gtk->id }}">{{ $gtk->name }}{{ $gtk->latestEmployment?->jabatan ? ' · '.$gtk->latestEmployment->jabatan : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Guru yang Disupervisi</label>
                        <select name="supervisee_gtk_id" class="form-select form-select-sm" required>
                            <option value="">— Pilih —</option>
                            @foreach($gtks as $gtk)
                                <option value="{{ $gtk->id }}">{{ $gtk->name }}{{ $gtk->latestEmployment?->nupy ? ' · NUPY '.$gtk->latestEmployment->nupy : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Catatan</label>
                        <input name="notes" class="form-control form-control-sm" maxlength="1000" placeholder="Opsional">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-sm btn-success w-100" title="Tambahkan supervisor untuk guru" @if($supervisorGtks->isEmpty()) disabled @endif><i class="ri-add-line"></i></button>
                    </div>
                </form>

                <div class="table-responsive" style="max-height:360px;overflow:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Supervisor</th><th>Guru yang Disupervisi</th><th>Catatan</th><th class="text-center">Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                        @forelse($pengaturanSupervisor as $row)
                            <tr>
                                <td class="fw-medium">{{ $row->supervisor?->name ?? '-' }}</td>
                                <td>{{ $row->supervisee?->name ?? '-' }}</td>
                                <td class="small text-muted">{{ $row->notes ?: '—' }}</td>
                                <td class="text-center"><span class="badge {{ $row->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('waka.supervisi-pengaturan.toggle', $row->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm {{ $row->is_active ? 'btn-soft-warning' : 'btn-soft-success' }}" title="{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="{{ $row->is_active ? 'ri-pause-line' : 'ri-play-line' }}"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('waka.supervisi-pengaturan.destroy', $row->id) }}" class="d-inline" data-confirm="Hapus penugasan {{ $row->supervisor?->name }} → {{ $row->supervisee?->name }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada penugasan supervisor. Tambahkan di atas.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
