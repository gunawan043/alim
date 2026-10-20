@php
    $filterFields = $filterFields ?? [];
    $filterHidden = $filterHidden ?? [];
    $filterAction = $filterAction ?? url()->current();
@endphp
<div class="modal fade" id="libraryFilterModal" tabindex="-1" aria-labelledby="libraryFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ $filterAction }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="libraryFilterModalLabel"><i class="ri-filter-3-fill me-2 text-primary"></i>Filter Lanjutan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="filter-group">
                        <div class="filter-group-title"><i class="ri-filter-line"></i>Kriteria Pencarian</div>
                        <div class="row g-3">
                            @foreach($filterFields as $field)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ $field['label'] }}</label>
                                    @if(($field['type'] ?? 'text') === 'select')
                                        <select name="{{ $field['name'] }}" class="form-select">
                                            @foreach($field['options'] ?? [] as $value => $label)
                                                <option value="{{ $value }}" @selected((string) request($field['name'], $field['value'] ?? '') === (string) $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(($field['type'] ?? 'text') === 'date')
                                        <input type="date" name="{{ $field['name'] }}" value="{{ request($field['name'], $field['value'] ?? '') }}" class="form-control">
                                    @elseif(($field['type'] ?? 'text') === 'month')
                                        <input type="month" name="{{ $field['name'] }}" value="{{ request($field['name'], $field['value'] ?? '') }}" class="form-control">
                                    @else
                                        <input type="text" name="{{ $field['name'] }}" value="{{ request($field['name'], $field['value'] ?? '') }}" class="form-control" placeholder="{{ $field['placeholder'] ?? '' }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @foreach($filterHidden as $name => $value)
                        @if($value !== null && $value !== '')<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
                    @endforeach
                </div>
                <div class="modal-footer">
                    <a href="{{ $filterAction }}" class="btn btn-light">Reset</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-filter-3-fill me-1"></i>Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>
