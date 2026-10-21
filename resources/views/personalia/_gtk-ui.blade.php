<style>
    /* Pola visual Personalia mengikuti gtk/index.blade.php */
    .page-content .card { transition: box-shadow .2s ease, transform .2s ease; }
    .page-content .card-animate:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(15, 23, 42, .10); }
    .page-content .table-responsive { width: 100%; overflow-x: auto; border-radius: .375rem; }
    .page-content .table-responsive > .table { margin-bottom: 0; }
    .page-content .table-responsive thead th {
        position: sticky; top: 0; z-index: 5;
        background: var(--vz-table-bg, #f8fafc);
        color: var(--vz-table-color, inherit);
        font-size: .76rem; letter-spacing: .02em; text-transform: uppercase;
        border-bottom: 2px solid var(--vz-border-color, #e2e8f0);
        white-space: nowrap;
    }
    .page-content .table-responsive tbody td { vertical-align: middle; }
    .page-content .card-header { border-bottom: 1px dashed var(--vz-border-color, #e2e8f0); }
    .page-content .filter-bar {
        background: var(--vz-tertiary-bg, #f8fafc);
        border-color: var(--vz-border-color, #e2e8f0) !important;
    }
    .page-content .filter-bar .form-label { font-size: .78rem; font-weight: 600; color: var(--vz-secondary-color, #64748b); }
    .page-content .stat-card { border-radius: .625rem; }
    [data-bs-theme="dark"] .page-content .table-responsive thead th {
        background: #1e293b !important; color: #e2e8f0 !important; border-color: #334155 !important;
    }
    [data-bs-theme="dark"] .page-content .filter-bar {
        background: #172033 !important; border-color: #2b3648 !important;
    }
    [data-bs-theme="dark"] .page-content .table-light > th,
    [data-bs-theme="dark"] .page-content .table-light > td {
        background: #1e293b !important; color: #e2e8f0 !important;
    }
</style>
