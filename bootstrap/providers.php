<?php

use App\Providers\AppServiceProvider;
use VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider;

return [
    AppServiceProvider::class,
    LaravelQueueRabbitMQServiceProvider::class,
];
