<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GtkProfile;
use App\Models\GtkEmployment;
use App\Models\GtkPension;
use App\Models\PensionSetting;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PensionController extends Controller
{
    public function index(string $userId)
    {
        $users = User::with([
            'gtkProfile',
            'gtkEmployment',
            'pension',
        ])->where('is_active', true)->get();

        $settings = PensionSetting::allSettings();
        $bupAge   = (int) ($settings['bup_age'] ?? 58);

        $gtkList = $users->map(function ($user) use ($bupAge) {
            $tanggalLahir = $user->gtkProfile?->tanggal_lahir;
            $age = $tanggalLahir ? Carbon::parse($tanggalLahir)->age : null;
            $plannedPensionDate = null;

            if ($age !== null && $age < $bupAge) {
                $plannedPensionDate = Carbon::parse($tanggalLahir)
                    ->addYears($bupAge)
                    ->toDateString();
            } elseif ($age !== null && $age >= $bupAge) {
                $plannedPensionDate = Carbon::now()->toDateString();
            }

            $monthsUntilPension = null;
            if ($plannedPensionDate) {
                $monthsUntilPension = Carbon::now()->diffInMonths(Carbon::parse($plannedPensionDate), false);
            }

            $status = 'active';
            if ($user->pension) {
                $status = $user->pension->pension_status;
            } elseif ($monthsUntilPension !== null && $monthsUntilPension <= 0) {
                $status = 'due';
            } elseif ($monthsUntilPension !== null && $monthsUntilPension <= ((int) ($settings['notification_months'] ?? 6))) {
                $status = 'approaching';
            }

            return (object) [
                'user'             => $user,
                'age'              => $age,
                'bup_age'          => $bupAge,
                'planned_pension_date' => $plannedPensionDate,
                'months_until_pension' => $monthsUntilPension,
                'status'           => $status,
                'pension'          => $user->pension,
            ];
        })->sortBy(fn($item) => $item->months_until_pension ?? 9999);

        return view('pension.index', compact('userId', 'gtkList', 'settings'));
    }

    public function settings(string $userId)
    {
        $settings = PensionSetting::allSettings();

        return view('pension.settings', compact('userId', 'settings'));
    }

    public function updateSettings(Request $request, string $userId)
    {
        $rules = [
            'bup_age'               => 'required|integer|min:40|max:70',
            'notification_months'   => 'required|integer|min:1|max:24',
            'early_retirement_age'   => 'nullable|integer|min:30|max:65',
            'min_service_years'     => 'nullable|integer|min:0|max:50',
            'pension_percentage'    => 'nullable|integer|min:0|max:100',
            'early_retirement_years'=> 'nullable|integer|min:1|max:10',
            'notification_enabled'  => 'nullable|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $fields = [
            'bup_age', 'notification_months', 'early_retirement_age',
            'min_service_years', 'pension_percentage', 'early_retirement_years',
            'notification_enabled',
        ];

        foreach ($fields as $key) {
            if ($key === 'notification_enabled') {
                PensionSetting::updateSetting($key, $request->boolean($key) ? '1' : '0');
            } else {
                PensionSetting::updateSetting($key, $request->input($key));
            }
        }

        return redirect()->route('user.pension.settings', ['userId' => $userId])
            ->with('success', 'Pengaturan pensiun berhasil diperbarui.');
    }

    public function edit(string $userId, string $uuid)
    {
        $gtk = User::with(['gtkProfile', 'gtkEmployment', 'pension'])->where('id', $uuid)->firstOrFail();
        $settings = PensionSetting::allSettings();

        return view('pension.edit', compact('userId', 'gtk', 'settings'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $validator = Validator::make($request->all(), [
            'pension_type'        => 'nullable|in:normal,dini,cacat,janda',
            'planned_pension_date'=> 'nullable|date|after_or_equal:today',
            'pension_letter_no'   => 'nullable|string|max:100',
            'pension_letter_date'  => 'nullable|date',
            'pension_status'       => 'nullable|in:draft,pending,approved,completed,cancelled',
            'benefit_amount'       => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'pension_type'        => $request->pension_type,
                'planned_pension_date'=> $request->planned_pension_date,
                'pension_letter_no'   => $request->pension_letter_no,
                'pension_letter_date' => $request->pension_letter_date,
                'pension_status'      => $request->pension_status,
                'benefit_amount'      => $request->filled('benefit_amount') ? $request->benefit_amount : null,
                'notes'              => $request->notes,
                'processed_by'       => auth()->id(),
            ];

            GtkPension::updateOrCreate(
                ['user_id' => $uuid],
                $data
            );

            DB::commit();

            return redirect()->route('user.pension.index', ['userId' => $userId])
                ->with('success', 'Data pensiun berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating pension: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function datatable(Request $request, string $userId)
    {
        $users = User::with([
            'gtkProfile',
            'gtkEmployment',
            'pension',
        ])->where('is_active', true)->get();

        $settings = PensionSetting::allSettings();
        $bupAge   = (int) ($settings['bup_age'] ?? 58);

        $data = $users->map(function ($user) use ($bupAge) {
            $tanggalLahir = $user->gtkProfile?->tanggal_lahir;
            $age = $tanggalLahir ? Carbon::parse($tanggalLahir)->age : null;
            $plannedDate = null;

            if ($age !== null && $age < $bupAge) {
                $plannedDate = Carbon::parse($tanggalLahir)->addYears($bupAge)->toDateString();
            } elseif ($age !== null && $age >= $bupAge) {
                $plannedDate = Carbon::now()->toDateString();
            }

            $monthsUntil = $plannedDate ? Carbon::now()->diffInMonths(Carbon::parse($plannedDate), false) : null;

            return [
                'uuid'       => $user->id,
                'name'       => $user->name,
                'position'    => $user->gtkEmployment?->jabatan ?? '–',
                'work_unit'   => $user->workUnits?->first()?->workUnit?->name ?? '–',
                'age'         => $age,
                'bup_age'     => $bupAge,
                'planned_date'=> $plannedDate,
                'months_until'=> $monthsUntil,
                'pension'     => $user->pension ? [
                    'status'  => $user->pension->pension_status,
                    'type'    => $user->pension->pension_type,
                    'letter_no' => $user->pension->pension_letter_no,
                    'benefit' => $user->pension->benefit_amount,
                ] : null,
            ];
        })->sortBy(fn($item) => $item['months_until'] ?? 9999)->values();

        return response()->json(['data' => $data]);
    }
}