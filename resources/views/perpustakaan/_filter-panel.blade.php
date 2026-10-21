@php
    $filterAction = $filterAction ?? url()->current();
    $filterHidden = $filterHidden ?? [];
    $filterControls = $filterControls ?? [];
    $filterShowModal = $filterShowModal ?? true;
    $filterModalId = $filterModalId ?? 'libraryFilterModal';
    $filterResetUrl = $filterResetUrl ?? $filterAction;
@endphp
<div class="library-filter-panel">
    <form method="GET" action="{{ $filterAction }}" class="d-flex flex-wrap align-items-end gap-2">
        @foreach($filterHidden as $name => $value)
            @if($value !== null && $value !== '')<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
        @endforeach
        @foreach($filterControls as $control)
            @php
                $type = $control['type'] ?? 'text';
                $name = $control['name'];
                $value = $control['value'] ?? request($name);
                $options = $control['options'] ?? [];
            @endphp
            <div class="{{ $type === 'text' ? 'flex-grow-1' : '' }}" style="{{ $type === 'text' ? 'min-width:240px;' : 'min-width:155px;' }}">
                <label class="form-label">{{ $control['label'] }}</label>
                @if($type === 'select')
                    <select name="{{ $name }}" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($options as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                @elseif($type === 'date')
                    <input type="date" name="{{ $name }}" value="{{ $value }}" class="form-control form-control-sm">
                @elseif($type === 'month')
                    <input type="month" name="{{ $name }}" value="{{ $value }}" class="form-control form-control-sm">
                @else
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                        <input type="text" name="{{ $name }}" value="{{ $value }}" class="form-control" placeholder="{{ $control['placeholder'] ?? 'Cari...' }}">
                    </div>
                @endif
            </div>
        @endforeach
        <div class="d-flex flex-wrap gap-2 ms-lg-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
            <a href="{{ $filterResetUrl }}" class="btn btn-light btn-sm"><i class="ri-refresh-line me-1"></i>Reset</a>
            @if($filterShowModal)
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $filterModalId }}"><i class="bx bx-filter-alt me-1"></i>Filter Lanjutan</button>
            @endif
        </div>
    </form>
</div>
