@extends('layouts.master')

@section('title', 'Manajemen Regu')

@include('security.partials.styles')

@section('content')
    @include('security.partials.flash')

    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('li_2') Manajemen Regu @endslot
        @slot('title') Data Regu @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex flex-wrap gap-2 align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-0">Regu Keamanan</h5>
                        <p class="text-muted mb-0 small">Regu menentukan pos jaga dan shift yang muncul saat petugas memindai checkpoint.</p>
                    </div>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#teamModal">
                        <i class="ri-add-line me-1"></i> Tambah Regu
                    </button>
                </div>
                <div class="card-body">
                    @forelse($teams as $team)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex flex-wrap gap-2 align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0">{{ $team->name }}</h6>
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $team->code }}</span>
                                        @if($team->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <i class="ri-map-pin-line me-1"></i>{{ $team->post->name ?? 'Tanpa pos' }}
                                        <span class="mx-2">·</span>
                                        <i class="ri-time-line me-1"></i>{{ $team->shift->label() ?? 'Tanpa shift' }}
                                        <span class="mx-2">·</span>
                                        <i class="ri-user-star-line me-1"></i>Komandan: {{ $team->leader->name ?? '—' }}
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown">
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-team"
                                                    data-code="{{ $team->code }}" data-name="{{ $team->name }}"
                                                    data-post="{{ $team->post_id }}" data-shift="{{ $team->shift_id }}"
                                                    data-leader="{{ $team->leader_id }}" data-notes="{{ $team->notes }}"
                                                    data-active="{{ $team->is_active ? 1 : 0 }}"
                                                    data-url="{{ route('user.keamanan.teams.update', ['userId' => $userId, 'team' => $team->id]) }}">
                                                <i class="ri-pencil-line text-secondary me-2"></i> Edit Regu
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item btn-add-member"
                                                    data-team="{{ $team->id }}" data-name="{{ $team->name }}"
                                                    data-url="{{ route('user.keamanan.teams.members.store', ['userId' => $userId, 'team' => $team->id]) }}">
                                                <i class="ri-user-add-line text-primary me-2"></i> Tambah Anggota
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('user.keamanan.teams.destroy', ['userId' => $userId, 'team' => $team->id]) }}"
                                                  data-confirm="Hapus regu {{ $team->name }}?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ri-delete-bin-line me-2"></i> Hapus Regu
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            @if($team->activeMembers->isEmpty())
                                <div class="alert alert-light border mb-0 small">
                                    Belum ada anggota aktif. Tambahkan petugas agar regu bisa dipakai pada jadwal patroli.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0 align-middle">
                                        <tbody>
                                            @foreach($team->activeMembers as $member)
                                                <tr>
                                                    <td style="width:40px;">
                                                        <span class="avatar-xs">
                                                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                                                {{ strtoupper(mb_substr($member->user->name ?? '?', 0, 1)) }}
                                                            </span>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold">{{ $member->user->name ?? '—' }}</div>
                                                        <div class="small text-muted">{{ $member->user->email ?? '' }}</div>
                                                    </td>
                                                    <td style="width:140px;">
                                                        <span class="badge bg-info-subtle text-info">
                                                            {{ \App\Models\Security\SecurityTeamMember::POSITION_OPTIONS[$member->position] ?? $member->position }}
                                                        </span>
                                                    </td>
                                                    <td style="width:110px;" class="text-end">
                                                        <form method="POST"
                                                              action="{{ route('user.keamanan.teams.members.destroy', ['userId' => $userId, 'team' => $team->id, 'member' => $member->id]) }}"
                                                              data-confirm="Keluarkan {{ $member->user->name ?? 'petugas ini' }} dari regu?">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-soft-danger">
                                                                <i class="ri-user-unfollow-line"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @empty
                        <x-empty-state title="Belum ada regu"
                            message="Buat regu untuk mengelompokkan petugas berdasarkan pos dan shift.">
                            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#teamModal">
                                <i class="ri-add-line me-1"></i> Tambah Regu
                            </button>
                        </x-empty-state>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH REGU --}}
    <div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-team-line me-2 text-primary"></i> Tambah Regu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('user.keamanan.teams.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="R-01" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nama Regu <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Regu Alpha" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pos Jaga</label>
                                <select name="post_id" class="form-select">
                                    <option value="">— Tanpa pos —</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}" {{ old('post_id') === $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Shift</label>
                                <select name="shift_id" class="form-select">
                                    <option value="">— Tanpa shift —</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}" {{ old('shift_id') === $shift->id ? 'selected' : '' }}>{{ $shift->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Komandan Regu</label>
                                <select name="leader_id" class="form-select">
                                    <option value="">— Belum ditentukan —</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}" {{ old('leader_id') === $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT REGU --}}
    <div class="modal fade" id="teamEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-pencil-line me-2 text-primary"></i> Edit Regu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="teamEditForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="teamCode" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nama Regu <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="teamName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pos Jaga</label>
                                <select name="post_id" id="teamPost" class="form-select">
                                    <option value="">— Tanpa pos —</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}">{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Shift</label>
                                <select name="shift_id" id="teamShift" class="form-select">
                                    <option value="">— Tanpa shift —</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}">{{ $shift->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Komandan Regu</label>
                                <select name="leader_id" id="teamLeader" class="form-select">
                                    <option value="">— Belum ditentukan —</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" id="teamNotes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="teamActive">
                                    <label class="form-check-label" for="teamActive">Regu aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH ANGGOTA --}}
    <div class="modal fade" id="memberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-user-add-line me-2 text-primary"></i> Tambah Anggota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="memberForm">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small">Regu: <strong id="memberTeamName"></strong></p>
                        <div class="mb-3">
                            <label class="form-label">Petugas <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required>
                                <option value="">— Pilih petugas —</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Peran <span class="text-danger">*</span></label>
                                <select name="position" class="form-select" required>
                                    @foreach(\App\Models\Security\SecurityTeamMember::POSITION_OPTIONS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mulai Bertugas</label>
                                <input type="date" name="start_date" class="form-control" value="{{ today()->toDateString() }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Berakhir</label>
                                <input type="date" name="end_date" class="form-control">
                                <div class="form-text">Kosongkan bila penugasan tidak dibatasi tanggal.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Tambahkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit-team').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('teamEditForm').action = this.dataset.url;
            document.getElementById('teamCode').value = this.dataset.code || '';
            document.getElementById('teamName').value = this.dataset.name || '';
            document.getElementById('teamPost').value = this.dataset.post || '';
            document.getElementById('teamShift').value = this.dataset.shift || '';
            document.getElementById('teamLeader').value = this.dataset.leader || '';
            document.getElementById('teamNotes').value = this.dataset.notes || '';
            document.getElementById('teamActive').checked = this.dataset.active === '1';
            new bootstrap.Modal(document.getElementById('teamEditModal')).show();
        });
    });

    document.querySelectorAll('.btn-add-member').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('memberForm').action = this.dataset.url;
            document.getElementById('memberTeamName').textContent = this.dataset.name;
            new bootstrap.Modal(document.getElementById('memberModal')).show();
        });
    });
});
</script>
@endpush
