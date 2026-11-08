<?php

namespace App\Queue;

use RuntimeException;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class ConfirmedRabbitMQQueue extends RabbitMQQueue
{
    private ?int $confirmedChannelId = null;

    protected function publishBasic($msg, $exchange = '', $destination = '', $mandatory = false, $immediate = false, $ticket = null): void
    {
        $channel = $this->getChannel();

        if ($this->confirmedChannelId !== spl_object_id($channel)) {
            $channel->confirm_select();
            $this->confirmedChannelId = spl_object_id($channel);
        }

        $returned = null;
        $nacked = false;
        $channel->set_return_listener(function (int $code, string $text, string $returnedExchange, string $routingKey) use (&$returned) {
            $returned = compact('code', 'text', 'returnedExchange', 'routingKey');
        });
        $channel->set_nack_handler(function () use (&$nacked) {
            $nacked = true;
        });
        $channel->basic_publish($msg, $exchange, $destination, $mandatory, $immediate, $ticket);
        $channel->wait_for_pending_acks_returns((float) config('queue.connections.rabbitmq.publisher_confirm_timeout', 5));

        if ($nacked) {
            throw new RuntimeException('RabbitMQ rejected the published message.');
        }

        if ($returned) {
            throw new RuntimeException("RabbitMQ could not route the message to {$returned['routingKey']}: {$returned['text']}");
        }
    }
}
