<div class="mb-3">
    <label class="form-label">{{ $label }} <span class="text-danger">*</span></label>
    <select
        name="{{ $selectName }}"
        id="{{ $selectId }}"
        class="form-select @if(isset($errorName)) @error($errorName) is-invalid @enderror @endif"
        required
    >
        <option value="">-- Pilih --</option>
        @foreach($groupedStudents as $sgName => $students)
            @foreach($students as $s)
                <option
                    value="{{ $s->id }}"
                    {{ isset($selectedId) && old($selectName, $selectedId) == $s->id ? 'selected' : '' }}
                >
                    {{ $s->name }} - {{ $sgName }}
                </option>
            @endforeach
        @endforeach
    </select>
    @if(isset($errorName))
        @error($errorName)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    @endif
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#{{ $selectId }}').select2({
        placeholder: '-- Pilih --',
        allowClear: false,
        width: 'resolve',
        language: {
            noResults: function () {
                return 'Nama Santi tidak ditemukan';
            }
        }
    });
});
</script>
@endpush
