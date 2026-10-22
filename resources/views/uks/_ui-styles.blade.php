@once
    <style>
        .table-container { position: relative; width: 100%; overflow-x: auto; }
        .table-freeze { table-layout: auto; min-width: max-content; margin-bottom: 0; width: 100%; }
        .table-freeze th, .table-freeze td { white-space: normal; overflow: visible; text-overflow: clip; vertical-align: middle; padding: 12px 16px; word-break: break-word; }
        .table-freeze th:first-child, .table-freeze td:first-child { position: sticky; left: 0; z-index: 100; min-width: 150px; max-width: 220px; box-shadow: 2px 0 5px rgba(0,0,0,0.08); white-space: normal; word-wrap: break-word; background: #fff; }
        .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .table-freeze tr.table-light td[colspan] { position: static; box-shadow: none; background: #f8f9fa; }
        .col-hidden { display: none !important; }
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .filter-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 30px; font-size: 13px; transition: all 0.2s; margin: 4px; cursor: pointer; background: #fff; color: #475569; text-decoration: none; }
        .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
        .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
        .filter-badge .remove-filter { cursor: pointer; margin-left: 4px; opacity: 0.7; }
        .filter-badge .remove-filter:hover { opacity: 1; }
        .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 5px 12px; }
        .column-visibility-dropdown { max-height: 400px; overflow-y: auto; padding: 12px; width: 260px; }
        .filter-group { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .filter-group-title { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .filter-group-title i { color: #0a5f9e; font-size: 18px; }
        .modal .filter-group { padding: 14px; }
        .uks-quick-filter { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .uks-table-actions .dropdown-menu { min-width: 210px; }
        .uks-empty { padding: 56px 20px; text-align: center; }

        [data-bs-theme="dark"] .table-freeze th:first-child,
        [data-bs-theme="dark"] .table-freeze td:first-child {
            background: var(--bs-secondary-bg);
            box-shadow: 2px 0 5px rgba(0,0,0,.35);
        }
        [data-bs-theme="dark"] .table-freeze tr.table-light td[colspan] {
            background: var(--bs-tertiary-bg);
        }
        [data-bs-theme="dark"] .filter-badge {
            background: var(--bs-secondary-bg);
            border-color: var(--bs-border-color);
            color: var(--bs-body-color);
        }
        [data-bs-theme="dark"] .filter-badge:hover,
        [data-bs-theme="dark"] .filter-badge.active {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
        }
        [data-bs-theme="dark"] .filter-group,
        [data-bs-theme="dark"] .uks-quick-filter {
            background: var(--bs-tertiary-bg);
            border-color: var(--bs-border-color);
        }
        [data-bs-theme="dark"] .filter-group-title {
            color: var(--bs-body-color);
        }
    </style>
@endonce
