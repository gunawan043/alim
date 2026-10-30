<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\Documents\DocumentVisualService;
use App\Services\Documents\SchoolLeaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Pengaturan tanda tangan digital & validasi dokumen surat.
 *
 * Dipakai dari halaman surat (mutasi masuk/keluar, lulus/DO, SKL) supaya admin
 * bisa langsung mengunggah TTD KSP/stempel dan memilih mode validasi tanpa
 * berpindah ke menu data sekolah.
 */
class DocumentSignatureSettingController extends Controller
{
    public function update(Request $request, string $userId)
    {
        $data = $request->validate([
            'type' => 'required|in:mutasi,sk,skl',
            'school_id' => 'required|exists:schools,id',
            'validation_mode' => 'required|in:visual,qr,pades',
            'show_ttd_ksp' => 'nullable|in:0,1',
            'show_stamp' => 'nullable|in:0,1',
            'ttd_ksp_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'stamp_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'remove_ttd_ksp' => 'nullable|boolean',
            'remove_stamp' => 'nullable|boolean',
            'ttd_width' => 'nullable|numeric|min:5|max:100',
            'ttd_height' => 'nullable|numeric|min:5|max:60',
            'ttd_margin' => 'nullable|numeric|min:0|max:30',
            'stamp_width' => 'nullable|numeric|min:5|max:100',
            'stamp_height' => 'nullable|numeric|min:5|max:100',
            'stamp_margin' => 'nullable|numeric|min:0|max:30',
            'document_leader_name' => 'nullable|string|max:255',
            'document_leader_nupy' => 'nullable|string|max:50',
        ], [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute yang dipilih tidak valid.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat PNG atau JPG.',
            'max' => ':attribute maksimal :max KB.',
            'exists' => ':attribute tidak ditemukan.',
        ], [
            'type' => 'jenis dokumen',
            'school_id' => 'satuan pendidikan',
            'validation_mode' => 'mode validasi',
            'ttd_ksp_file' => 'file tanda tangan',
            'stamp_file' => 'file stempel',
        ]);

        $contextSchoolId = $request->attributes->get('schoolContextId');
        abort_if($contextSchoolId && (string) $contextSchoolId !== (string) $data['school_id'], 403, 'Akses ditolak.');

        $school = School::findOrFail($data['school_id']);

        $this->replaceImage($request, $school, 'ttd_ksp_file', 'ttd_ksp_path', 'remove_ttd_ksp');
        $this->replaceImage($request, $school, 'stamp_file', 'stamp_path', 'remove_stamp');

        try {
            app(DocumentVisualService::class)->updateType($school, $data['type'], [
                'validation_mode' => $data['validation_mode'],
                'show_ttd_ksp' => $request->input('show_ttd_ksp', '1'),
                'show_stamp' => $request->input('show_stamp', '1'),
                'ttd_width' => $data['ttd_width'] ?? null,
                'ttd_height' => $data['ttd_height'] ?? null,
                'ttd_margin' => $data['ttd_margin'] ?? null,
                'stamp_width' => $data['stamp_width'] ?? null,
                'stamp_height' => $data['stamp_height'] ?? null,
                'stamp_margin' => $data['stamp_margin'] ?? null,
            ]);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first('document_visual'));
        }

        $visual = is_array($school->document_visual_settings) ? $school->document_visual_settings : [];
        $leaderName = trim((string) $request->input('document_leader_name', ''));
        $leaderNupy = trim((string) $request->input('document_leader_nupy', ''));
        $auto = app(SchoolLeaderService::class)->autoForSchool($school);
        $visual['leader'] = ($leaderName === $auto['display_name'] && $leaderNupy === $auto['nupy'])
            ? ['name' => '', 'nupy' => '']
            : ['name' => $leaderName, 'nupy' => $leaderNupy];
        $school->document_visual_settings = $visual;

        $school->save();

        return back()->with('success', 'Tanda tangan digital & validasi dokumen berhasil diperbarui.');
    }

    /**
     * Ganti/hapus satu berkas gambar milik sekolah.
     */
    private function replaceImage(Request $request, School $school, string $input, string $column, string $removeKey): void
    {
        if ($request->boolean($removeKey) && $school->{$column}) {
            Storage::disk('public')->delete($school->{$column});
            $school->{$column} = null;
        }

        if (! $request->hasFile($input)) {
            return;
        }

        if ($school->{$column}) {
            Storage::disk('public')->delete($school->{$column});
        }

        $school->{$column} = $request->file($input)->store('schools/'.$column, 'public');
    }
}
