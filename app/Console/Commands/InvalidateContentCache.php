<?php

namespace App\Console\Commands;

use App\Services\ContentCache;
use Illuminate\Console\Command;

class InvalidateContentCache extends Command
{
    protected $signature = 'cache:content-invalidate {domain=all}';

    protected $description = 'Invalidate product and post content cache versions';

    public function handle(ContentCache $cache): int
    {
        $domain = (string) $this->argument('domain');

        if (! in_array($domain, ['all', 'products', 'posts'], true)) {
            $this->components->error('Domain must be all, products, or posts.');

            return self::INVALID;
        }

        $success = match ($domain) {
            'products' => $cache->invalidateProducts(),
            'posts' => $cache->invalidatePosts(),
            'all' => $this->invalidateAll($cache),
        };

        if (! $success) {
            $this->components->error('Content cache invalidation failed.');

            return self::FAILURE;
        }

        $this->components->info("Content cache invalidated for {$domain}.");

        return self::SUCCESS;
    }

    private function invalidateAll(ContentCache $cache): bool
    {
        $productsInvalidated = $cache->invalidateProducts();
        $postsInvalidated = $cache->invalidatePosts();

        return $productsInvalidated && $postsInvalidated;
    }
}
