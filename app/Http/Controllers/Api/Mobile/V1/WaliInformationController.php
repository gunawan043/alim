<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\Kaldik;
use App\Models\LibraryItem;
use App\Models\LibraryProgram;
use App\Models\LibraryTitle;
use App\Models\Peraturan;
use App\Models\School;
use App\Models\WaliSantri;
use App\Services\Dashboard\DashboardCalendarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Informasi umum yang relevan untuk wali: profil sekolah, agenda,
 * peraturan, jadwal shalat, katalog perpustakaan, dan program literasi.
 */
class WaliInformationController extends Controller
{
    use ApiResponse;

    public function schoolProfile(Request $request, string $schoolId): JsonResponse
    {
        $this->assertSchoolAccess($request, $schoolId);

        $school = School::with('workUnit:id,name')->findOrFail($schoolId);

        return $this->ok([
            'id' => $school->id,
            'name' => $school->name,
            'school_code' => $school->school_code,
            'npsn' => $school->npsn,
            'nss' => $school->nss,
            'address' => $school->address,
            'phone' => $school->phone,
            'email' => $school->email,
            'website' => $school->website,
            'school_level' => $school->school_level,
            'school_status' => $school->school_status,
            'boarding_type' => $school->boarding_type,
            'accreditation' => $school->accreditation,
            'principal_name' => $school->principal_name,
            'principal_nip' => $school->principal_nip,
            'operational_hours' => $school->operational_hours,
            'logo_url' => $school->logo_path ? asset('storage/'.$school->logo_path) : null,
            'work_unit' => $school->workUnit?->name,
        ], 'Profil sekolah berhasil diambil.');
    }

    public function agendas(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);

        $records = Kaldik::active()
            ->where('category', Kaldik::CATEGORY_AGENDA)
            ->whereHas('workUnit', fn ($query) => $query->whereHas('schools', fn ($school) => $school->where('id', $schoolId)))
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->limit(100)
            ->get()
            ->map(fn (Kaldik $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'type' => $row->type,
                'type_label' => Kaldik::TYPE_OPTIONS[$row->type] ?? $row->type,
                'start_date' => $row->start_date?->toDateString(),
                'end_date' => $row->end_date?->toDateString(),
                'description' => $row->description,
                'color' => $row->color,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Agenda sekolah berhasil diambil.');
    }

    public function regulations(Request $request): JsonResponse
    {
        $records = Peraturan::with('kategori:id,nama')
            ->where('status', 'aktif')
            ->orderByDesc('tanggal_berlaku')
            ->limit(100)
            ->get()
            ->map(fn (Peraturan $row) => [
                'id' => $row->id,
                'title' => $row->judul,
                'description' => $row->deskripsi,
                'document_number' => $row->nomor_dokumen,
                'category' => $row->kategori?->nama,
                'effective_date' => $row->tanggal_berlaku?->toDateString(),
                'expired_date' => $row->tanggal_expired?->toDateString(),
                'version' => $row->versi,
                'document_url' => $row->dokumen_path ? asset('storage/'.$row->dokumen_path) : null,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Daftar peraturan berhasil diambil.');
    }

    public function prayerTimes(Request $request, DashboardCalendarService $calendar): JsonResponse
    {
        $schoolId = $this->schoolId($request);
        $school = School::find($schoolId);

        return $this->ok($calendar->prayerTimes(['school' => $school]), 'Jadwal shalat berhasil diambil.');
    }

    public function libraryBooks(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);
        $search = trim((string) $request->query('search', ''));

        $records = LibraryTitle::with('genre:id,name')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            }))
            ->withCount(['items as available_items' => fn ($query) => $query->where('status', LibraryItem::STATUS_AVAILABLE)])
            ->orderBy('title')
            ->limit(100)
            ->get()
            ->map(fn (LibraryTitle $row) => [
                'id' => $row->id,
                'title' => $row->title,
                'author' => $row->author,
                'publisher' => $row->publisher,
                'publication_year' => $row->publication_year,
                'isbn' => $row->isbn,
                'genre' => $row->genre?->name,
                'available_items' => (int) $row->available_items,
                'cover_url' => $row->cover_path ? asset('storage/'.$row->cover_path) : null,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Katalog perpustakaan berhasil diambil.');
    }

    public function libraryPrograms(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);

        $records = LibraryProgram::whereHas('unit', fn ($query) => $query->where('school_id', $schoolId))
            ->orderByDesc('event_date')
            ->limit(50)
            ->get()
            ->map(fn (LibraryProgram $row) => [
                'id' => $row->id,
                'title' => $row->title,
                'type' => $row->type,
                'category' => $row->category,
                'status' => $row->status,
                'event_date' => $row->event_date?->toDateString(),
                'description' => $row->description,
            ]);

        return $this->ok(['records' => $records, 'total' => $records->count()], 'Program perpustakaan berhasil diambil.');
    }

    private function schoolId(Request $request): string
    {
        $schoolId = $request->attributes->get('schoolContextId');

        abort_if($schoolId === null, 403, 'Konteks sekolah diperlukan. Pilih sekolah terlebih dahulu.');

        $this->assertSchoolAccess($request, (string) $schoolId);

        return (string) $schoolId;
    }

    private function assertSchoolAccess(Request $request, string $schoolId): void
    {
        $allowed = WaliSantri::where('user_id', $request->user()->id)
            ->where('school_id', $schoolId)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->exists();

        abort_unless($allowed, 403, 'Anda tidak memiliki akses ke sekolah ini.');
    }
}
