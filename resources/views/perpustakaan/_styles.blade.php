<style>
    /* ── Library list pages: mengikuti pola visual gtk/index.blade.php ── */
    .table-container {
        position: relative;
        width: 100%;
        overflow-x: auto;
        border-radius: 0 0 .375rem .375rem;
    }
    .table-freeze {
        table-layout: auto;
        min-width: max-content;
        width: 100%;
        margin-bottom: 0;
    }
    .table-freeze th,
    .table-freeze td {
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
        vertical-align: middle;
        padding: 12px 16px;
        word-break: break-word;
    }
    .table-freeze thead th {
        position: sticky;
        top: 0;
        z-index: 20;
        font-weight: 600;
        border-bottom: 2px solid var(--vz-border-color, #dee2e6);
        background: var(--vz-table-bg, #f8f9fa);
        color: var(--vz-table-color, inherit);
    }
    .library-table td, .library-table th { vertical-align: middle; }
    .library-table { min-width: 100%; }
    .library-table .btn-group .btn { padding: .25rem .45rem; }
    .card-animate { transition: all .3s ease; }
    .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,.1); }
    .library-stats .card { transition: all .3s ease; background: var(--vz-card-bg, #fff); }
    .library-stats .card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,.1); }
    .library-stats .card-body > .d-flex { margin-bottom: .75rem; }
    .library-stats .card-body .text-muted.small,
    .library-stats .card-body .text-muted.fs-6 {
        text-transform: uppercase;
        font-size: 11px !important;
        font-weight: 500;
        letter-spacing: .02em;
    }
    .library-stats .card-body .fs-4 { font-size: 1.55rem; }
    .library-stats .avatar-title i { font-size: 1.2rem; }
    .library-stats .card-body { min-height: 92px; }
    .library-filter-panel {
        padding: .9rem 1rem;
        border-top: 1px solid var(--vz-border-color, #e9ecef);
        background: var(--vz-tertiary-bg, #f8f9fa);
    }
    .library-filter-panel .form-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--vz-secondary-color, #6c757d);
        margin-bottom: .3rem;
    }
    .library-filter-panel .form-control,
    .library-filter-panel .form-select { min-height: 38px; }
    .library-filter-panel .filter-search { min-width: 240px; }
    .library-quick-filter-bar {
        background: var(--vz-tertiary-bg, #f8f9fa) !important;
        border-color: var(--vz-border-color, #e9ecef) !important;
    }
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid var(--vz-border-color, #e2e8f0);
        border-radius: 30px;
        font-size: 13px;
        transition: all .2s;
        margin: 3px;
        cursor: pointer;
        color: var(--vz-body-color, #495057);
        text-decoration: none;
        background: var(--vz-card-bg, transparent);
    }
    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
    .filter-badge .remove-filter { cursor: pointer; margin-left: 4px; opacity: .7; }
    .filter-badge .remove-filter:hover { opacity: 1; }
    .filter-group {
        background: var(--vz-tertiary-bg, #f8fafc);
        border: 1px solid var(--vz-border-color, transparent);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
    }
    .filter-group-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--vz-heading-color, #1e293b);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filter-group-title i { color: #0a5f9e; font-size: 18px; }
    .library-index .card-header { border-bottom: 1px dashed var(--vz-border-color, #e2e8f0); }
    .library-index .card-title { font-size: 1rem; }
    .library-index .empty-state { padding: 3rem 1rem; text-align: center; color: var(--vz-secondary-color, #6c757d); }
    .library-index .empty-state i { font-size: 2.5rem; display: block; margin-bottom: .75rem; opacity: .45; }
    .library-modal .modal-header { border-bottom: 1px solid var(--vz-border-color, #e9ecef); }
    .library-modal .modal-footer { border-top: 1px solid var(--vz-border-color, #e9ecef); }
    .library-cover { width: 38px; height: 48px; object-fit: cover; border-radius: 4px; background: var(--vz-secondary-bg, #eef2f7); }
    @media (max-width: 767.98px) {
        .library-table { min-width: 760px; }
        .table-freeze { min-width: 760px; }
        .library-modal .modal-body { max-height: calc(100dvh - 180px); overflow-y: auto; }
        .library-filter-panel .filter-search { min-width: 100%; }
    }
    /* Penyesuaian mode gelap: jangan memakai warna terang hardcoded */
    [data-bs-theme="dark"] .filter-badge {
        background: var(--vz-secondary-bg, #1e293b);
        border-color: var(--vz-border-color, #334155);
        color: var(--vz-body-color, #cbd5e1);
    }
    [data-bs-theme="dark"] .filter-badge:hover,
    [data-bs-theme="dark"] .filter-badge.active {
        background: #2563eb;
        border-color: #3b82f6;
        color: #fff;
    }
    [data-bs-theme="dark"] .filter-group,
    [data-bs-theme="dark"] .library-filter-panel,
    [data-bs-theme="dark"] .library-quick-filter-bar {
        background: var(--vz-tertiary-bg, #172033) !important;
        border-color: var(--vz-border-color, #2b3648) !important;
    }
    [data-bs-theme="dark"] .filter-group-title { color: var(--vz-heading-color, #e2e8f0); }
    [data-bs-theme="dark"] .table-freeze thead th {
        background: var(--vz-table-bg, #1e293b);
        color: var(--vz-table-color, #e2e8f0);
    }
    [data-bs-theme="dark"] .badge.bg-light.text-dark {
        background: var(--vz-secondary-bg, #1e293b) !important;
        color: var(--vz-body-color, #e2e8f0) !important;
    }
    [data-bs-theme="dark"] .card,
    [data-bs-theme="dark"] .modal-content,
    [data-bs-theme="dark"] .list-group-item {
        background-color: var(--vz-card-bg, #1a2233);
        color: var(--vz-body-color, #dbe4f0);
    }

    [data-bs-theme="dark"] .table-freeze thead th,
    [data-bs-theme="dark"] .table-light > th,
    [data-bs-theme="dark"] .table-light > td {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }
    [data-bs-theme="dark"] .bg-light {
        background-color: var(--vz-tertiary-bg, #172033) !important;
        color: var(--vz-body-color, #dbe4f0) !important;
    }
    [data-bs-theme="dark"] .library-index .card-header,
    [data-bs-theme="dark"] .library-filter-panel,
    [data-bs-theme="dark"] .library-quick-filter-bar {
        color: var(--vz-body-color, #dbe4f0);
    }
    [data-bs-theme="dark"] .library-filter-panel .form-label { color: var(--vz-secondary-color, #94a3b8); }
    [data-bs-theme="dark"] .library-cover { background: var(--vz-secondary-bg, #1e293b); }
</style>
