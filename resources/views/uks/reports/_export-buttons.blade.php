@php
    $exportQuery = array_merge(['userId' => $userId, 'report' => $report], request()->query());
@endphp
<div class="d-flex gap-2 flex-shrink-0">
    <a href="{{ route('user.uks.reports.export', $exportQuery) }}" class="btn btn-sm btn-soft-success">
        <i class="ri-file-excel-2-line me-1"></i>CSV
    </a>
    <a href="{{ route('user.uks.reports.pdf', $exportQuery) }}" class="btn btn-sm btn-soft-danger">
        <i class="ri-file-pdf-2-line me-1"></i>PDF
    </a>
</div>
