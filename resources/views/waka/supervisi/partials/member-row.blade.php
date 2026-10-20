@php
    $rowIndex = $index;
    $selectedGtk = $member?->gtk_profile_id;
    $selectedRole = $member?->peran ?? 'guru';
@endphp
<tr class="member-row">
    <td class="text-center row-number"></td>
    <td>
        <select name="members[{{ $rowIndex }}][gtk_profile_id]" class="form-select form-select-sm member-gtk" required>
            <option value="">— Pilih GTK —</option>
            @foreach($gtks as $gtk)
                <option value="{{ $gtk->id }}"
                        data-jabatan="{{ $gtk->latestEmployment?->jabatan ?? '' }}"
                        {{ (string) $selectedGtk === (string) $gtk->id ? 'selected' : '' }}>
                    {{ $gtk->name }}{{ $gtk->latestEmployment?->nupy ? ' · NUPY '.$gtk->latestEmployment->nupy : '' }}
                </option>
            @endforeach
        </select>
    </td>
    <td class="small text-muted member-position">{{ $member?->gtk?->latestEmployment?->jabatan ?? '—' }}</td>
    <td>
        <select name="members[{{ $rowIndex }}][peran]" class="form-select form-select-sm member-role" required>
            <option value="guru" {{ $selectedRole === 'guru' ? 'selected' : '' }}>Perwakilan Guru</option>
            <option value="wksp" {{ $selectedRole === 'wksp' ? 'selected' : '' }}>WKSP</option>
            <option value="ksp" {{ $selectedRole === 'ksp' ? 'selected' : '' }}>KSP</option>
        </select>
    </td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-soft-danger remove-member-row" title="Hapus baris"><i class="ri-delete-bin-line"></i></button>
    </td>
</tr>
