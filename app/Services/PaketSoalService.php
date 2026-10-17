<?php

namespace App\Services;

use App\Models\KisiKisiSoal;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\Soal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaketSoalService
{
    /**
     * Create a paket soal from a kisi-kisi by selecting soals matching the kisi spec.
     *
     * @param  array<string, mixed>  $data  {
     *                                      kisi_kisi_soal_id, judul, kode_paket, versi,
     *                                      is_acak_urutan_soal, is_acak_opsi, waktu_pengerjaan_menit,
     *                                      shared_scope, published_by?, items?: [['soal_id', 'urutan', 'bobot_override']]
     *                                      }
     */
    public function create(array $data): PaketSoal
    {
        $validator = Validator::make($data, [
            'kisi_kisi_soal_id' => 'required|exists:kisi_kisi_soal,id',
            'judul' => 'required|string|max:150',
            'kode_paket' => 'required|string|max:50|unique:paket_soal,kode_paket',
            'versi' => 'nullable|integer|min:1',
            'is_acak_urutan_soal' => 'nullable|boolean',
            'is_acak_opsi' => 'nullable|boolean',
            'waktu_pengerjaan_menit' => 'nullable|integer|min:1|max:600',
            'shared_scope' => 'nullable|in:private,internal_school,public_pool',
            'published_by' => 'nullable|exists:users,id',
            'instruksi_umum' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $validator->validate();

        $kisi = KisiKisiSoal::with('items')->findOrFail($data['kisi_kisi_soal_id']);

        return DB::transaction(function () use ($data, $kisi) {
            $paket = PaketSoal::create([
                'kisi_kisi_soal_id' => $kisi->id,
                'judul' => $data['judul'],
                'kode_paket' => $data['kode_paket'],
                'versi' => $data['versi'] ?? 1,
                'is_acak_urutan_soal' => $data['is_acak_urutan_soal'] ?? false,
                'is_acak_opsi' => $data['is_acak_opsi'] ?? false,
                'waktu_pengerjaan_menit' => $data['waktu_pengerjaan_menit'] ?? 90,
                'shared_scope' => $data['shared_scope'] ?? 'private',
                'instruksi_umum' => $data['instruksi_umum'] ?? null,
                'is_published' => ! empty($data['published_by']),
            ]);

            if (! empty($data['published_by'])) {
                $paket->publish($data['published_by']);
            }

            if (! empty($data['items'])) {
                $this->syncItems($paket, $data['items']);
            } else {
                $this->autoSelectFromKisi($paket, $kisi);
            }

            $paket->syncActualCounts();

            return $paket->fresh(['items.soal.options', 'kisiKisi.items']);
        });
    }

    /**
     * Auto-select soals from kisi-kisi spec.
     * Picks approved soals matching each (tp_id, level_kognitif) requirement.
     */
    public function autoSelectFromKisi(PaketSoal $paket, KisiKisiSoal $kisi): void
    {
        $items = $kisi->items;
        $selectedSoalIds = [];

        foreach ($items as $kisiItem) {
            $candidates = Soal::where('tp_id', $kisiItem->tp_id)
                ->where('status', 'approved')
                ->whereNotIn('id', $selectedSoalIds)
                ->inRandomOrder()
                ->limit($kisiItem->jumlah_soal)
                ->pluck('id');

            foreach ($candidates as $soalId) {
                $selectedSoalIds[] = $soalId;
            }
        }

        $urutan = 1;
        foreach ($selectedSoalIds as $soalId) {
            PaketSoalItem::create([
                'paket_soal_id' => $paket->id,
                'soal_id' => $soalId,
                'urutan' => $urutan++,
            ]);
        }
    }

    /**
     * Sync items (replace all).
     *
     * @param  array<int, array{soal_id:string,urutan:int,bobot_override?:float}>  $items
     */
    public function syncItems(PaketSoal $paket, array $items): void
    {
        $paket->items()->delete();
        foreach ($items as $item) {
            PaketSoalItem::create([
                'paket_soal_id' => $paket->id,
                'soal_id' => $item['soal_id'],
                'urutan' => $item['urutan'],
                'bobot_override' => $item['bobot_override'] ?? null,
            ]);
        }
    }

    /**
     * Build a new version (V2, V3) from an existing paket for parallel use (anti-cheating).
     */
    public function cloneForParallel(string $paketId, string $newKode, ?string $judul = null): PaketSoal
    {
        $source = PaketSoal::with('items')->findOrFail($paketId);

        return DB::transaction(function () use ($source, $newKode, $judul) {
            $cloned = PaketSoal::create([
                'kisi_kisi_soal_id' => $source->kisi_kisi_soal_id,
                'judul' => $judul ?? ($source->judul.' (Versi Paralel)'),
                'kode_paket' => $newKode,
                'versi' => $source->versi + 1,
                'is_acak_urutan_soal' => $source->is_acak_urutan_soal,
                'is_acak_opsi' => $source->is_acak_opsi,
                'waktu_pengerjaan_menit' => $source->waktu_pengerjaan_menit,
                'instruksi_umum' => $source->instruksi_umum,
                'shared_scope' => $source->shared_scope,
                'is_published' => false,
            ]);

            foreach ($source->items as $item) {
                PaketSoalItem::create([
                    'paket_soal_id' => $cloned->id,
                    'soal_id' => $item->soal_id,
                    'urutan' => $item->urutan,
                    'bobot_override' => $item->bobot_override,
                ]);
            }

            $cloned->syncActualCounts();

            return $cloned;
        });
    }
}
