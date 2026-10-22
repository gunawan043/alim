<style>
    /* Penyesuaian lintas halaman untuk mode gelap. */
    [data-bs-theme="dark"] .bg-white {
        background-color: var(--bs-body-bg) !important;
    }

    [data-bs-theme="dark"] .bg-light {
        background-color: var(--bs-tertiary-bg) !important;
        color: var(--bs-body-color) !important;
    }

    [data-bs-theme="dark"] .text-dark {
        color: var(--bs-body-color) !important;
    }

    [data-bs-theme="dark"] .border-light {
        border-color: var(--bs-border-color) !important;
    }

    [data-bs-theme="dark"] .table-light {
        --bs-table-bg: var(--bs-tertiary-bg);
        --bs-table-color: var(--bs-body-color);
        --bs-table-border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .table > :not(caption) > * > * {
        border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .modal-content {
        background-color: var(--bs-modal-bg);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .modal-header,
    [data-bs-theme="dark"] .modal-footer {
        border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select {
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .form-control::placeholder {
        color: var(--bs-secondary-color);
    }

    [data-bs-theme="dark"] .select2-container--default .select2-selection--single,
    [data-bs-theme="dark"] .select2-container--default .select2-selection--multiple {
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .select2-dropdown {
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: var(--bs-primary);
        color: #fff;
    }

    [data-bs-theme="dark"] .swal2-popup {
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .swal2-title,
    [data-bs-theme="dark"] .swal2-html-container {
        color: var(--bs-body-color);
    }
</style>
