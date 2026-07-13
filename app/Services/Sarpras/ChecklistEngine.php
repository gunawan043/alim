<?php

namespace App\Services\Sarpras;

use App\Models\ChecklistInstance;
use App\Models\ChecklistInstanceResponse;
use App\Models\ChecklistTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChecklistEngine
{
    public function resolveTemplate(string $workflowType, ?string $categorySlug = null): ?ChecklistTemplate
    {
        $query = ChecklistTemplate::where('workflow_type', $workflowType)
            ->where('is_active', true);

        if ($categorySlug) {
            $query->where(function ($q) use ($categorySlug) {
                $q->where('category_slug', $categorySlug)
                    ->orWhereNull('category_slug');
            })->orderByRaw('CASE WHEN category_slug = ? THEN 0 ELSE 1 END', [$categorySlug]);
        }

        return $query->orderByDesc('version')->first();
    }

    public function start(Model $context, ChecklistTemplate $template, ?int $executorId = null): ChecklistInstance
    {
        return DB::transaction(function () use ($context, $template, $executorId) {
            return ChecklistInstance::create([
                'template_id' => $template->id,
                'context_type' => $context->getMorphClass(),
                'context_id' => $context->getKey(),
                'executor_id' => $executorId,
                'status' => 'in_progress',
                'total_items_count' => $template->items()->count(),
                'started_at' => now(),
            ]);
        });
    }

    public function record(ChecklistInstance $instance, string $templateItemId, mixed $value, array $meta = []): ChecklistInstanceResponse
    {
        $item = $instance->template->items()->where('id', $templateItemId)->firstOrFail();

        $passed = $this->evaluatePassed($item, $value);

        return DB::transaction(function () use ($instance, $item, $value, $passed, $meta) {
            $response = ChecklistInstanceResponse::updateOrCreate(
                [
                    'instance_id' => $instance->id,
                    'template_item_id' => $item->id,
                ],
                [
                    'response_value' => is_array($value) ? $value : ['value' => $value],
                    'passed' => $passed,
                    'notes' => $meta['notes'] ?? null,
                ]
            );

            $this->recomputeAggregate($instance);

            return $response;
        });
    }

    public function complete(ChecklistInstance $instance, array $summary = []): ChecklistInstance
    {
        return DB::transaction(function () use ($instance, $summary) {
            $totalItems = $instance->template->items()->count();
            $answeredItems = $instance->responses()->count();
            $missingRequired = $instance->template->items()
                ->where('is_required', true)
                ->whereNotIn('id', $instance->responses()->pluck('template_item_id'))
                ->count();

            if ($missingRequired > 0) {
                throw new \DomainException(
                    "Cannot complete checklist — {$missingRequired} required item(s) unanswered."
                );
            }

            $failed = $instance->responses()->where('passed', false)->count();

            $status = $failed > 0 ? 'failed' : 'completed';
            if ($answeredItems < $totalItems) {
                $status = 'in_progress';
            }

            $instance->update([
                'status' => $summary['status'] ?? $status,
                'completed_at' => now(),
                'failed_items_count' => $failed,
                'result_summary' => array_merge([
                    'total_items' => $totalItems,
                    'answered' => $answeredItems,
                    'failed' => $failed,
                ], $summary),
            ]);

            return $instance->fresh();
        });
    }

    public function cancel(ChecklistInstance $instance, string $reason): ChecklistInstance
    {
        $instance->update([
            'status' => 'cancelled',
            'completed_at' => now(),
            'result_summary' => array_merge($instance->result_summary ?? [], ['cancel_reason' => $reason]),
        ]);
        return $instance->fresh();
    }

    public function progress(ChecklistInstance $instance): array
    {
        $total = $instance->total_items_count;
        $answered = $instance->responses()->count();
        $failed = $instance->responses()->where('passed', false)->count();

        return [
            'total' => $total,
            'answered' => $answered,
            'failed' => $failed,
            'remaining' => max(0, $total - $answered),
            'percent_complete' => $total > 0 ? round(($answered / $total) * 100, 1) : 0,
            'is_complete' => $answered >= $total,
        ];
    }

    protected function evaluatePassed($item, $value): bool
    {
        if ($item->triggers_failure === false) {
            return true;
        }
        if ($item->response_type === 'boolean') {
            return (bool) $value === true;
        }
        if ($item->response_type === 'severity') {
            return !in_array($value, ['high', 'critical', 'severe', 'rusak_berat'], true);
        }
        if ($item->response_type === 'choice' && is_array($item->options)) {
            $failures = $item->options['fail_on'] ?? [];
            return !in_array($value, $failures, true);
        }
        return $value !== null && $value !== '' && $value !== false;
    }

    protected function recomputeAggregate(ChecklistInstance $instance): void
    {
        $failed = $instance->responses()->where('passed', false)->count();
        $instance->update(['failed_items_count' => $failed]);
    }
}