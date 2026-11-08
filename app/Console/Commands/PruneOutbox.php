<?php

namespace App\Console\Commands;

use App\Services\OutboxPruner;
use Illuminate\Console\Command;

class PruneOutbox extends Command
{
    protected $signature = 'outbox:prune {--days=30}';

    protected $description = 'Remove old outbox messages after their consumers have processed them';

    public function handle(OutboxPruner $pruner): int
    {
        $result = $pruner->prune((int) $this->option('days'));
        $this->components->info("Pruned outbox messages: {$result['messages']}; processed records: {$result['processed']}");

        return self::SUCCESS;
    }
}
