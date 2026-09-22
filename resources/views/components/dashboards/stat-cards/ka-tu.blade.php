<x-dashboards.stat-card label="Surat Masuk Hari Ini" :value="$suratMasuk" icon="ri-mail-open-line" color="primary" />
<x-dashboards.stat-card label="Surat Keluar Pending" :value="$suratKeluar" icon="ri-send-plane-line" color="warning" />
<x-dashboards.stat-card label="Dokumen GTK Expiring" :value="$dokumenExpiring" icon="ri-alert-line" color="danger" />
<x-dashboards.stat-card label="GTK Baru Bulan Ini" :value="$gtkBaru" icon="ri-user-add-line" color="success" />
