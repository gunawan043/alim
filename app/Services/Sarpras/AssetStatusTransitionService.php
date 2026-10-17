<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Services\SarprasCacheInvalidator;

class AssetStatusTransitionService
{
    public function __construct(
        protected StateMachine $stateMachine,
        protected AssetEventLogger $eventLogger,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {
        $this->stateMachine->define(
            StateMachineRegistry::ASSET_STATUS,
            StateMachineRegistry::ASSET_STATUS_TRANSITIONS
        );
    }

    /**
     * Transition an asset to a new status.
     * Throws IllegalStateTransitionException if the transition is not allowed.
     */
    public function transition(
        Asset $asset,
        string $target,
        ?int $actorId = null,
        string $reason = ''
    ): Asset {
        $from = $asset->status ?: 'active';

        if ($from === $target) {
            return $asset;
        }

        $this->stateMachine->assert(
            StateMachineRegistry::ASSET_STATUS,
            $from,
            $target
        );

        $asset->update(['status' => $target]);
        $this->eventLogger->logAssetStatusChanged(
            $asset,
            $from,
            $target,
            $reason ?: "status changed from {$from} to {$target}"
        );
        $this->cacheInvalidator->invalidateAsset($asset);

        return $asset->fresh();
    }

    public function canTransition(Asset $asset, string $target): bool
    {
        $from = $asset->status ?: 'active';

        return $this->stateMachine->can(
            StateMachineRegistry::ASSET_STATUS,
            $from,
            $target
        );
    }

    /**
     * Bypass validation — only use for creation or imports.
     */
    public function set(Asset $asset, string $status, ?int $actorId = null): Asset
    {
        $asset->update(['status' => $status]);
        $this->cacheInvalidator->invalidateAsset($asset);

        return $asset->fresh();
    }
}
