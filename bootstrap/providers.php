<?php

use App\Providers\AppServiceProvider;
use App\Providers\VoltServiceProvider;
use VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider;

return [
    AppServiceProvider::class,
    VoltServiceProvider::class,
    LaravelQueueRabbitMQServiceProvider::class,
];
