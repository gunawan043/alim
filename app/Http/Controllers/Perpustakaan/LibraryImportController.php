<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Exports\LibraryTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\LibraryRowsImport;
use App\Models\LibraryUnit;
use App\Services\LibraryAccess;
use App\Services\LibraryGenreImportService;
use App\Services\LibraryImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LibraryImportController extends Controller
{
    public function __construct(
        private readonly LibraryAccess $access,
        private readonly LibraryImportService $imports,
        private readonly LibraryGenreImportService $genreImports,
    ) {}

    public function membersForm(Request $request, string $userId)
    {
        return $this->form($request, $userId, 'members');
    }

    public function catalogForm(Request $request, string $userId)
    {
        return $this->form($request, $userId, 'catalog');
    }

    public function genresForm(Request $request, string $userId)
    {
        return $this->form($request, $userId, 'genres');
    }

    public function membersStore(Request $request, string $userId)
    {
        return $this->store($request, $userId, 'members');
    }

    public function catalogStore(Request $request, string $userId)
    {
        return $this->store($request, $userId, 'catalog');
    }

    public function genresStore(Request $request, string $userId)
    {
        return $this->store($request, $userId, 'genres');
    }

    public function membersTemplate(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);

        return Excel::download(
            new LibraryTemplateExport(
                ['nis', 'nisn', 'nama', 'email', 'jenis', 'nomor_anggota', 'tanggal_daftar'],
                []
            ),
            'template-import-anggota-perpustakaan-'.now()->format('Ymd').'.xlsx'
        );
    }

    public function catalogTemplate(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);

        return Excel::download(
            new LibraryTemplateExport(
                ['judul', 'penulis', 'penerbit', 'tahun', 'genre', 'jenis_koleksi', 'isbn', 'kode_inventaris', 'rak', 'kondisi', 'status', 'tanggal_perolehan', 'harga'],
                []
            ),
            'template-import-katalog-perpustakaan-'.now()->format('Ymd').'.xlsx'
        );
    }

    public function genresTemplate(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);

        return Excel::download(
            new LibraryTemplateExport(['nama', 'kode', 'deskripsi', 'status'], []),
            'template-import-genre-perpustakaan-'.now()->format('Ymd').'.xlsx'
        );
    }

    private function form(Request $request, string $userId, string $type)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        abort_if($units->isEmpty(), 404, 'Belum ada unit perpustakaan.');
        $meta = $this->meta($type);

        return view('perpustakaan.imports.index', compact('userId', 'units', 'type', 'meta'));
    }

    private function store(Request $request, string $userId, string $type)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'library_unit_id' => 'required|exists:library_units,id',
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);

        try {
            $sheets = Excel::toArray(new LibraryRowsImport, $request->file('file'));
            $rows = $sheets[0] ?? [];
            if ($rows === []) {
                return back()->with('error', 'File import kosong atau format tidak dikenali.');
            }
            $result = match ($type) {
                'members' => $this->imports->importMembers($unit, $rows),
                'genres' => $this->genreImports->import($unit, $rows),
                default => $this->imports->importCatalog($unit, $rows),
            };
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('user.perpustakaan.imports.'.$type.'.form', ['userId' => $userId])
            ->with('import_result', $result)
            ->with('import_unit', $unit->name);
    }

    private function meta(string $type): array
    {
        if ($type === 'genres') {
            return [
                'title' => 'Import Genre Buku',
                'icon' => 'ri-price-tag-3-line',
                'description' => 'Tambahkan kategori/genre secara massal. Genre baru langsung tersedia pada form katalog.',
                'action' => 'user.perpustakaan.imports.genres.store',
                'template' => 'user.perpustakaan.imports.genres.template',
                'columns' => [
                    ['nama', 'Nama genre', 'Wajib'],
                    ['kode', 'Kode genre', 'Opsional'],
                    ['deskripsi', 'Deskripsi', 'Opsional'],
                    ['status', 'Aktif / nonaktif', 'Default aktif'],
                ],
            ];
        }

        return $type === 'members' ? [
            'title' => 'Import Anggota Perpustakaan',
            'icon' => 'ri-user-add-line',
            'description' => 'Daftarkan santri dan GTK secara massal. Santri dicocokkan berdasarkan NIS/NISN; GTK berdasarkan email.',
            'action' => 'user.perpustakaan.imports.members.store',
            'template' => 'user.perpustakaan.imports.members.template',
            'columns' => [
                ['nis', 'NIS santri', 'Wajib untuk santri'],
                ['nisn', 'NISN santri', 'Alternatif NIS'],
                ['nama', 'Nama', 'Opsional untuk validasi'],
                ['email', 'Email GTK', 'Wajib untuk jenis GTK'],
                ['jenis', 'santri / gtk', 'Kosongkan untuk santri'],
                ['nomor_anggota', 'Nomor anggota', 'Opsional, otomatis jika kosong'],
                ['tanggal_daftar', 'Tanggal daftar', 'Opsional, default hari ini'],
            ],
        ] : [
            'title' => 'Import Katalog & Eksemplar',
            'icon' => 'ri-book-mark-line',
            'description' => 'Import judul dan eksemplar sekaligus. Stok bertambah hanya sebagai eksemplar katalog, bukan melalui pengadaan.',
            'action' => 'user.perpustakaan.imports.catalog.store',
            'template' => 'user.perpustakaan.imports.catalog.template',
            'columns' => [
                ['judul', 'Judul buku', 'Wajib'],
                ['penulis', 'Penulis', 'Opsional'],
                ['penerbit', 'Penerbit', 'Opsional'],
                ['tahun', 'Tahun terbit', 'Opsional'],
                ['genre', 'Genre', 'Dibuat otomatis jika belum ada'],
                ['jenis_koleksi', 'umum/fiksi/nonfiksi/pelajaran/kitab/referensi', 'Default umum'],
                ['isbn', 'ISBN', 'Opsional'],
                ['kode_inventaris', 'Kode inventaris', 'Opsional, otomatis jika kosong'],
                ['rak', 'Lokasi rak', 'Opsional'],
                ['kondisi', 'baik/rusak ringan/rusak sedang/rusak berat', 'Default baik'],
                ['status', 'tersedia/perbaikan/hilang/ditarik', 'Default tersedia'],
                ['tanggal_perolehan', 'Tanggal perolehan', 'Opsional'],
                ['harga', 'Harga', 'Opsional'],
            ],
        ];
    }
}
