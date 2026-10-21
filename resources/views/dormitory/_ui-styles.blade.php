{{-- Shared visual language for all Asrama pages.
     Mirrors the GTK index: compact stat cards, grouped filters, dense tables,
     and Bootstrap 5.3 dark-mode aware surfaces. --}}
<style>
    .asrama-page {
        --asrama-radius: .75rem;
        --asrama-border: var(--bs-border-color);
        --asrama-soft: var(--bs-tertiary-bg);
        --asrama-heading: var(--bs-emphasis-color);
    }

    .asrama-page .card {
        border: 1px solid var(--asrama-border);
        border-radius: var(--asrama-radius);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .asrama-page .card-header {
        background: var(--bs-body-bg);
        border-bottom: 1px solid var(--asrama-border);
        padding: 1rem 1.25rem;
    }

    .asrama-page .card-header.bg-light,
    .asrama-page .card-header.bg-transparent {
        background: var(--bs-body-bg) !important;
    }

    .asrama-page .card-title,
    .asrama-page h4,
    .asrama-page h5,
    .asrama-page h6 {
        color: var(--asrama-heading);
    }

    .asrama-page .card-animate {
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .asrama-page .card-animate:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .10);
    }

    .asrama-page .stat-card .avatar-title {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }

    .asrama-page .stat-card .stat-label {
        font-size: 11px;
        letter-spacing: .03em;
        text-transform: uppercase;
        font-weight: 600;
        color: var(--bs-secondary-color);
    }

    .asrama-page .stat-card .stat-value {
        font-weight: 700;
        line-height: 1.15;
        margin-bottom: 0;
    }

    .asrama-page .stat-card .stat-note {
        font-size: 11px;
        color: var(--bs-secondary-color);
    }

    .asrama-page .filter-group {
        background: var(--asrama-soft);
        border: 1px solid var(--asrama-border);
        border-radius: .65rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .asrama-page .filter-group-title {
        font-size: .92rem;
        font-weight: 650;
        color: var(--asrama-heading);
        margin-bottom: .75rem;
        display: flex;
        align-items: center;
        gap: .45rem;
    }

    .asrama-page .filter-group-title i {
        color: var(--bs-primary);
        font-size: 1.05rem;
    }

    .asrama-page .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .7rem;
        border: 1px solid var(--asrama-border);
        border-radius: 2rem;
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        font-size: .78rem;
        line-height: 1.35;
        margin: .2rem;
        transition: background .15s ease, border-color .15s ease, color .15s ease;
    }

    .asrama-page .filter-badge:hover,
    .asrama-page .filter-badge.active {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
        color: #fff;
    }

    .asrama-page .filter-badge .remove-filter {
        cursor: pointer;
        opacity: .8;
    }

    .asrama-page .table {
        --bs-table-border-color: var(--asrama-border);
        color: var(--bs-body-color);
    }

    .asrama-page .table > :not(caption) > * > * {
        border-color: var(--asrama-border);
        padding: .7rem .85rem;
    }

    .asrama-page .table > thead > tr > th {
        background: var(--bs-tertiary-bg);
        color: var(--bs-body-color);
        font-weight: 650;
        vertical-align: middle;
    }

    .asrama-page .table > tbody > tr > td {
        vertical-align: middle;
    }

    .asrama-page .table-hover > tbody > tr:hover > * {
        --bs-table-accent-bg: var(--bs-tertiary-bg);
        color: var(--bs-body-color);
    }

    .asrama-page .table-container {
        position: relative;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .asrama-page .table-freeze {
        table-layout: auto;
        min-width: max-content;
        width: 100%;
        margin-bottom: 0;
    }

    .asrama-page .table-freeze th,
    .asrama-page .table-freeze td {
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
        vertical-align: middle;
        padding: .7rem .85rem;
        word-break: break-word;
        border-color: var(--asrama-border);
    }

    .asrama-page .table-freeze th:first-child,
    .asrama-page .table-freeze td:first-child {
        position: sticky;
        left: 0;
        z-index: 5;
        min-width: 150px;
        max-width: 220px;
        background: var(--bs-body-bg);
        box-shadow: 2px 0 5px rgba(15, 23, 42, .08);
    }

    .asrama-page .table-freeze thead th {
        position: sticky;
        top: 0;
        z-index: 6;
        font-weight: 650;
        background: var(--bs-tertiary-bg) !important;
        border-bottom: 2px solid var(--asrama-border);
    }

    .asrama-page .table-freeze tbody tr {
        transition: background-color .12s ease;
    }

    .asrama-page .table-freeze tbody tr:hover > * {
        background-color: var(--bs-tertiary-bg);
    }

    .asrama-page .table-freeze .table-danger > * {
        --bs-table-bg: var(--bs-danger-bg-subtle);
        --bs-table-color: var(--bs-danger-text-emphasis);
    }

    .asrama-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .3rem .6rem;
        border-radius: 2rem;
        font-size: .72rem;
        font-weight: 650;
        white-space: nowrap;
    }

    .asrama-page .toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    .asrama-page .toolbar-actions .form-control,
    .asrama-page .toolbar-actions .form-select {
        min-height: 38px;
    }

    .asrama-page .modal-content {
        border: 1px solid var(--asrama-border);
        border-radius: .8rem;
    }

    .asrama-page .modal-header,
    .asrama-page .modal-footer {
        border-color: var(--asrama-border);
    }

    .asrama-page .form-control,
    .asrama-page .form-select {
        border-radius: .5rem;
        border-color: var(--asrama-border);
        background-color: var(--bs-body-bg);
        color: var(--bs-body-color);
    }

    .asrama-page .form-control:focus,
    .asrama-page .form-select:focus {
        border-color: rgba(var(--bs-primary-rgb), .55);
        box-shadow: 0 0 0 .18rem rgba(var(--bs-primary-rgb), .12);
    }

    .asrama-page .form-label {
        color: var(--bs-body-color);
    }

    .asrama-page .form-text,
    .asrama-page .text-muted {
        color: var(--bs-secondary-color) !important;
    }

    .asrama-page .btn-light {
        background: var(--bs-tertiary-bg);
        border-color: var(--asrama-border);
        color: var(--bs-body-color);
    }

    .asrama-page .btn-light:hover {
        background: var(--bs-secondary-bg);
        border-color: var(--asrama-border);
        color: var(--bs-body-color);
    }

    .asrama-page .table-light {
        --bs-table-bg: var(--bs-tertiary-bg);
        --bs-table-color: var(--bs-body-color);
        --bs-table-border-color: var(--asrama-border);
    }

    .asrama-page .select2-container--default .select2-selection--single {
        min-height: 38px;
        border: 1px solid var(--asrama-border);
        border-radius: .5rem;
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
    }

    .asrama-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color);
        line-height: 36px;
    }

    [data-bs-theme="dark"] .asrama-page .card,
    [data-bs-theme="dark"] .asrama-page .filter-group {
        box-shadow: none;
    }

    [data-bs-theme="dark"] .asrama-page .table-freeze th:first-child,
    [data-bs-theme="dark"] .asrama-page .table-freeze td:first-child {
        background: var(--bs-secondary-bg);
        box-shadow: 2px 0 5px rgba(0, 0, 0, .35);
    }

    [data-bs-theme="dark"] .asrama-page .table-freeze thead th {
        background: var(--bs-tertiary-bg) !important;
    }

    [data-bs-theme="dark"] .asrama-page .table-freeze tbody tr:hover > * {
        background-color: var(--bs-secondary-bg);
    }

    [data-bs-theme="dark"] .asrama-page .filter-badge {
        background: var(--bs-secondary-bg);
        border-color: var(--asrama-border);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .asrama-page .filter-badge:hover,
    [data-bs-theme="dark"] .asrama-page .filter-badge.active {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
        color: #fff;
    }

    [data-bs-theme="dark"] .asrama-page .bg-primary-subtle { background-color: var(--bs-primary-bg-subtle) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-success-subtle { background-color: var(--bs-success-bg-subtle) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-warning-subtle { background-color: var(--bs-warning-bg-subtle) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-danger-subtle { background-color: var(--bs-danger-bg-subtle) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-info-subtle { background-color: var(--bs-info-bg-subtle) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-secondary-subtle { background-color: var(--bs-secondary-bg-subtle) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-secondary-subtle { background-color: var(--bs-secondary-bg-subtle) !important; }

    [data-bs-theme="dark"] .asrama-page .step-icon {
        border-color: var(--bs-body-bg);
        box-shadow: 0 0 0 2px var(--bs-border-color);
    }

    [data-bs-theme="dark"] .asrama-page .timeline-line,
    [data-bs-theme="dark"] .asrama-page .horizontal-step::after,
    [data-bs-theme="dark"] .asrama-page .status-step::before {
        background-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .asrama-page .bg-soft-warning {
        background: var(--bs-warning-bg-subtle) !important;
    }

    [data-bs-theme="dark"] .asrama-page .bg-primary-subtle.text-primary { color: var(--bs-primary) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-success-subtle.text-success { color: var(--bs-success) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-warning-subtle.text-warning { color: var(--bs-warning) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-danger-subtle.text-danger { color: var(--bs-danger) !important; }
    [data-bs-theme="dark"] .asrama-page .bg-info-subtle.text-info { color: var(--bs-info) !important; }
</style>
