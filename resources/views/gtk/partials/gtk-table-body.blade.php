@php $userId = $userId ?? request()->route('userId') ?? Auth::id(); @endphp
@forelse($gtkList as $gtk)
    <tr data-gtk-id="{{ $gtk->id }}">
        <td class="text-center">
            <div class="avatar-xs" style="width:2rem;height:2rem">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-5">
                    {{ strtoupper(substr($gtk->name, 0, 1)) }}
                </div>
            </div>
        </td>
        <td data-column="nama">
            <a href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $gtk->id]) }}" class="text-body fw-medium">
                {{ $gtk->name }}
            </a>
        </td>
        <td data-column="email">
            <a href="mailto:{{ $gtk->email }}">{{ $gtk->email }}</a>
        </td>
        <td data-column="jabatan">{{ $gtk->employment?->jabatan ?? '-' }}</td>
        <td data-column="satuan_kerja">
            @if($gtk->gtkWorkUnits->isNotEmpty())
                @foreach($gtk->gtkWorkUnits as $gu)
                    @php $wu = \App\Models\WorkUnit::find($gu->work_unit_id); @endphp
                    <span class="badge bg-secondary-subtle text-secondary">{{ $wu->name ?? 'N/A' }}</span>
                @endforeach
            @else - @endif
        </td>
        <td data-column="status_aktif">
            @if($gtk->is_active)
                <span class="badge bg-success-subtle text-success">Aktif</span>
            @else
                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
            @endif
        </td>
        <td>
            <div class="dropdown">
                <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown">
                    <i class="ri-more-2-fill"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $gtk->id]) }}">
                            <i class="ri-eye-fill text-info me-2"></i> Lihat Detail
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $gtk->id]) }}">
                            <i class="ri-pencil-fill text-primary me-2"></i> Edit
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item text-danger delete-btn" data-id="{{ $gtk->id }}" data-name="{{ $gtk->name }}">
                            <i class="ri-delete-bin-line text-danger me-2"></i> Hapus
                        </button>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center py-4">
            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                colors="primary:#121331,secondary:#08a88a" style="width:60px;height:60px"></lord-icon>
            <h5 class="mt-2">Data tidak ditemukan</h5>
            <p class="text-muted">Coba ubah filter pencarian</p>
        </td>
    </tr>
@endforelse
