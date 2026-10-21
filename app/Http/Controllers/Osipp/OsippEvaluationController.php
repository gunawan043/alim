<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippEvaluation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OsippEvaluationController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);
        $period = $this->workspace->ensurePeriod($context);

        $evaluations = OsippEvaluation::query()
            ->with('evaluator:id,name')
            ->where('period_id', $period->id)
            ->orderByDesc('evaluation_date')
            ->paginate(15)
            ->withQueryString();

        $latest = $evaluations->getCollection()
            ->firstWhere('status', OsippEvaluation::STATUS_FINAL)
            ?: $evaluations->first();

        $aspects = OsippEvaluation::ASPECTS;
        $typeOptions = OsippEvaluation::TYPE_OPTIONS;
        $statusOptions = OsippEvaluation::STATUS_OPTIONS;

        $evaluationsJson = $evaluations->getCollection()->map(fn (OsippEvaluation $evaluation) => [
            'id' => $evaluation->id,
            'evaluation_type' => $evaluation->evaluation_type,
            'evaluation_date' => $evaluation->evaluation_date?->format('Y-m-d'),
            'scores' => $evaluation->scores ?? [],
            'strengths' => $evaluation->strengths,
            'recommendations' => $evaluation->recommendations,
            'status' => $evaluation->status,
            'update_url' => route('osipp.evaluasi.update', ['userId' => $context['userId'], 'evaluation' => $evaluation->id]),
        ])->values();

        return view('osipp.evaluasi.index', array_merge($context, compact(
            'period', 'evaluations', 'latest', 'aspects', 'typeOptions', 'statusOptions', 'evaluationsJson'
        )));
    }

    public function store(Request $request)
    {
        $context = $this->context($request);
        $this->ensureEvaluate($context);
        $period = $this->workspace->ensurePeriod($context);

        $data = $this->validated($request);
        $data['total_score'] = OsippEvaluation::computeTotal($data['scores']);
        $data['grade'] = OsippEvaluation::gradeFor((float) $data['total_score']);

        OsippEvaluation::create($data + [
            'period_id' => $period->id,
            'evaluator_user_id' => $context['user']->id,
            'created_by' => $context['user']->id,
            'updated_by' => $context['user']->id,
        ]);

        return back()->with('success', 'Evaluasi kepengurusan OSIPP disimpan.');
    }

    public function update(Request $request, string $userId, OsippEvaluation $evaluation)
    {
        $context = $this->context($request);
        $this->ensureEvaluate($context);
        $this->authorizePeriod($evaluation->period);

        $data = $this->validated($request);
        $data['total_score'] = OsippEvaluation::computeTotal($data['scores']);
        $data['grade'] = OsippEvaluation::gradeFor((float) $data['total_score']);
        $data['updated_by'] = $context['user']->id;

        $evaluation->update($data);

        return back()->with('success', 'Evaluasi kepengurusan OSIPP diperbarui.');
    }

    public function finalize(Request $request, string $userId, OsippEvaluation $evaluation)
    {
        $context = $this->context($request);
        $this->ensureEvaluate($context);
        $this->authorizePeriod($evaluation->period);

        $evaluation->update([
            'status' => OsippEvaluation::STATUS_FINAL,
            'updated_by' => $context['user']->id,
        ]);

        return back()->with('success', 'Evaluasi kepengurusan OSIPP difinalkan.');
    }

    public function destroy(Request $request, string $userId, OsippEvaluation $evaluation)
    {
        $context = $this->context($request);
        $this->ensureEvaluate($context);
        $this->authorizePeriod($evaluation->period);

        $evaluation->delete();

        return back()->with('success', 'Evaluasi kepengurusan OSIPP dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $rules = [
            'evaluation_type' => ['required', Rule::in(array_keys(OsippEvaluation::TYPE_OPTIONS))],
            'evaluation_date' => ['required', 'date'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(OsippEvaluation::STATUS_OPTIONS))],
            'scores' => ['required', 'array'],
        ];

        foreach (array_keys(OsippEvaluation::ASPECTS) as $aspect) {
            $rules['scores.'.$aspect] = ['required', 'integer', 'min:1', 'max:5'];
        }

        return $request->validate($rules);
    }
}
