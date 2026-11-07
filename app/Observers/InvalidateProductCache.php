<?php

namespace App\Observers;

use App\Services\ContentCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

class InvalidateProductCache implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly ContentCache $cache) {}

    public function saved(Model $model): void
    {
        $this->cache->invalidateProducts();
    }

    public function deleted(Model $model): void
    {
        $this->cache->invalidateProducts();
    }

    public function restored(Model $model): void
    {
        $this->cache->invalidateProducts();
    }

    public function forceDeleted(Model $model): void
    {
        $this->cache->invalidateProducts();
    }
}
