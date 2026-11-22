{{--
    Penanda cakupan unit untuk halaman yang tidak memakai kop periode.
    Parameter: $scope (ReportScope)
--}}
@if (! empty($scope))
    <div class="lap-scope-line">
        <span class="lap-scope">
            <i class="ri-focus-3-line"></i>
            {{ $scope->label }}
        </span>
        @if ($scope->isDormitory())
            <span class="lap-scope-hint">Data dibatasi pada asrama ini.</span>
        @elseif ($scope->isSchool())
            <span class="lap-scope-hint">Data dibatasi pada satuan pendidikan ini.</span>
        @elseif ($scope->isEmpty())
            <span class="lap-scope-hint">{{ $scope->reason }}</span>
        @endif
    </div>
@endif
