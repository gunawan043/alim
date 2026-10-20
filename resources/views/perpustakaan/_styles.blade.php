<style>
    /* ── Library list pages: pola visual mengikuti resources/views/gtk/index.blade.php ── */
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
        border-bottom: 2px solid #dee2e6;
        background: #f8f9fa;
    }
    .library-table td, .library-table th { vertical-align: middle; }
    .library-table { min-width: 100%; }
    .library-table .btn-group .btn { padding: .25rem .45rem; }
    .card-animate { transition: all .3s ease; }
    .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,.1); }
    .library-stats .card { transition: all .3s ease; }
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
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 30px;
        font-size: 13px;
        transition: all .2s;
        margin: 4px;
        cursor: pointer;
        color: #495057;
        text-decoration: none;
        background: transparent;
    }
    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
    .filter-badge .remove-filter { cursor: pointer; margin-left: 4px; opacity: .7; }
    .filter-badge .remove-filter:hover { opacity: 1; }
    .filter-group { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    .filter-group-title { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .filter-group-title i { color: #0a5f9e; font-size: 18px; }
    .library-index .card-header { border-bottom: 1px dashed #e2e8f0; }
    .library-index .card-title { font-size: 1rem; }
    .library-index .empty-state { padding: 3rem 1rem; text-align: center; color: #6c757d; }
    .library-index .empty-state i { font-size: 2.5rem; display: block; margin-bottom: .75rem; opacity: .45; }
    .library-modal .modal-header { border-bottom: 1px solid #e9ecef; }
    .library-modal .modal-footer { border-top: 1px solid #e9ecef; }
    @media (max-width: 767.98px) {
        .library-table { min-width: 760px; }
        .table-freeze { min-width: 760px; }
        .library-modal .modal-body { max-height: calc(100dvh - 180px); overflow-y: auto; }
        .library-index .card-header .form-control,
        .library-index .card-header .form-select { min-width: 140px; }
    }
    .library-cover { width: 38px; height: 48px; object-fit: cover; border-radius: 4px; background: #eef2f7; }
</style>
