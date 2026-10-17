<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Uks\UksMedicationAdministration;
use App\Models\Uks\UksPatient;
use App\Models\UksCareEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * MedicationAdministrationController — Histori pemberian obat yang lebih lengkap
 * untuk fitur Status Perawatan (memiliki kuantitas, jam pemberian, dll).
 *
 * Berbeda dari controller UksMedicationLog yang sudah ada — controller ini
 * menyimpan histori yang akan dirender di tab "Pemberian Obat".
 */
class MedicationAdministrationController extends Controller
{
    public function store(Request $request, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'medicine_name' => 'required|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1|max:999',
            'given_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $admin = UksMedicationAdministration::create([
            'patient_id' => $patient->id,
            'administered_by' => Auth::id(),
            'medicine_name' => $validated['medicine_name'],
            'dosage' => $validated['dosage'] ?? null,
            'quantity' => $validated['quantity'],
            'given_at' => $validated['given_at'] ?? now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        // Juga tulis ke care-event agar muncul di timeline existing
        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => $admin->given_at,
            'event_type' => 'pemberian_obat',
            'event_title' => 'Pemberian Obat: '.$validated['medicine_name'],
            'description' => sprintf(
                '%s%s · %d unit%s',
                $validated['dosage'] ?? '—',
                $validated['dosage'] ? ' · ' : '',
                $validated['quantity'],
                $validated['notes'] ? ' · '.$validated['notes'] : ''
            ),
        ]);

        return redirect()
            ->route('user.uks.treatment-status.show', ['uuid' => $patient->id])
            ->with('success', sprintf(
                'Pemberian obat %s (%d unit) berhasil dicatat.',
                $validated['medicine_name'],
                $validated['quantity']
            ));
    }
}
