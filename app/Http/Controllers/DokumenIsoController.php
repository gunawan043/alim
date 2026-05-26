<?php

namespace App\Http\Controllers;

use App\Models\DokumenIso;
use App\Models\Divisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DokumenIsoController extends Controller
{
    public function index(Request $request)
    {
        $query = DokumenIso::with('divisi')
            ->leftJoin('divisis', 'dokumen_iso.divisi_id', '=', 'divisis.id')
            ->orderByRaw("COALESCE(divisis.sort_order, 999) ASC")
            ->orderBy('dokumen_iso.sort_order', 'asc')
            ->select('dokumen_iso.*');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_dokumen', 'like', "%{$request->search}%")
                    ->orWhere('kode_dokumen', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('divisi_id')) {
            $query->where('divisi_id', $request->divisi_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $dokumenList = $query->paginate(20)->withQueryString();
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');
        $divisiList = Divisi::orderBy('sort_order')->get();
        $totalDokumen = DokumenIso::count();
        $latestRevisi = DokumenIso::orderBy('updated_at', 'desc')->value('revisi_ke') ?? '0';

        return view('dokumen-iso.index', compact(
            'dokumenList', 'isSuperAdmin', 'totalDokumen',
            'latestRevisi', 'divisiList'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_dokumen'       => 'required|string|max:255',
            'prosedur_konsultan' => 'nullable|string|max:255',
            'pasal'              => 'nullable|string|max:100',
            'kode_dokumen'      => 'nullable|string|max:50',
            'tanggal_berlaku'   => 'nullable|date',
            'revisi_ke'         => 'nullable|string|max:20',
            'keterangan'        => 'nullable|string',
            'kategori'          => 'nullable|in:PROSEDUR,FORMULIR',
            'link_dokumen'      => 'nullable|url|max:500',
            'divisi_id'         => 'nullable|exists:divisis,id',
            'is_active'         => 'nullable|in:0,1',
        ]);

        $data['id'] = Str::uuid();
        $data['is_active'] = $request->boolean('is_active', true);
        DokumenIso::create($data);

        return redirect()->back()->with('success', 'Dokumen ISO berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $dokumen = DokumenIso::findOrFail($id);

        $data = $request->validate([
            'nama_dokumen'       => 'required|string|max:255',
            'prosedur_konsultan' => 'nullable|string|max:255',
            'pasal'              => 'nullable|string|max:100',
            'kode_dokumen'      => 'nullable|string|max:50',
            'tanggal_berlaku'   => 'nullable|date',
            'revisi_ke'         => 'nullable|string|max:20',
            'keterangan'        => 'nullable|string',
            'kategori'          => 'nullable|in:PROSEDUR,FORMULIR',
            'link_dokumen'      => 'nullable|url|max:500',
            'divisi_id'         => 'nullable|exists:divisis,id',
            'is_active'         => 'nullable|in:0,1',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $dokumen->update($data);

        return redirect()->back()->with('success', 'Dokumen ISO berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        DokumenIso::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Dokumen ISO berhasil dihapus.');
    }

    public function subscriptions(string $userId)
    {
        $user = \App\Models\User::with('divisiSubscriptions')->findOrFail($userId);
        $isSuperAdmin = $user->hasRole('Super Admin');

        $allDivisis = \App\Models\Divisi::orderBy('sort_order')->get();
        $subscribedIds = $user->divisiSubscriptions->pluck('id')->toArray();

        $divisiList = $allDivisis->map(function ($d) use ($subscribedIds) {
            return [
                'id'       => $d->id,
                'nama'     => $d->nama,
                'kode'     => $d->kode,
                'subscribed' => in_array($d->id, $subscribedIds),
            ];
        });

        return view('dokumen-iso.subscriptions', compact(
            'user', 'divisiList', 'isSuperAdmin'
        ));
    }

    public function subscribe(string $userId, string $divisiId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $divisi = \App\Models\Divisi::findOrFail($divisiId);
        $user->divisiSubscriptions()->syncWithoutDetaching([$divisi->id]);

        return redirect()->back()->with('success', "Berhasil subscribe ke {$divisi->nama}.");
    }

    public function unsubscribe(string $userId, string $divisiId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $divisi = \App\Models\Divisi::findOrFail($divisiId);
        $user->divisiSubscriptions()->detach($divisi->id);

        return redirect()->back()->with('success', "Berhasil unsubscribe dari {$divisi->nama}.");
    }
}
