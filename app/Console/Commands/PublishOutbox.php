<?php

namespace App\Console\Commands;

use App\Services\OutboxPublisher;
use Illuminate\Console\Command;

class PublishOutbox extends Command
{
    protected $signature = 'outbox:publish {--limit=100}';

    protected $description = 'Publish pending transactional outbox messages to RabbitMQ';

    public function handle(OutboxPublisher $publisher): int
    {
        $result = $publisher->publish((int) $this->option('limit'));

        if ($result['published'] > 0 || $result['failed'] > 0) {
            $this->components->info("Published: {$result['published']}; failed: {$result['failed']}");
        }

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
