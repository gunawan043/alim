<?php

namespace App\Http\Controllers\Osipp;

use App\Models\AcademicYear;
use App\Models\Osipp\OsippPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OsippPeriodController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);

        $academicYears = $this->workspace->academicYearOptions($context['schoolId']);
        $statusOptions = OsippPeriod::STATUS_OPTIONS;

        return view('osipp.periode.index', array_merge($context, compact('academicYears', 'statusOptions')));
    }

    public function store(Request $request)
    {
        $context = $this->context($request);
        $this->ensureManage($context);

        $data = $this->validated($request, $context['schoolId']);
        $data['supervisor_user_id'] = $data['supervisor_user_id'] ?? $context['user']->id;

        $period = DB::transaction(function () use ($data, $context) {
            if (($data['status'] ?? null) === OsippPeriod::STATUS_ACTIVE) {
                OsippPeriod::where('school_id', $context['schoolId'])
                    ->update(['status' => OsippPeriod::STATUS_ENDED, 'updated_by' => $context['user']->id]);
            }

            return OsippPeriod::create($data + [
                'school_id' => $context['schoolId'],
                'created_by' => $context['user']->id,
                'updated_by' => $context['user']->id,
            ]);
        });

        return redirect()
            ->route('osipp.dashboard', ['userId' => $context['userId'], 'period_id' => $period->id])
            ->with('success', 'Periode kepengurusan OSIPP berhasil dibuat.');
    }

    public function update(Request $request, string $userId, OsippPeriod $period)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($period);

        abort_unless((string) $period->school_id === (string) $context['schoolId'], 404);

        $data = $this->validated($request, $context['schoolId'], $period);

        DB::transaction(function () use ($data, $period, $context) {
            if (($data['status'] ?? null) === OsippPeriod::STATUS_ACTIVE) {
                OsippPeriod::where('school_id', $context['schoolId'])
                    ->where('id', '!=', $period->id)
                    ->update(['status' => OsippPeriod::STATUS_ENDED, 'updated_by' => $context['user']->id]);
            }

            $period->update($data + ['updated_by' => $context['user']->id]);
        });

        return back()->with('success', 'Periode kepengurusan OSIPP berhasil diperbarui.');
    }

    public function activate(Request $request, string $userId, OsippPeriod $period)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($period);

        abort_unless((string) $period->school_id === (string) $context['schoolId'], 404);

        DB::transaction(function () use ($period, $context) {
            OsippPeriod::where('school_id', $context['schoolId'])
                ->where('id', '!=', $period->id)
                ->update(['status' => OsippPeriod::STATUS_ENDED, 'updated_by' => $context['user']->id]);

            $period->update([
                'status' => OsippPeriod::STATUS_ACTIVE,
                'updated_by' => $context['user']->id,
            ]);
        });

        return back()->with('success', 'Periode OSIPP diaktifkan.');
    }

    public function destroy(Request $request, string $userId, OsippPeriod $period)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($period);

        abort_unless((string) $period->school_id === (string) $context['schoolId'], 404);
        abort_if($period->isActive(), 422, 'Periode aktif tidak dapat dihapus. Aktifkan periode lain terlebih dahulu.');

        $period->delete();

        return back()->with('success', 'Periode kepengurusan OSIPP dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $schoolId, ?OsippPeriod $period = null): array
    {
        $academicYearId = $request->input('academic_year_id')
            ?: AcademicYear::query()->where('is_active', true)->value('id');

        $data = $request->validate([
            'academic_year_id' => ['nullable', 'uuid', 'exists:academic_years,id'],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('osipp_periods', 'name')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)
                        ->where('academic_year_id', $academicYearId))
                    ->ignore($period?->id),
            ],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'decree_date' => ['nullable', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'supervisor_user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(array_keys(OsippPeriod::STATUS_OPTIONS))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['status'] = $data['status'] ?? OsippPeriod::STATUS_DRAFT;

        $data['academic_year_id'] = $academicYearId;

        return $data;
    }
}
