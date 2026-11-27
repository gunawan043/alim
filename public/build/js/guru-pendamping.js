/**
 * Panel tugas tambahan "Guru Pendamping".
 *
 * Pengguna hanya memilih MAPEL dan KELAS. JP tidak ditampilkan maupun
 * diketik: nilainya dihitung di server dari Peta Kurikulum saat disimpan.
 *
 * Dropdown nama tugas memakai select2, yang tidak memicu event 'change'
 * native — karena itu pemilihan juga ditangkap lewat select2:select.
 */
(function () {
    'use strict';

    function init() {
        var panel = document.getElementById('guruPendampingPanel');
        if (! panel || panel.dataset.gpInit === '1') return;
        panel.dataset.gpInit = '1';

        var taskSelect = document.querySelector('select[name="nama_tugas"]');
        var manualHours = document.getElementById('manualHoursField');
        var subjectSearch = document.getElementById('gpSubjectSearch');
        var subjectEmpty = document.getElementById('gpSubjectEmpty');

        function isPendamping() {
            return taskSelect && String(taskSelect.value || '').trim().toLowerCase() === 'guru pendamping';
        }

        function syncManualHours() {
            // Untuk Guru Pendamping JP dihitung dari Peta Kurikulum, jadi
            // isian JP manual tidak perlu ditampilkan sama sekali.
            if (manualHours) manualHours.classList.toggle('d-none', isPendamping());
        }

        function togglePanel() {
            var active = isPendamping();
            panel.classList.toggle('d-none', ! active);
            syncManualHours();
        }

        function filterSubjects() {
            if (! subjectSearch) return;

            var query = subjectSearch.value.trim().toLowerCase();
            var items = document.querySelectorAll('.gp-subject-item');
            var shown = 0;

            items.forEach(function (item) {
                var match = query === '' || (item.dataset.name || '').indexOf(query) !== -1;
                item.classList.toggle('d-none', ! match);
                if (match) shown++;
            });

            // Judul pengelompokan sekolah ikut disembunyikan bila kosong.
            document.querySelectorAll('.gp-subject-group').forEach(function (group) {
                var visible = group.querySelectorAll('.gp-subject-item:not(.d-none)').length;
                group.classList.toggle('d-none', visible === 0);
            });

            if (subjectEmpty) subjectEmpty.classList.toggle('d-none', shown !== 0 || items.length === 0);
        }

        // Select2 memancarkan event jQuery; tanpa ini panel tidak terbuka
        // walau "Guru Pendamping" sudah dipilih.
        if (window.jQuery && taskSelect) {
            window.jQuery(taskSelect).on('select2:select select2:unselect change', togglePanel);
        }

        if (taskSelect) {
            taskSelect.addEventListener('change', togglePanel);
            taskSelect.addEventListener('input', togglePanel);
        }

        if (subjectSearch) {
            subjectSearch.addEventListener('input', filterSubjects);
            subjectSearch.addEventListener('search', filterSubjects);
        }

        togglePanel();
    }

    document.addEventListener('DOMContentLoaded', init);
    if (document.readyState !== 'loading') init();
})();
