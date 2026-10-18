<style>
    .tahfidz-stat .avatar-title { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; }
    .tahfidz-stat .stat-label { font-size: 11px; }
    .tahfidz-stat .card-animate { transition: transform .2s ease, box-shadow .2s ease; }
    .tahfidz-stat .card-animate:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(31,42,68,.08); }
    .tahfidz-table td, .tahfidz-table th { vertical-align: middle; }
    .tahfidz-table { min-width: 100%; }
    .table-responsive { -webkit-overflow-scrolling: touch; }
    .select2-container { width: 100% !important; }
    @media (max-width: 767.98px) {
        .tahfidz-stat .card-body { padding: 12px; }
        .tahfidz-table { min-width: 720px; }
        .card-header { gap: .5rem; }
        .card-header .btn { white-space: nowrap; }
        .modal-dialog-scrollable { margin: .5rem; max-height: calc(100vh - 1rem); max-height: calc(100dvh - 1rem); }
        .modal-dialog-scrollable .modal-content { max-height: calc(100vh - 1rem); max-height: calc(100dvh - 1rem); }
        .modal-dialog-scrollable .modal-body { overflow-y: auto !important; -webkit-overflow-scrolling: touch; }
        .modal .modal-body { overscroll-behavior: contain; }
        .modal-footer { flex-wrap: wrap; gap: .5rem; }
        .modal-footer .btn { flex: 1 1 auto; }
        .row.g-2 > [class*="col-"] > .btn.w-100 { min-height: 38px; }
        .tahfidz-filter-actions { width: 100%; }
    }
    @media (max-width: 575.98px) {
        .tahfidz-page-actions { width: 100%; }
        .tahfidz-page-actions .btn { flex: 1 1 auto; }
        .modal-dialog { margin: .35rem; }
    }
</style>
