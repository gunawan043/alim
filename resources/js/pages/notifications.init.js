/**
 * Notifications JS - ALIM
 * Real-time notification via Pusher + polling fallback
 * Toast + Sound + Badge update
 */

(function () {
    'use strict';

    // ── Element refs ────────────────────────────────────────────
    const notifDropdown = document.getElementById('notificationDropdown');
    const badgeEl       = document.getElementById('notif-badge-count');
    const newBadgeEl    = document.getElementById('notif-new-badge');
    const allList       = document.getElementById('notif-all-list');
    const unreadList    = document.getElementById('notif-unread-list');

    // ── Audio ──────────────────────────────────────────────────
    const notifSound = new Audio();

    function loadSound(src) {
        notifSound.src = src;
        notifSound.load();
    }

    function generateBeep(frequency = 800, duration = 0.3) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = ctx.createOscillator();
            const gainNode = ctx.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(ctx.destination);
            oscillator.frequency.value = frequency;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
            oscillator.start(ctx.currentTime);
            oscillator.stop(ctx.currentTime + duration);
            setTimeout(() => ctx.close(), (duration + 0.1) * 1000);
        } catch (e) {}
    }

    function playSound(type) {
        if (notifSound.src && notifSound.readyState >= 2) {
            notifSound.currentTime = 0;
            notifSound.volume = 0.4;
            notifSound.play().catch(() => {});
            return;
        }
        const tones = { info: 800, success: 1000, warning: 600, error: 400 };
        const freq = tones[type] || 800;
        if (type === 'error') {
            generateBeep(freq, 0.2);
            setTimeout(() => generateBeep(freq, 0.2), 250);
        } else {
            generateBeep(freq, 0.25);
        }
    }

    loadSound('/sounds/notification.mp3');

    // ── Toast notification ─────────────────────────────────────
    function showToast({ title, message, type = 'info', action_url }) {
        const colors = { info: '#0ab4c8', success: '#198754', warning: '#f7b84b', error: '#f06548' };
        const icons = { info: 'bx bx-info-circle', success: 'bx bx-check-circle', warning: 'bx bx-warning', error: 'bx bx-x-circle' };

        Toastify({
            text: `
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <i class="${icons[type] || icons.info}" style="font-size:20px;color:#fff;margin-top:2px;"></i>
                    <div>
                        <div style="font-weight:600;font-size:13px;margin-bottom:3px;">${title}</div>
                        <div style="font-size:12px;opacity:0.9;line-height:1.4;">${message}</div>
                    </div>
                </div>
            `,
            duration: 6000,
            gravity: 'top',
            position: 'right',
            style: { background: colors[type] || colors.info, borderRadius: '10px', minWidth: '320px' },
            escapeMarkup: false,
            close: true,
            stopOnFocus: true,
            onClick: () => {
                if (action_url && action_url !== '#') {
                    window.location.href = action_url;
                }
            }
        }).showToast();
    }

    // ── Helpers ─────────────────────────────────────────────────
    function getApiUrl(path) {
        const uid = window.userId;
        return uid ? `/${uid}/${path}` : `/${path}`;
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function fetchJSON(url, options = {}) {
        return fetch(url, {
            ...options,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
                ...(options.headers || {}),
            },
        }).then(r => r.json());
    }

    function timeAgo(dateStr) {
        if (!dateStr) return '';
        const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 60)  return diff + ' detik lalu';
        if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
        if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
        return Math.floor(diff / 86400) + ' hari lalu';
    }

    // ── Badge ───────────────────────────────────────────────────
    function updateBadges(unreadCount) {
        const count = parseInt(unreadCount) || 0;
        if (badgeEl) {
            badgeEl.classList.toggle('d-none', count === 0);
            badgeEl.innerHTML = count + '<span class="visually-hidden">unread messages</span>';
        }
        if (newBadgeEl) {
            newBadgeEl.textContent = count;
        }
    }

    // ── Item HTML ───────────────────────────────────────────────
    function typeIcon(type) {
        return {
            info:    '<i class="bx bx-info-circle"></i>',
            success: '<i class="bx bx-badge-check"></i>',
            warning: '<i class="bx bx-message-square-dots"></i>',
            error:   '<i class="bx bx-x-circle"></i>',
        }[type] || '<i class="bx bx-bell"></i>';
    }

    function typeColor(type) {
        return {
            info:    'bg-info-subtle text-info',
            success: 'bg-success-subtle text-success',
            warning: 'bg-warning-subtle text-warning',
            error:   'bg-danger-subtle text-danger',
        }[type] || 'bg-info-subtle text-info';
    }

    function typeDotColor(type) {
        return {
            info:    'bg-info',
            success: 'bg-success',
            warning: 'bg-warning',
            error:   'bg-danger',
        }[type] || 'bg-info';
    }

    function buildItemHTML(notif) {
        const unreadClass  = notif.is_read ? '' : 'notif-unread';
        const bgColorClass = typeColor(notif.type);
        const iconHtml     = typeIcon(notif.type);
        const actionUrl   = notif.action_url || '#';
        const dotColor    = typeDotColor(notif.type);
        const priorityDot = (notif.priority === 'urgent' || notif.priority === 'high')
            ? '<span class="badge bg-danger ms-2" style="font-size:9px;">!</span>' : '';

        return `
        <div class="notif-item d-flex align-items-start px-3 py-2 ${unreadClass} ${notif.is_deleted ? 'notif-deleted' : ''}"
             data-id="${notif.id || ''}"
             data-url="${actionUrl}">
            <div class="flex-shrink-0 me-3">
                <span class="avatar-title rounded-circle fs-18 ${bgColorClass}">
                    ${iconHtml}
                </span>
            </div>
            <div class="flex-grow-1 min-w-0">
                <a href="${actionUrl}" class="stretched-link text-decoration-none">
                    <h6 class="mb-1 fs-13 fw-semibold text-dark notif-title">
                        ${priorityDot}${notif.title || 'Notifikasi baru'}
                    </h6>
                </a>
                <p class="mb-1 fs-11 text-muted notif-message"
                   style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    ${notif.message || ''}
                </p>
                <div class="d-flex align-items-center justify-content-between">
                    <small class="text-muted notif-time">
                        <i class="mdi mdi-clock-outline me-1"></i>${timeAgo(notif.created_at)}
                    </small>
                    <div class="d-flex gap-2 align-items-center">
                        ${notif.reference_code ? `<small class="badge bg-light text-muted">${notif.reference_code}</small>` : ''}
                        <div class="notif-item-actions d-none">
                            <button class="btn btn-sm btn-ghost-secondary p-0 notif-read-btn" title="Tandai dibaca">
                                <i class="bx bx-check fs-14"></i>
                            </button>
                            <button class="btn btn-sm btn-ghost-secondary p-0 notif-delete-btn" title="Hapus">
                                <i class="bx bx-trash fs-14 text-danger"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            ${!notif.is_read ? `<div class="flex-shrink-0 mt-1"><span class="badge ${dotColor} rounded-circle" style="width:8px;height:8px;display:block;"></span></div>` : ''}
        </div>`;
    }

    function renderList(container, items) {
        // remove old items
        container.querySelectorAll(':not(.notif-empty-state)').forEach(el => el.remove());
        container.querySelectorAll('.notif-item').forEach(el => el.remove());

        const emptyState = container.querySelector('.notif-empty-state');
        if (items.length === 0) {
            if (emptyState) emptyState.classList.remove('d-none');
        } else {
            if (emptyState) emptyState.classList.add('d-none');
            const fragment = document.createDocumentFragment();
            items.forEach(n => {
                const tmp = document.createElement('div');
                tmp.innerHTML = buildItemHTML(n);
                fragment.appendChild(tmp.firstElementChild);
            });
            container.appendChild(fragment);
        }
    }

    // ── Load notifications ──────────────────────────────────────
    function loadNotifications() {
        fetchJSON(getApiUrl('notifications'))
            .then(res => {
                if (!res.success) return;

                const all    = res.data?.data || [];
                const unread = res.unread_count || 0;

                updateBadges(unread);
                renderList(allList, all.slice(0, 15));

                const unreadItems = all.filter(n => !n.is_read).slice(0, 15);
                renderList(unreadList, unreadItems);

                bindItemClicks();
            })
            .catch(err => console.error('[Notif] Load error:', err));
    }

    function markAsRead(notifId, $item) {
        if (!notifId) return;
        fetchJSON(getApiUrl(`notifications/${notifId}/mark-read`), { method: 'POST' })
            .then(() => {
                if ($item) $item.classList.remove('notif-unread');
                const badge = document.getElementById('notif-badge-count');
                const current = parseInt(badge?.textContent) || 0;
                updateBadges(Math.max(0, current - 1));
            }).catch(() => {});
    }

    function deleteNotification(notifId, $item) {
        if (!notifId) return;
        fetchJSON(getApiUrl(`notifications/${notifId}`), { method: 'DELETE' })
            .then(() => {
                if ($item) {
                    $item.style.transition = 'all 0.3s';
                    $item.style.opacity = '0';
                    $item.style.transform = 'translateX(20px)';
                    setTimeout(() => $item.remove(), 300);
                }
            }).catch(() => {});
    }

    function bindItemClicks() {
        document.querySelectorAll('.notif-item').forEach(item => {
            item.addEventListener('click', function (e) {
                const $btn = e.target.closest('.notif-read-btn, .notif-delete-btn');
                if ($btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = this.dataset.id;
                    if ($btn.classList.contains('notif-read-btn')) {
                        markAsRead(id, this);
                    } else if ($btn.classList.contains('notif-delete-btn')) {
                        deleteNotification(id, this);
                    }
                    return;
                }

                const id  = this.dataset.id;
                const url = this.dataset.url || '#';

                if (id && this.classList.contains('notif-unread')) {
                    markAsRead(id, this);
                    this.classList.remove('notif-unread');
                }

                if (url && url !== '#') {
                    window.location.href = url;
                }
            });

            // show action buttons on hover
            item.addEventListener('mouseenter', () => {
                item.querySelector('.notif-item-actions')?.classList.remove('d-none');
            });
            item.addEventListener('mouseleave', () => {
                item.querySelector('.notif-item-actions')?.classList.add('d-none');
            });
        });
    }

    // ── Mark all read ───────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.notif-mark-all-btn');
        if (!btn) return;
        e.preventDefault();

        fetchJSON(getApiUrl('notifications/mark-all-read'), { method: 'POST' })
            .then(res => {
                if (res.success) {
                    document.querySelectorAll('.notif-item').forEach(el => el.classList.remove('notif-unread'));
                    updateBadges(0);
                    Toastify({
                        text: 'Semua notifikasi ditandai dibaca',
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        backgroundColor: '#198754',
                    }).showToast();
                }
            }).catch(() => {});
    });

    // ── "View all" links ────────────────────────────────────────
    document.querySelectorAll('.notif-all-link').forEach(link => {
        link.setAttribute('href', '/notifications');
    });

    // ── Tab badge update ─────────────────────────────────────────
    document.querySelectorAll('#notifTab .nav-link').forEach(tab => {
        tab.addEventListener('shown.bs.tab', () => {
            loadNotifications();
        });
    });

    // ─────────────────────────────────────────────────────────────
    //  LARAVEL ECHO — REAL-TIME (Pusher)
    // ─────────────────────────────────────────────────────────────
    function initEcho() {
        if (!window.userId) return;

        const pusherKey = document.querySelector('meta[name="pusher-key"]')?.content;
        if (!pusherKey) return;

        try {
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: pusherKey,
                cluster: document.querySelector('meta[name="pusher-cluster"]')?.content || 'ap1',
                forceTLS: true,
            });

            window.Echo.private(`user.${window.userId}`)
                .listen('.notification.received', function (payload) {
                    console.log('[Notif] Real-time event received:', payload);

                    showToast({
                        title:      payload.title,
                        message:    payload.message,
                        type:       payload.type || 'info',
                        action_url: payload.action_url,
                    });
                    playSound(payload.type);

                    const badge = document.getElementById('notif-badge-count');
                    const current = parseInt(badge?.textContent) || 0;
                    updateBadges(current + 1);

                    // Insert new item into all list
                    if (allList) {
                        const newItem = document.createElement('div');
                        newItem.innerHTML = buildItemHTML({
                            ...payload,
                            is_read: false,
                            created_at: payload.created_at || new Date().toISOString(),
                        });
                        const firstItem = allList.querySelector('.notif-item');
                        if (firstItem) {
                            allList.insertBefore(newItem.firstElementChild, firstItem);
                        } else {
                            const emptyState = allList.querySelector('.notif-empty-state');
                            if (emptyState) emptyState.classList.add('d-none');
                            allList.appendChild(newItem.firstElementChild);
                        }
                        bindItemClicks();
                    }
                })
                .listen('.Illuminate\\Notifications\\BroadcastMessageCreated', function (payload) {
                    showToast({ title: payload.title || 'Notifikasi', message: payload.body || '', type: 'info' });
                    playSound('info');
                    updateBadges((parseInt(badgeEl?.textContent) || 0) + 1);
                })
                .on('pusher:subscription_error', function (status) {
                    console.error('[Notif] Echo subscription error:', status);
                });

            console.log('[Notif] Laravel Echo initialized for user:', window.userId);
        } catch (err) {
            console.error('[Notif] Echo init failed:', err);
        }
    }

    // ── Init ───────────────────────────────────────────────────
    if (document.readyState === 'complete') {
        initEcho();
    } else {
        window.addEventListener('load', initEcho);
    }

    if (notifDropdown) {
        notifDropdown.addEventListener('shown.bs.dropdown', loadNotifications);
    }

    setInterval(loadNotifications, 30000);

})();
