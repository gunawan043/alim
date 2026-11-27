/**
 * Panel Guru Pendamping pada halaman Tugas Tambahan Guru.
 *
 * Alur: pilih mapel -> pilih kelas untuk mapel itu -> centang kelas yang
 * didampingi. Yang dikirim ke server adalah pasangan mapel-kelas
 * (pendamping[mapel_id][] = kelas_id), sehingga satu guru boleh
 * mendampingi matematika di 7A tetapi IPA di 7B.
 *
 * Tidak ada isian JP manual: JP dihitung server dari Peta Kurikulum.
 */
(function () {
    'use strict';

    function init() {
        var panel = document.getElementById('pendampingPanel');
        var taskSelect = document.getElementById('taskType');
        if (!panel || !taskSelect) {
            return;
        }
        if (panel.dataset.pendampingInit === '1') {
            return;
        }
        panel.dataset.pendampingInit = '1';

        var panelHoursField = document.getElementById('hoursField');
        var hoursInput = document.getElementById('weeklyHours');
        var search = document.getElementById('pendampingSearch');
        var emptyMsg = document.getElementById('pendampingEmpty');
        var classesBox = document.getElementById('pendampingClasses');
        var summary = document.getElementById('pendampingSummary');

        // Mapel terpilih. Setiap mapel punya sendiri daftar kelasnya.
        var selection = {};   // subjectId -> { label, groups: { groupId: name } }
        var activeSubject = null;

        function selectedOption() {
            return taskSelect.options[taskSelect.selectedIndex] || null;
        }

        function isPendamping() {
            var opt = selectedOption();
            return !!opt && opt.getAttribute('data-pendamping') === '1';
        }

        function groupOptions() {
            var select = document.getElementById('rombelSelect');
            if (!select) {
                return [];
            }
            return Array.prototype.map.call(select.options, function (o) {
                return { id: o.value, name: o.textContent.trim() };
            }).filter(function (o) { return o.id !== ''; });
        }

        function renderClasses() {
            classesBox.innerHTML = '';

            if (!activeSubject || !selection[activeSubject]) {
                classesBox.innerHTML = '<div class="text-muted small">Centang salah satu mapel di atas untuk memilih kelasnya.</div>';
                return;
            }

            var options = groupOptions();
            if (options.length === 0) {
                classesBox.innerHTML = '<div class="alert alert-light border mb-0 small">Belum ada kelas aktif pada tahun ajaran ini.</div>';
                return;
            }

            var head = document.createElement('div');
            head.className = 'fw-semibold small mb-2';
            head.textContent = 'Kelas untuk ' + selection[activeSubject].label;
            classesBox.appendChild(head);

            var row = document.createElement('div');
            row.className = 'row g-1';

            options.forEach(function (group) {
                var col = document.createElement('div');
                col.className = 'col-md-3 col-6';

                var wrap = document.createElement('label');
                wrap.className = 'form-check mb-0';

                var input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'form-check-input pendamping-pair';
                input.name = 'pendamping[' + activeSubject + '][]';
                input.value = group.id;
                input.checked = !!selection[activeSubject].groups[group.id];

                var text = document.createElement('span');
                text.className = 'form-check-label small';
                text.textContent = group.name;

                wrap.appendChild(input);
                wrap.appendChild(text);
                col.appendChild(wrap);
                row.appendChild(col);
            });

            classesBox.appendChild(row);
        }

        function refreshSummary() {
            if (!summary) {
                return;
            }
            var subjects = Object.keys(selection).filter(function (id) {
                return Object.keys(selection[id].groups).length > 0;
            });

            if (subjects.length === 0) {
                summary.textContent = 'Belum ada pilihan';
                return;
            }

            var pairs = 0;
            subjects.forEach(function (id) {
                pairs += Object.keys(selection[id].groups).length;
            });

            summary.textContent = subjects.length + ' mapel · ' + pairs + ' kelas';
        }

        function markSubjectCheckboxes() {
            panel.querySelectorAll('.pendamping-subject').forEach(function (cb) {
                var hit = !!selection[cb.value];
                cb.checked = hit;
                if (hit) {
                    cb.dataset.label = selection[cb.value].label;
                }
            });
        }

        function applyPanel() {
            var active = isPendamping();

            panel.classList.remove('d-none');
            panel.classList.toggle('d-none', !active);

            if (panelHoursField && hoursInput) {
                panelHoursField.classList.toggle('d-none', active);
                hoursInput.required = !active;
                if (active) {
                    hoursInput.value = '';
                }
            }

            if (active) {
                renderClasses();
                refreshSummary();
            }
        }

        // Cari nama mapel di daftar.
        if (search) {
            search.addEventListener('input', function () {
                var term = search.value.trim().toLowerCase();
                var shown = 0;

                panel.querySelectorAll('.pendamping-item').forEach(function (item) {
                    var hit = term === '' || (item.dataset.name || '').indexOf(term) !== -1;
                    item.classList.toggle('d-none', !hit);
                    if (hit) {
                        shown++;
                    }
                });

                panel.querySelectorAll('.pendamping-group').forEach(function (group) {
                    var any = group.querySelector('.pendamping-item:not(.d-none)');
                    group.classList.toggle('d-none', !any);
                });

                if (emptyMsg) {
                    emptyMsg.classList.toggle('d-none', shown > 0);
                }
            });
        }

        // Centang mapel: klik pertama memilih mapel & menampilkan kelasnya,
        // klik ulang membatalkan mapel beserta kelasnya.
        panel.querySelectorAll('.pendamping-subject').forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (cb.checked) {
                    if (!selection[cb.value]) {
                        selection[cb.value] = { label: cb.dataset.label || '', groups: {} };
                    }
                    activeSubject = cb.value;
                } else if (selection[cb.value]) {
                    delete selection[cb.value];
                    if (activeSubject === cb.value) {
                        var remaining = Object.keys(selection);
                        activeSubject = remaining.length ? remaining[0] : null;
                    }
                }

                markSubjectCheckboxes();
                renderClasses();
                refreshSummary();
            });
        });

        // Centang kelas untuk mapel yang sedang dibuka.
        classesBox.addEventListener('change', function (event) {
            var input = event.target;
            if (!input.classList.contains('pendamping-pair') || !activeSubject) {
                return;
            }

            var label = input.parentNode.querySelector('.form-check-label');
            if (input.checked) {
                selection[activeSubject].groups[input.value] = label ? label.textContent.trim() : input.value;
            } else {
                delete selection[activeSubject].groups[input.value];
            }

            refreshSummary();
        });

        // Klik nama mapel mengaktifkan mapel itu agar kelasnya bisa dipilih.
        panel.querySelectorAll('.pendamping-item').forEach(function (item) {
            item.addEventListener('click', function (event) {
                var cb = item.querySelector('.pendamping-subject');
                if (event.target === cb) {
                    return;
                }
                if (selection[cb.value]) {
                    activeSubject = cb.value;
                    renderClasses();
                }
            });
        });

        function onTaskChanged() {
            applyPanel();
        }

        // Dropdown memakai select2, yang tidak memicu event 'change' native.
        // select2 v4 juga tidak memancarkan 'select2:select' untuk perubahan
        // yang dilakukan lewat API (.val().trigger('change')), jadi nilai
        // dipantau langsung: panel selalu mengikuti isi dropdown yang benar.
        var lastType = taskSelect.value;
        function watchTask() {
            if (taskSelect.value !== lastType) {
                lastType = taskSelect.value;
                onTaskChanged();
            }
            setTimeout(watchTask, 150);
        }

        taskSelect.addEventListener('change', onTaskChanged);

        applyPanel();
        watchTask();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
