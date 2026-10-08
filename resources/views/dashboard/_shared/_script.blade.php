<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ApexCharts global config (dipakai widget chart)
        if (window.ApexCharts) {
            window.Apex = {
                chart: { fontFamily: 'inherit', toolbar: { show: false } },
                colors: ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                grid: { borderColor: '#f1f1f1' },
            };
        }

        console.log('[Dashboard] Loaded jabatan:', document.querySelector('.dashboard-role')?.dataset.jabatan);
    });
</script>
