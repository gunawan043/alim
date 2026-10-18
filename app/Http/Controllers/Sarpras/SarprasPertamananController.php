<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\GtkWorkUnit;
use App\Models\PertamananAgenda;
use App\Models\PertamananAgendaItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SarprasPertamananController extends SarprasBaseController
{
    public function agenda(Request $request)
    {
        $this->authorizePertamanan();

        $date = $this->resolveDate($request->input('date'));
        $agenda = PertamananAgenda::firstOrCreate(
            ['agenda_date' => $date->toDateString()],
            [
                'academic_year' => $this->academicYear($date),
                'officer_id' => auth()->id(),
                'created_by' => auth()->id(),
            ]
        );

        if ($agenda->wasRecentlyCreated) {
            $this->seedDefaults($agenda);
            $agenda->load('items');
        }

        $items = $agenda->items()->with('officer')->orderBy('sort_order')->get();
        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('sarpras.pertamanan.agenda', compact('agenda', 'items', 'users', 'date'));
    }

    public function saveAgenda(Request $request)
    {
        $this->authorizePertamanan();

        $validated = $request->validate([
            'agenda_date' => 'required|date',
            'officer_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.status' => 'nullable|in:belum,selesai,tunda',
            'items.*.officer_id' => 'nullable|uuid|exists:users,id',
            'items.*.notes' => 'nullable|string|max:1000',
        ]);

        $agenda = PertamananAgenda::whereDate('agenda_date', $validated['agenda_date'])->firstOrFail();
        $agenda->update([
            'officer_id' => $validated['officer_id'] ?? $agenda->officer_id,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach (($validated['items'] ?? []) as $itemId => $data) {
            $item = PertamananAgendaItem::where('agenda_id', $agenda->id)->find($itemId);
            if (! $item) {
                continue;
            }
            $item->update([
                'status' => $data['status'] ?? 'belum',
                'officer_id' => $data['officer_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return back()->with('success', 'Agenda harian pertamanan berhasil disimpan.');
    }

    public function storeItem(Request $request)
    {
        $this->authorizePertamanan();

        $validated = $request->validate([
            'agenda_date' => 'required|date',
            'section' => 'required|in:pemeliharaan,pembibitan,permintaan_darurat',
            'group_title' => 'nullable|string|max:191',
            'title' => 'required|string|max:191',
            'status' => 'required|in:belum,selesai,tunda',
            'officer_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($validated['agenda_date']);
        $agenda = PertamananAgenda::firstOrCreate(
            ['agenda_date' => $date->toDateString()],
            [
                'academic_year' => $this->academicYear($date),
                'officer_id' => auth()->id(),
                'created_by' => auth()->id(),
            ]
        );

        $nextSort = ((int) PertamananAgendaItem::where('agenda_id', $agenda->id)->max('sort_order')) + 1;
        PertamananAgendaItem::create([
            ...$validated,
            'agenda_id' => $agenda->id,
            'sort_order' => $nextSort,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Item agenda pertamanan ditambahkan.');
    }

    public function destroyItem(string $userId, Request $request, string $itemId)
    {
        $this->authorizePertamanan();
        $item = PertamananAgendaItem::findOrFail($itemId);
        $date = $item->agenda?->agenda_date;
        $item->delete();

        return redirect()->route('sarpras.pertamanan.agenda.index', ['date' => $date?->toDateString()])
            ->with('success', 'Item agenda pertamanan dihapus.');
    }

    private function seedDefaults(PertamananAgenda $agenda): void
    {
        $items = [
            ['pemeliharaan', 'Menyiram taman', 'Taman asrama SMA putri lantai 2 dan lantai 3 sebelah Timur'],
            ['pemeliharaan', 'Menyiram taman', 'Taman depan SD'],
            ['pemeliharaan', 'Menyiram taman', 'Taman depan UKS putra'],
            ['pemeliharaan', 'Menyiram taman', 'Taman depan MA plus'],
            ['pemeliharaan', 'Menyiram taman', 'Taman SMP putra'],
            ['pemeliharaan', 'Penataan taman', "Taman masjid jami'"],
            ['pemeliharaan', 'Pemangkasan pohon', 'Pemangkasan pelepah dan memetik buah 2 pohon kelapa SMA putri'],
            ['pemeliharaan', null, 'Menimbunan sampah organik untuk kompos'],
            ['pemeliharaan', null, 'Membantu membersihkan jalan utama'],
        ];

        foreach ($items as $index => [$section, $group, $title]) {
            PertamananAgendaItem::create([
                'agenda_id' => $agenda->id,
                'section' => $section,
                'group_title' => $group,
                'title' => $title,
                'status' => 'belum',
                'sort_order' => $index + 1,
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function authorizePertamanan(): void
    {
        if ($this->isCentralSarpras() || $this->isUrtLeadership()) {
            return;
        }

        $user = auth()->user();
        $jabatan = $user
            ? strtoupper(GtkWorkUnit::where('user_id', $user->id)->pluck('jabatan')->implode(' '))
            : '';

        abort_unless(
            str_contains($jabatan, 'PERTAMANAN') || str_contains($jabatan, 'KEBERSIHAN'),
            403,
            'Hanya Petugas Pertamanan/Kebersihan dan URT yang dapat mengakses agenda ini.'
        );
    }

    private function resolveDate(?string $date): Carbon
    {
        try {
            return $date ? Carbon::parse($date)->startOfDay() : today();
        } catch (\Throwable $e) {
            return today();
        }
    }

    private function academicYear(Carbon $date): string
    {
        $start = $date->month >= 7 ? $date->year : $date->year - 1;

        return $start.'/'.($start + 1);
    }
}
