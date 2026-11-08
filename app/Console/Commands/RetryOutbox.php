<?php

namespace App\Console\Commands;

use App\Models\OutboxMessage;
use Illuminate\Console\Command;

class RetryOutbox extends Command
{
    protected $signature = 'outbox:retry {id}';

    protected $description = 'Make a permanently failed outbox message available for publication again';

    public function handle(): int
    {
        $message = OutboxMessage::query()->whereKey($this->argument('id'))->first();

        if (! $message || ! $message->failed_at || $message->published_at) {
            $this->components->error('A failed unpublished outbox message with this ID was not found.');

            return self::FAILURE;
        }

        $message->update([
            'attempts' => 0,
            'available_at' => now(),
            'claimed_at' => null,
            'claim_token' => null,
            'failed_at' => null,
            'last_error' => null,
        ]);

        $this->components->info("Outbox message {$message->id} is ready for publication.");

        return self::SUCCESS;
    }
}
