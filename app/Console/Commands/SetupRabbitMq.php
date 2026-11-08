<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class SetupRabbitMq extends Command
{
    protected $signature = 'rabbitmq:setup';

    protected $description = 'Declare durable RabbitMQ queues and dead-letter queues';

    public function handle(): int
    {
        $connection = Queue::connection('rabbitmq');

        foreach (['orders.process', 'notifications.email', 'delivery.submit'] as $queue) {
            $connection->declareQueue($queue.'.failed', true, false, ['x-queue-type' => 'quorum']);
            $connection->declareQueue($queue, true, false, [
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $queue.'.failed',
                'x-queue-type' => 'quorum',
            ]);
        }

        $this->components->info('RabbitMQ topology is ready.');

        return self::SUCCESS;
    }
}
