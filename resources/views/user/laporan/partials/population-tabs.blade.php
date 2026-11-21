@include('user.laporan.partials.tabs', [
    'tabs' => [
        ['route' => 'user.laporan.populasi.mutasi', 'label' => 'Mutasi santri'],
        ['route' => 'user.laporan.populasi.pertumbuhan', 'label' => 'Pertumbuhan'],
        ['route' => 'user.laporan.populasi.profil', 'label' => 'Profil & sebaran'],
        ['route' => 'user.laporan.populasi.keberlanjutan', 'label' => 'Keberlanjutan'],
        ['route' => 'user.laporan.populasi.kenaikan-kelulusan', 'label' => 'Kenaikan & kelulusan'],
    ],
])
