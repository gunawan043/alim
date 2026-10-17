/*
Template Name: Alim - Academic Learning & Information Management
File: Kaldik Calendar init js
*/

(function () {
    'use strict';

    var addEvent = new bootstrap.Modal(document.getElementById('event-modal'), {
        keyboard: false
    });
    var modalTitle = document.getElementById('modal-title');
    var formEvent = document.getElementById('form-event');
    var selectedEvent = null;
    var kaldikEvents = window.KALDIK_EVENTS || [];
    var currentFilterCategory = '';
    var currentFilterAY = '';

    // ── Helpers ────────────────────────────────────────────────
    function getTime(params) {
        params = new Date(params);
        if (params.getHours != null) {
            var hour = params.getHours();
            var minute = params.getMinutes() ? params.getMinutes() : 0;
            return hour + ':' + minute;
        }
        return null;
    }

    function tConvert(time) {
        if (!time) return null;
        var t = time.split(':');
        var hours = parseInt(t[0]);
        var minutes = t[1];
        var newformat = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        return hours + ':' + minutes + ' ' + newformat;
    }

    function str_dt(date) {
        if (!date || date === 'Invalid Date') return '';
        // Parse as local noon to avoid UTC midnight shifting
        var d = new Date(date);
        if (isNaN(d)) return '';
        var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
        return d.getDate() + ' ' + monthNames[d.getMonth()] + ' ' + d.getFullYear();
    }

    function formatDateYmd(date) {
        if (!date) return '';
        var d = new Date(date);
        var month = '' + (d.getMonth() + 1);
        var day = '' + d.getDate();
        var year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }

    function getCategoryClass(category) {
        return category === 'kaldik' ? 'bg-primary-subtle' : 'bg-warning-subtle';
    }

    function getCategoryLabel(category) {
        return category === 'kaldik' ? 'Kaldik' : 'Agenda Kegiatan';
    }

    // ── Upcoming Events list ─────────────────────────────────────
    function upcomingEvent(events) {
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var upcoming = events
            .filter(function (e) {
                var end = e.end ? new Date(e.end + 'T00:00:00') : new Date(e.start + 'T00:00:00');
                return end >= today;
            })
            .sort(function (o1, o2) {
                return new Date(o1.start + 'T00:00:00') - new Date(o2.start + 'T00:00:00');
            })
            .slice(0, 15);

        document.getElementById('upcoming-event-list').innerHTML = '';
        upcoming.forEach(function (element) {
            renderUpcomingItem(element);
        });

        var countEl = document.getElementById('upcoming-count');
        if (countEl) {
            countEl.textContent = upcoming.length + ' agenda';
        }
    }

    function renderUpcomingItem(element) {
        var color = element.extendedProps?.color || '#3B82F6';
        var catLabel = element.extendedProps?.categoryLabel || 'Agenda';
        var workUnit = element.extendedProps?.work_unit_name || 'Pondok';
        var startDate = element.start ? str_dt(new Date(element.start + 'T12:00:00')) : '';
        var endDate = '';
        if (element.end) {
            var endParts = element.end.split('-');
            var ed = new Date(parseInt(endParts[0]), parseInt(endParts[1]) - 1, parseInt(endParts[2]) - 1);
            endDate = str_dt(ed);
        }
        var diff = (endDate && startDate !== endDate) ? ' – ' + endDate : '';
        var html = "<div class='upcoming-card bg-white mb-2' style='border-left:3px solid " + color + "'>\
            <div class='card-body py-2 px-3'>\
                <div class='d-flex align-items-center gap-2 mb-1'>\
                    <div class='upcoming-title flex-grow-1'>" + element.title + "</div>\
                </div>\
                <div class='mb-1'>\
                    <span class='badge rounded-pill' style='background:" + color + ";color:#fff;font-size:0.65rem;padding:1px 7px'>" + catLabel + "</span>\
                </div>\
                <div class='upcoming-wunit mb-1'>" + workUnit + "</div>\
                <div class='upcoming-date' style='color:#94a3b8'>\
                    <i class='ri-calendar-line' style='font-size:0.7rem'></i> " + startDate + diff + "\
                </div>\
            </div>\
        </div>";
        document.getElementById('upcoming-event-list').innerHTML += html;
    }

    // ── Apply Filters ───────────────────────────────────────────
    function applyFilters() {
        currentFilterCategory = document.getElementById('filter-category')?.value || '';
        currentFilterAY = document.getElementById('filter-academic-year')?.value || '';
        calendar.refetchEvents();
        upcomingEvent(getFilteredEvents());
        setTimeout(colorDayCells, 100);
    }

    // Convert kaldik event to FullCalendar format with custom color
    function toFullCalendarEvent(item) {
        // Use T00:00:00 to avoid UTC midnight shifting in UTC+7 timezone
        var eventStart = item.start ? new Date(item.start + 'T00:00:00') : null;
        var eventEnd = item.end ? new Date(item.end + 'T23:59:59') : null;
        return {
            id: item.id,
            title: item.title,
            start: eventStart,
            end: eventEnd,
            allDay: item.allDay,
            extendedProps: item.extendedProps,
        };
    }

    function getFilteredEvents() {
        return kaldikEvents.filter(function (e) {
            var catMatch = !currentFilterCategory || (e.extendedProps?.category === currentFilterCategory);
            var ayMatch = !currentFilterAY || (e.extendedProps?.academic_year_id === currentFilterAY);
            return catMatch && ayMatch;
        });
    }

    // ── New Event ───────────────────────────────────────────────
    function addNewEvent(info) {
        if (!window.KALDIK_CAN_CREATE) {
            Swal.fire({ icon: 'warning', title: 'Tidak punya akses', text: 'Anda tidak memiliki izin untuk menambah kegiatan.' });
            return;
        }
        document.getElementById('form-event').reset();
        document.getElementById('btn-delete-event').setAttribute('hidden', true);
        addEvent.show();
        formEvent.classList.remove('was-validated');
        selectedEvent = null;
        modalTitle.innerText = 'Tambah Kegiatan';
        document.getElementById('btn-save-event').innerHTML = '<i class="ri-save-line"></i> Simpan';
        document.getElementById('edit-event-btn').setAttribute('data-id', 'new-event');
        document.getElementById('edit-event-btn').click();
        document.getElementById('edit-event-btn').setAttribute('hidden', true);

        // Pre-fill date from clicked date
        if (info && info.dateStr) {
            flatpickr(start_date, {
                defaultDate: info.dateStr,
                altInput: true,
                altFormat: 'j F Y',
                dateFormat: 'Y-m-d',
            });
        }

        // Clear color selection on new event
        document.querySelectorAll('input[name="event-color"]').forEach(function(el) {
            el.checked = false;
            el.closest('label')?.classList.remove('selected');
        });
    }

    // ── Color swatch click handler ────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="event-color"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="event-color"]').forEach(function(el) {
                    el.closest('label')?.classList.remove('selected');
                });
                if (radio.checked) {
                    radio.closest('label')?.classList.add('selected');
                }
            });
        });
    });

    // ── Flatpickr Init ───────────────────────────────────────────
    var start_date = document.getElementById('event-start-date');

    function flatPickrInit() {
        flatpickr(start_date, {
            enableTime: false,
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j F Y',
            onChange: function (selectedDates, dateStr, instance) {
                var dates = dateStr.split(' to ');
                if (dates.length > 1) {
                    document.getElementById('event-time').setAttribute('hidden', true);
                }
            },
        });
    }

    function flatpicekrValueClear() {
        if (start_date && start_date.flatpickr) start_date.flatpickr().clear();
    }

    // ── View Mode ────────────────────────────────────────────────
    function eventClicked() {
        var els = [
            ['event-title', 'd-none'],
            ['event-category', 'd-none'],
            ['event-type', 'd-none'],
            ['event-academic-year', 'd-none'],
            ['event-work-unit', 'd-none'],
            ['event-start-date', 'd-none'],
            ['event-description', 'd-none'],
            ['event-is-active', 'd-none'],
        ];
        els.forEach(function (pair) {
            var el = document.getElementById(pair[0]);
            if (el) el.classList.replace('d-block', 'd-none');
        });

        var tagEls = ['event-start-date-tag', 'event-timepicker1-tag', 'event-timepicker2-tag',
            'event-category-tag', 'event-workunit-tag', 'event-academic-year-tag',
            'event-description-tag', 'event-time-divider', 'event-color-row'];
        tagEls.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.classList.replace('d-none', 'd-block');
        });
    }

    function eventTyped() {
        var formEls = ['event-title', 'event-category', 'event-type', 'event-academic-year',
            'event-start-date', 'event-description', 'event-is-active'];
        formEls.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.classList.remove('d-none');
                el.classList.add('d-block');
            }
        });

        var workUnitEl = document.getElementById('event-work-unit');
        if (workUnitEl) {
            workUnitEl.classList.remove('d-none');
            workUnitEl.classList.add('d-block');
        }

        var colorRow = document.getElementById('event-color-row');
        if (colorRow) colorRow.classList.add('d-none');

        var tagEls = ['event-start-date-tag', 'event-timepicker1-tag', 'event-timepicker2-tag',
            'event-category-tag', 'event-workunit-tag', 'event-academic-year-tag',
            'event-description-tag', 'event-time-divider'];
        tagEls.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('d-none');
        });
    }

    function editEvent(data) {
        var data_id = data.getAttribute('data-id');
        if (data_id === 'new-event') {
            modalTitle.innerHTML = 'Tambah Kegiatan';
            eventTyped();
        } else if (data_id === 'edit-event') {
            data.innerHTML = 'Batal';
            data.setAttribute('data-id', 'cancel-event');
            eventTyped();
        } else {
            data.innerHTML = 'Edit';
            data.setAttribute('data-id', 'edit-event');
            eventClicked();
        }
    }

    // ── Populate modal from event ─────────────────────────────────
    function populateModal(event) {
        var props = event.extendedProps || {};
        var catClass = getCategoryClass(props.category);
        var catLabel = getCategoryLabel(props.category);

        document.getElementById('event-title').value = event.title || '';
        document.getElementById('event-description').value = props.description || '';
        document.getElementById('event-database-id').value = event.id || '';

        // Category
        var catSelect = document.getElementById('event-category');
        catSelect.value = catClass;

        // Type
        var typeSelect = document.getElementById('event-type');
        if (typeSelect) typeSelect.value = props.type || '';

        // Academic Year
        var aySelect = document.getElementById('event-academic-year');
        if (aySelect) aySelect.value = props.academic_year_id || '';

        // Work Unit
        var wuSelect = document.getElementById('event-work-unit');
        if (wuSelect) wuSelect.value = props.work_unit_id || '';

        // Active
        document.getElementById('event-is-active').checked = props.is_active !== false;

        // Color swatches
        if (props.color) {
            var colorInput = document.querySelector('input[name="event-color"][value="' + props.color + '"]');
            if (colorInput) colorInput.checked = true;
            document.querySelectorAll('input[name="event-color"]').forEach(function(el) {
                el.closest('label').classList.toggle('selected', el.value === props.color);
            });
            // View tag
            var colorTag = document.getElementById('event-color-tag');
            if (colorTag) {
                colorTag.innerHTML = '<span class="color-swatch-label" style="background:' + props.color + '"></span> ' + props.color;
                document.getElementById('event-color-row').style.display = '';
            }
        } else {
            document.querySelectorAll('input[name="event-color"]').forEach(function(el) {
                el.checked = false;
                el.closest('label').classList.remove('selected');
            });
            document.getElementById('event-color-row').style.display = 'none';
        }

        // Dates
        var sDate = event.start ? formatDateYmd(event.start) : '';
        var eDate = '';
        if (event.end) {
            var endAdj = new Date(event.end);
            endAdj.setDate(endAdj.getDate() - 1);
            eDate = formatDateYmd(endAdj);
        }
        var rangeStr = (sDate === eDate || !eDate) ? sDate : sDate + ' to ' + eDate;

        flatpickr(start_date, {
            defaultDate: rangeStr,
            altInput: true,
            altFormat: 'j F Y',
            dateFormat: 'Y-m-d',
            mode: 'range',
        });

        // View tags
        var startStr = str_dt(event.start);
        var endStr = '';
        if (event.end) {
            var endDate = new Date(event.end);
            endDate.setDate(endDate.getDate() - 1);
            endStr = str_dt(endDate);
        }
        document.getElementById('event-start-date-tag').innerHTML = (startStr === endStr ? startStr : startStr + ' – ' + endStr);

        var badge = document.getElementById('event-category-tag');
        badge.className = 'badge ' + catClass;
        badge.innerHTML = catLabel;

        document.getElementById('event-workunit-tag').innerHTML = props.work_unit_name || 'Pondok (Semua)';
        document.getElementById('event-academic-year-tag').innerHTML = props.academic_year_name || '-';
        document.getElementById('event-description-tag').innerHTML = props.description || '<em class="text-muted">Tidak ada deskripsi</em>';
    }

    // ── Show events on a specific day ─────────────────────────
    function showDayEvents(dateStr, events) {
        var monthNames = ['Januari','Februari','Maret','April','Mei','Juni',
                          'Juli','Agustus','September','Oktober','November','Desember'];
        var d = new Date(dateStr + 'T00:00:00');
        var formattedDate = d.getDate() + ' ' + monthNames[d.getMonth()] + ' ' + d.getFullYear();
        var itemsHtml = events.map(function(e) {
            var color = e.extendedProps?.color || '#3B82F6';
            var cat = e.extendedProps?.categoryLabel || 'Agenda';
            var workUnit = e.extendedProps?.work_unit_name || 'Pondok (Semua)';
            var desc = e.extendedProps?.description || '';
            return '<div class="mb-3" style="border-left:3px solid ' + color + ';padding-left:10px">\
                <div class="fw-semibold" style="font-size:0.9rem">' + e.title + '</div>\
                <div class="d-flex gap-2 mt-1">\
                    <span class="badge rounded-pill" style="background:' + color + ';color:#fff;font-size:0.65rem">' + cat + '</span>\
                    <span class="small text-muted">' + workUnit + '</span>\
                </div>\
                ' + (desc ? '<div class="small text-muted mt-1">' + desc + '</div>' : '') + '\
            </div>';
        }).join('');

        Swal.fire({
            title: formattedDate,
            html: '<div style="text-align:left;max-height:320px;overflow-y:auto">' + itemsHtml + '</div>',
            width: '420px',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: { popup: 'text-start' },
        });
    }

    // ── Color day cells based on events ────────────────────────
    function colorDayCells() {
        // Build date→color map from original kaldikEvents (stored as Y-m-d strings, no timezone issues)
        var dateColorMap = {};
        var filtered = getFilteredEvents();
        filtered.forEach(function(e) {
            var color = e.extendedProps && e.extendedProps.color;
            if (!color) return;
            var start = e.start; // Y-m-d string
            var end = e.end;     // Y-m-d string (FullCalendar already +1 day)
            // Subtract 1 from end to get true inclusive end
            var endParts = end ? end.split('-') : start.split('-');
            var endY = parseInt(endParts[0]);
            var endM = parseInt(endParts[1]) - 1;
            var endD = parseInt(endParts[2]) - 1;
            var endDate = new Date(endY, endM, endD);
            var startDate = new Date(parseInt(start.split('-')[0]), parseInt(start.split('-')[1]) - 1, parseInt(start.split('-')[2]));
            for (var d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
                var key = d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
                if (!dateColorMap[key]) dateColorMap[key] = color;
            }
        });

        document.querySelectorAll('#kaldik-calendar .fc-daygrid-day').forEach(function(cell) {
            var dateAttr = cell.getAttribute('data-date');
            if (dateAttr && dateColorMap[dateAttr]) {
                var color = dateColorMap[dateAttr];
                var r = parseInt(color.slice(1, 3), 16);
                var g = parseInt(color.slice(3, 5), 16);
                var b = parseInt(color.slice(5, 7), 16);
                var opacity = 0.30;
                cell.style.backgroundColor = 'rgba(' + r + ',' + g + ',' + b + ',' + opacity + ')';
                var dayNum = cell.querySelector('.fc-daygrid-day-number');
                if (dayNum) dayNum.style.color = color;
            } else if (dateAttr && !dateColorMap[dateAttr]) {
                cell.style.backgroundColor = '';
                var dayNum = cell.querySelector('.fc-daygrid-day-number');
                if (dayNum) dayNum.style.color = '';
            }
        });
    }
    window.colorDayCells = colorDayCells;

    // ── Calendar ────────────────────────────────────────────────
    var calendarEl = document.getElementById('kaldik-calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        timeZone: 'local',
        editable: false,
        droppable: false,
        selectable: true,
        navLinks: true,
        initialView: 'dayGridMonth',
        themeSystem: 'bootstrap',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek',
        },
        eventClick: function (info) {
            document.getElementById('edit-event-btn').removeAttribute('hidden');
            document.getElementById('edit-event-btn').setAttribute('data-id', 'edit-event');
            document.getElementById('edit-event-btn').innerHTML = 'Edit';
            document.getElementById('btn-delete-event').removeAttribute('hidden');
            document.getElementById('btn-save-event').innerHTML = '<i class="ri-save-line"></i> Perbarui';

            eventClicked();
            flatPickrInit();
            flatpicekrValueClear();
            addEvent.show();
            formEvent.reset();
            selectedEvent = info.event;

            modalTitle.innerText = info.event.title;
            populateModal(info.event);
        },
        dateClick: function (info) {
            var dateStr = info.dateStr;
            var eventsOnDay = getFilteredEvents().filter(function(e) {
                var start = new Date(e.start + 'T00:00:00');
                var end = e.end ? new Date(e.end + 'T00:00:00') : new Date(e.start + 'T00:00:00');
                var click = new Date(dateStr + 'T00:00:00');
                return click >= start && click <= end;
            });

            if (window.KALDIK_CAN_CREATE) {
                addNewEvent(info);
            } else if (eventsOnDay.length > 0) {
                showDayEvents(dateStr, eventsOnDay);
            } else {
                Swal.fire({ icon: 'info', title: 'Tidak ada kegiatan', text: 'Tidak ada agenda di tanggal ini.' });
            }
        },
        events: function (info, successCallback, failureCallback) {
            var filtered = getFilteredEvents().map(function(e) { return toFullCalendarEvent(e); });
            successCallback(filtered);
        },
        eventDidMount: function(info) {
            if (info.event.extendedProps && info.event.extendedProps.color) {
                var color = info.event.extendedProps.color;
                var titleEl = info.el.querySelector('.fc-event-title');
                if (titleEl) titleEl.style.color = color;
            }
        },
        datesSet: function() {
            colorDayCells();
        },
    });

    calendar.render();
    upcomingEvent(getFilteredEvents());
    colorDayCells();

    // ── Form Submit ──────────────────────────────────────────────
    formEvent.addEventListener('submit', function (ev) {
        ev.preventDefault();

        if (!formEvent.checkValidity()) {
            formEvent.classList.add('was-validated');
            return;
        }

        var eventId = document.getElementById('event-database-id').value;
        var title = document.getElementById('event-title').value;
        var categorySelectVal = document.getElementById('event-category').value;
        var category = categorySelectVal === 'bg-primary-subtle' ? 'kaldik' : 'agenda';
        var type = document.getElementById('event-type').value;
        var academicYearId = document.getElementById('event-academic-year').value;
        var dateRange = document.getElementById('event-start-date').value;
        var description = document.getElementById('event-description').value;
        var isActive = document.getElementById('event-is-active').checked ? 1 : 0;

        // Get selected color
        var selectedColorEl = document.querySelector('input[name="event-color"]:checked');
        var eventColor = selectedColorEl ? selectedColorEl.value : null;

        // Admin TU: force work_unit_id to their own unit, no category selection
        var workUnitId = null;
        if (window.KALDIK_IS_ADMIN_TU) {
            workUnitId = window.KALDIK_USER_WORK_UNIT_ID || null;
            category = 'agenda'; // force agenda
        } else {
            workUnitId = document.getElementById('event-work-unit').value || null;
        }

        // Parse dates
        var dates = dateRange.split(' to ');
        var startDate = dates[0] ? dates[0].trim() : '';
        var endDate = dates[1] ? dates[1].trim() : startDate;

        var payload = {
            _token: window.KALDIK_CSRF_TOKEN,
            name: title,
            category: category,
            type: type || null,
            color: eventColor,
            academic_year_id: academicYearId || null,
            work_unit_id: workUnitId,
            start_date: startDate,
            end_date: endDate || startDate,
            description: description,
            is_active: isActive,
        };

        var url, method;
        if (eventId) {
            url = window.KALDIK_UPDATE_URL_PREFIX.replace('__id__', eventId);
            method = 'PUT';
        } else {
            url = window.KALDIK_STORE_URL;
            method = 'POST';
        }

        fetch(url, {
            method: method,
            headers: { 'X-CSRF-TOKEN': window.KALDIK_CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
        .then(function (res) {
            console.log('HTTP status:', res.status, res.statusText);
            return res.json().then(function(body) {
                body._status = res.status;
                return body;
            });
        })
        .then(function (data) {
            console.log('Server response:', data);

            // Handle 403
            if (data._status === 403) {
                Swal.fire({ icon: 'error', title: 'Akses Ditolak', text: data.message || 'Anda tidak memiliki akses.' });
                return;
            }

            if (data.success !== false) {
                addEvent.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data berhasil disimpan. Halaman akan dimuat ulang.',
                    timer: 1200,
                    showConfirmButton: false,
                }).then(function () {
                    window.location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.' });
            }
        })
        .catch(function (err) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat menyimpan data.' });
        });
    });

    // ── Delete Event ─────────────────────────────────────────────
    document.getElementById('btn-delete-event').addEventListener('click', function () {
        var eventId = document.getElementById('event-database-id').value;
        if (!eventId) return;

        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: 'Kegiatan ini akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            var url = window.KALDIK_DESTROY_URL_PREFIX.replace('__id__', eventId);
            fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': window.KALDIK_CSRF_TOKEN, 'Accept': 'application/json' },
            })
            .then(function (res) { return res.json().then(function(body) {
                body._status = res.status;
                return body;
            }); })
            .then(function (data) {
                if (data._status === 403) {
                    Swal.fire({ icon: 'error', title: 'Akses Ditolak', text: data.message || 'Anda tidak memiliki akses.' });
                    return;
                }
                addEvent.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Kegiatan berhasil dihapus.',
                    timer: 1200,
                    showConfirmButton: false,
                }).then(function () {
                    window.location.reload();
                });
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat menghapus.' });
            });
        });
    });

    // ── New Event Button ──────────────────────────────────────────
    document.getElementById('btn-new-event').addEventListener('click', function () {
        flatpicekrValueClear();
        flatPickrInit();
        addNewEvent();
        document.getElementById('edit-event-btn').setAttribute('data-id', 'new-event');
        document.getElementById('edit-event-btn').click();
        document.getElementById('edit-event-btn').setAttribute('hidden', true);
    });

    // ── Filter listeners ──────────────────────────────────────────
    document.getElementById('filter-category')?.addEventListener('change', applyFilters);
    document.getElementById('filter-academic-year')?.addEventListener('change', applyFilters);

})();
