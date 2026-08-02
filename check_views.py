import re, os

controllers = [
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/CutiController.php',
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/PayrollController.php',
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/KontrakController.php',
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/KinerjaController.php',
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/PelatihanController.php',
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/KesejahteraanController.php',
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/PeraturanController.php',
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/JamKerjaController.php',
    '/Users/gunawan_wawan43/Movies/Alim/alim/app/Http/Controllers/Personalia/AbsensiGtkController.php',
]

base = '/Users/gunawan_wawan43/Movies/Alim/alim/resources/views/'

for ctrl in controllers:
    views = re.findall(r"return view\(['\"]([^'\"]+)['\"]", open(ctrl).read())
    for v in views:
        full = os.path.join(base, v + '.blade.php')
        exists = 'OK' if os.path.exists(full) else 'MISSING'
        print(f'[{exists:8s}] {v}')