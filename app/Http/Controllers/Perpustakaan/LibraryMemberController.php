<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\LibraryMember;
use App\Models\LibraryMemberCard;
use App\Models\LibrarySetting;
use App\Models\LibraryUnit;
use App\Models\User;
use App\Services\LibraryAccess;
use App\Services\Ljk\LjkQrRenderer;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LibraryMemberController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $schoolId = $unit->school_id;
        $members = LibraryMember::with(['student:id,name,nis,nisn,gender,photo_path', 'user:id,name', 'cards' => fn ($q) => $q->where('status', LibraryMemberCard::STATUS_ACTIVE)])
            ->where('library_unit_id', $unit->id)
            ->when($request->filled('member_type'), fn ($q) => $q->where('member_type', $request->member_type))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('member_number', 'like', '%'.$request->search.'%')->orWhereHas('student', fn ($st) => $st->where('name', 'like', '%'.$request->search.'%')->orWhere('nis', 'like', '%'.$request->search.'%'))))
            ->orderByDesc('created_at')->limit(200)->get();

        $baseMembers = LibraryMember::where('library_unit_id', $unit->id);
        $stats = [
            'total' => (clone $baseMembers)->count(),
            'active' => (clone $baseMembers)->where('status', LibraryMember::STATUS_ACTIVE)->count(),
            'student' => (clone $baseMembers)->where('member_type', 'student')->count(),
            'gtk' => (clone $baseMembers)->where('member_type', 'gtk')->count(),
        ];

        return view('perpustakaan.members.index', compact('userId', 'units', 'unit', 'members', 'stats'));
    }

    public function searchStudents(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $unit = LibraryUnit::findOrFail($request->input('unit_id'));
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $search = trim((string) $request->input('q', ''));

        $students = Student::with('currentClassHistory.studyGroup')
            ->where('status', 'active')
            ->when($unit->gender_scope !== 'mixed', fn ($q) => $q->whereRaw('UPPER(gender) = ?', [strtoupper((string) $unit->gender_scope)]))
            ->when($search !== '', fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.$search.'%')->orWhere('nis', 'like', '%'.$search.'%')->orWhere('nisn', 'like', '%'.$search.'%')))
            ->orderBy('name')->limit(50)->get();

        return response()->json([
            'results' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'text' => trim(($student->nis ?: $student->nisn ?: '—').' — '.$student->name.($student->currentClassHistory?->studyGroup?->name ? ' ('.$student->currentClassHistory->studyGroup->name.')' : '')),
            ])->values(),
        ]);
    }

    public function searchGtk(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $unit = LibraryUnit::findOrFail($request->input('unit_id'));
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $search = trim((string) $request->input('q', ''));

        $users = User::with('gtkProfile')
            ->whereHas('gtkProfile', fn ($q) => $q->when($unit->gender_scope !== 'mixed', fn ($p) => $p->whereRaw('UPPER(jenis_kelamin) = ?', [strtoupper((string) $unit->gender_scope)])))
            ->when($search !== '', fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->orderBy('name')->limit(50)->get(['id', 'name', 'email']);

        return response()->json([
            'results' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'text' => trim($user->name.($user->email ? ' — '.$user->email : '')),
            ])->values(),
        ]);
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'library_unit_id' => 'required|exists:library_units,id',
            'member_type' => 'required|in:student,gtk',
            'student_id' => 'nullable|exists:students,id',
            'user_id' => 'nullable|exists:users,id',
            'member_number' => 'nullable|string|max:50',
            'joined_at' => 'nullable|date',
        ]);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);

        $student = null;
        if ($validated['member_type'] === 'student') {
            $student = Student::findOrFail($validated['student_id'] ?? null);
            $this->assertEligible($student, $unit);
            abort_if(LibraryMember::where('library_unit_id', $unit->id)->where('student_id', $student->id)->exists(), 422, 'Santri sudah menjadi anggota unit ini.');
        } else {
            $user = User::with('gtkProfile')->findOrFail($validated['user_id'] ?? null);
            abort_if(LibraryMember::where('library_unit_id', $unit->id)->where('user_id', $user->id)->exists(), 422, 'GTK sudah menjadi anggota unit ini.');
            if ($unit->gender_scope !== 'mixed') {
                $gtkGender = strtoupper((string) $user->gtkProfile?->jenis_kelamin);
                abort_if($gtkGender === '' || $gtkGender !== strtoupper((string) $unit->gender_scope), 422, 'GTK tidak sesuai gender unit perpustakaan.');
            }
        }

        $member = DB::transaction(function () use ($validated, $unit, $student, $request) {
            $memberNumber = trim((string) ($validated['member_number'] ?? ''));
            if ($memberNumber === '') {
                $identity = $student?->nis ?: $student?->nisn ?: Str::upper(Str::random(6));
                $memberNumber = 'LIB-'.preg_replace('/[^A-Za-z0-9]/', '', $unit->code).'-'.$identity;
                while (LibraryMember::where('member_number', $memberNumber)->exists()) {
                    $memberNumber .= '-'.random_int(1, 99);
                }
            }
            $member = LibraryMember::create([
                'library_unit_id' => $unit->id,
                'student_id' => $student?->id,
                'user_id' => $validated['member_type'] === 'gtk' ? $validated['user_id'] : null,
                'member_number' => $memberNumber,
                'member_type' => $validated['member_type'],
                'status' => LibraryMember::STATUS_ACTIVE,
                'joined_at' => $validated['joined_at'] ?? today(),
                'is_primary' => true,
            ]);
            LibraryMemberCard::create([
                'library_member_id' => $member->id,
                'card_number' => 'CARD-'.$memberNumber,
                'status' => LibraryMemberCard::STATUS_ACTIVE,
                'issued_at' => today(),
                'valid_until' => now()->addYear()->toDateString(),
            ]);

            return $member;
        });

        return redirect()->route('user.perpustakaan.members.index', ['userId' => $userId, 'unit_id' => $unit->id])
            ->with('success', 'Anggota '.$member->member_number.' berhasil didaftarkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $member = LibraryMember::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $member->unit), 403);
        $validated = $request->validate(['status' => 'required|in:active,suspended,expired,lost,revoked', 'notes' => 'nullable|string|max:2000']);
        $member->update($validated);

        return back()->with('success', 'Status anggota diperbarui.');
    }

    public function replaceCard(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $member = LibraryMember::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $member->unit), 403);
        $old = $member->cards()->where('status', LibraryMemberCard::STATUS_ACTIVE)->latest('issued_at')->first();
        $setting = LibrarySetting::where('library_unit_id', $member->library_unit_id)->latest()->first();
        DB::transaction(function () use ($member, $old, $setting, $request) {
            $old?->update(['status' => LibraryMemberCard::STATUS_LOST, 'revoked_at' => now(), 'revoked_by' => $request->user()->id]);
            $number = 'CARD-'.$member->member_number.'-'.now()->format('YmdHis');
            LibraryMemberCard::create([
                'library_member_id' => $member->id,
                'card_number' => $number,
                'status' => LibraryMemberCard::STATUS_ACTIVE,
                'issued_at' => today(),
                'valid_until' => now()->addYear()->toDateString(),
                'replacement_of_id' => $old?->id,
                'replacement_fee' => $setting?->replacement_card_fee ?? 0,
                'fee_status' => ($setting?->replacement_card_fee ?? 0) > 0 ? 'unpaid' : 'free',
            ]);
        });

        return back()->with('success', 'Kartu pengganti diterbitkan; kartu lama dinonaktifkan.');
    }

    public function printCard(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $member = LibraryMember::with(['unit', 'student', 'user'])->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $member->unit), 403);
        $card = $member->cards()->where('status', LibraryMemberCard::STATUS_ACTIVE)->latest('issued_at')->firstOrFail();
        $qr = LjkQrRenderer::pngDataUri($card->card_number, 260);
        $card->increment('print_count');
        $card->forceFill(['printed_at' => now()])->save();

        return app(DocumentPdfService::class)->fromView(
            'perpustakaan.members.card-pdf',
            compact('member', 'card', 'qr'),
            'kartu-anggota-'.$member->member_number,
            'portrait'
        );
    }

    public function printCards(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $unit = LibraryUnit::findOrFail($request->input('library_unit_id'));
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $memberIds = collect($request->input('member_ids', []))->filter()->unique()->values();
        $members = LibraryMember::with(['unit', 'student', 'user', 'cards' => fn ($q) => $q->where('status', LibraryMemberCard::STATUS_ACTIVE)->latest('issued_at')])
            ->where('library_unit_id', $unit->id)
            ->when($memberIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $memberIds))
            ->orderBy('member_number')->limit(100)->get();
        abort_if($members->isEmpty(), 404, 'Tidak ada anggota untuk dicetak.');

        $cards = $members->map(function ($member) {
            $card = $member->cards->first();
            if (! $card) {
                return null;
            }

            return ['member' => $member, 'card' => $card, 'qr' => LjkQrRenderer::pngDataUri($card->card_number, 240)];
        })->filter()->values();

        return app(DocumentPdfService::class)->fromView(
            'perpustakaan.members.cards-pdf',
            compact('unit', 'cards'),
            'kartu-anggota-'.$unit->code,
            'portrait'
        );
    }

    private function assertEligible(Student $student, LibraryUnit $unit): void
    {
        if ($unit->gender_scope !== 'mixed' && $student->gender !== $unit->gender_scope) {
            abort(422, 'Gender santri tidak sesuai dengan unit perpustakaan.');
        }

        $history = StudentClassHistory::with('studyGroup.gradeLevel')->where('student_id', $student->id)->latest()->first();
        $level = $history?->studyGroup?->gradeLevel?->level;
        if ($unit->level_scope === 'sd' && $level && $level > 6) {
            abort(422, 'Unit perpustakaan khusus jenjang SD.');
        }
        if ($unit->level_scope === 'smp' && $level && ($level < 7 || $level > 9)) {
            abort(422, 'Unit perpustakaan khusus jenjang SMP.');
        }
        if ($unit->level_scope === 'sd_smp' && $level && $level > 9) {
            abort(422, 'Unit perpustakaan khusus jenjang SD–SMP.');
        }
    }
}
