<?php

namespace App\Services\Sarpras;

/**
 * Generic state-machine helper used by repair, work-order, and asset
 * status transitions. Throws IllegalStateTransitionException if a caller
 * attempts to move a model into a state it cannot legally occupy.
 */
class StateMachine
{
    /** @var array<string, array<string, array<int, string>>> */
    protected array $transitions = [];

    public function define(string $model, array $transitions): void
    {
        $this->transitions[$model] = $transitions;
    }

    public function can(string $model, string $from, string $to): bool
    {
        $map = $this->transitions[$model] ?? [];

        if (! isset($map[$from])) {
            return false;
        }

        return in_array($to, $map[$from], true);
    }

    public function assert(string $model, string $from, string $to): void
    {
        if (! $this->can($model, $from, $to)) {
            throw new IllegalStateTransitionException(
                "Illegal transition [{$model}]: {$from} -> {$to}"
            );
        }
    }

    /** All states reachable from $from for the given model. */
    public function nextStates(string $model, string $from): array
    {
        return $this->transitions[$model][$from] ?? [];
    }
}
