/**
 * Sidebar Scroll Init
 * Inisialisasi custom scroll untuk sidebar vertikal menu.
 */

(function () {
    "use strict";

    // selector sidebar scroll
    const sidebarScrollSelector = ".sidebar-nav";

    function initSidebarScroll() {
        const sidebar = document.querySelector(sidebarScrollSelector);
        if (!sidebar) return;

        // Jika simplebar belum ter-load, skip
        if (typeof SimpleBar === "undefined") return;

        // Destroy instance lama jika ada
        const existingInstance = sidebar.SimpleBar;
        if (existingInstance) {
            try { existingInstance.unmount(); } catch (e) {}
        }

        // Inisialisasi SimpleBar untuk sidebar
        new SimpleBar(sidebar, {
            autoHide: true,
            scrollbarMinSize: 4,
        });
    }

    // Jalankan saat DOM ready
    window.addEventListener("DOMContentLoaded", initSidebarScroll);

    // Jalankan ulang saat route berubah (Livewire/Turbolinks style)
    document.addEventListener("turbolinks:load", initSidebarScroll);
    document.addEventListener("livewire:load", initSidebarScroll);
})();