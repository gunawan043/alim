<script>
(function () {
    function initSearchableSelects(root) {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;

        window.jQuery(root || document).find('select[data-searchable]').addBack('select[data-searchable]').each(function () {
            const $select = window.jQuery(this);
            if ($select.data('searchable-initialized')) return;

            const $modal = $select.closest('.modal');
            $select.select2({
                width: '100%',
                placeholder: $select.data('placeholder') || 'Cari...',
                allowClear: !$select.prop('required'),
                dropdownParent: $modal.length ? $modal : undefined,
            });
            $select.data('searchable-initialized', true);
        });
    }

    window.initSearchableSelects = initSearchableSelects;

    document.addEventListener('DOMContentLoaded', function () {
        initSearchableSelects(document);
    });

    if (window.MutationObserver) {
        new MutationObserver(function (mutations) {
            let shouldInit = false;
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1 && (node.matches?.('select[data-searchable]') || node.querySelector?.('select[data-searchable]'))) {
                        shouldInit = true;
                    }
                });
            });
            if (shouldInit) initSearchableSelects(document);
        }).observe(document.body, { childList: true, subtree: true });
    }
})();
</script>
