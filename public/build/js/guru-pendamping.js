/**
 * Panel tugas tambahan "Guru Pendamping".
 *
 * Mapel + kelas wajib dipilih; JP diambil dari Peta Kurikulum lewat
 * endpoint data-hours-url. JP hasil hitungan mengisi hours_per_week
 * secara read-only karena sumbernya bukan isian manual.
 */
(function () {
    'use strict';

    function init() {
        var panel = document.getElementById('guruPendampingPanel');
        if (! panel || panel.dataset.gpInit === '1') return;
        panel.dataset.gpInit = '1';

        var taskSelect = document.querySelector('select[name="nama_tugas"]');
        var hoursInput = document.querySelector('input[name="hours_per_week"]');
        var hoursBox = document.getElementById('gpHoursBox');
        var hoursUrl = panel.dataset.hoursUrl;
        var typeId = panel.dataset.typeId || '';
        var petaUrl = panel.dataset.petaUrl || '#';

        function isPendamping() {
            return taskSelect && String(taskSelect.value || '').trim().toLowerCase() === 'guru pendamping';
        }

        function picked(selector) {
            return Array.prototype.slice.call(document.querySelectorAll(selector + ':checked')).map(function (el) {
                return el.value;
            });
        }

        function setMessage(html, variant) {
            hoursBox.innerHTML = '<div class="alert alert-' + (variant || 'light') + ' border mb-0 small">' + html + '</div>';
        }

        function render(data) {
            if (! data.subjects || ! data.subjects.length) {
                setMessage('Pilih minimal satu mapel terlebih dahulu.', 'warning');
                return;
            }
            if (! data.groups || ! data.groups.length) {
                setMessage('Pilih kelas untuk melihat JP tiap mapel.');
                return;
            }

            var missing = false;
            var rows = data.groups.map(function (group) {
                var cells = data.subjects.map(function (subject) {
                    var jp = group.subject_hours ? group.subject_hours[subject.id] : null;
                    if (jp === null || jp === undefined) {
                        missing = true;
                        return '<td class="text-center text-danger">—</td>';
                    }
                    return '<td class="text-center">' + jp + '</td>';
                }).join('');

                return '<tr><td>' + group.group_name + '</td>' + cells +
                    '<td class="text-center fw-semibold">' + group.total + '</td></tr>';
            }).join('');

            var head = data.subjects.map(function (s) {
                return '<th class="text-center small">' + s.name + '</th>';
            }).join('');

            hoursBox.innerHTML =
                '<div class="table-responsive">' +
                '<table class="table table-sm table-bordered align-middle mb-2">' +
                '<thead class="table-light"><tr><th>Kelas</th>' + head +
                '<th class="text-center">JP Kelas</th></tr></thead>' +
                '<tbody>' + rows + '</tbody></table></div>' +
                '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">' +
                '<span class="text-muted small">JP mengikuti Peta Kurikulum' +
                (missing ? ' — sebagian mapel belum punya JP.' : '.') + '</span>' +
                '<span class="badge bg-primary fs-6">Total ' + data.total + ' JP</span></div>' +
                (missing ? '<div class="mt-2 small"><a href="' + petaUrl + '">Atur JP di Peta Kurikulum</a></div>' : '');

            if (hoursInput) hoursInput.value = data.total;
        }

        function refresh() {
            if (! isPendamping() || ! hoursUrl) return;

            var subjectIds = picked('.gp-subject');
            var groupIds = picked('.gp-group');

            if (! subjectIds.length) {
                setMessage('Pilih minimal satu mapel terlebih dahulu.', 'warning');
                return;
            }

            var params = ['nama_tugas=' + encodeURIComponent('Guru Pendamping')];
            if (typeId) params.push('task_type_id=' + encodeURIComponent(typeId));
            groupIds.forEach(function (id) { params.push('group_ids[]=' + encodeURIComponent(id)); });
            subjectIds.forEach(function (id) { params.push('subject_ids[]=' + encodeURIComponent(id)); });

            fetch(hoursUrl + '?' + params.join('&'), { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(render)
                .catch(function () {
                    setMessage('Gagal menghitung JP. Muat ulang halaman lalu coba lagi.', 'danger');
                });
        }

        function togglePanel() {
            var active = isPendamping();
            panel.classList.toggle('d-none', ! active);

            if (hoursInput) {
                hoursInput.readOnly = active;
                hoursInput.classList.toggle('bg-light', active);
            }

            if (active) refresh();
        }

        if (taskSelect) taskSelect.addEventListener('change', togglePanel);
        document.querySelectorAll('.gp-subject, .gp-group').forEach(function (el) {
            el.addEventListener('change', refresh);
        });
        togglePanel();
    }

    document.addEventListener('DOMContentLoaded', init);
    if (document.readyState !== 'loading') init();
})();
