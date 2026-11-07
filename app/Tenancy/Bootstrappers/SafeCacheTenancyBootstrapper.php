<?php

namespace App\Tenancy\Bootstrappers;

use Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper;

/**
 * Vendor revert() always re-extends the 'cache' binding with $originalCache,
 * which is null when bootstrap never ran (or already reverted). In a queue
 * worker that nulls app['cache'] for good ("store() on null" in
 * QueueManager::getPausedQueues). Only restore a real, captured cache manager.
 */
class SafeCacheTenancyBootstrapper extends CacheTenancyBootstrapper
{
    public function revert()
    {
        if ($this->originalCache === null) {
            $this->resetFacadeCache();

            return;
        }

        parent::revert();
    }
}
