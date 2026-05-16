<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Connectors\RabbitMQConnector;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class EnsureRabbitQueuesCommand extends Command
{
    protected $signature = 'notifications:ensure-queues {connection=rabbitmq}';

    protected $description = 'Declare notification queues in RabbitMQ (idempotent, no passive checks)';

    public function handle(RabbitMQConnector $connector): int
    {
        $config = $this->laravel['config']->get('queue.connections.'.$this->argument('connection'));

        /** @var RabbitMQQueue $queue */
        $queue = $connector->connect($config);

        $arguments = [];
        $maxPriority = (int) ($config['options']['queue']['queue_max_priority'] ?? 0);
        if ($maxPriority > 0) {
            $arguments['x-max-priority'] = $maxPriority;
        }

        foreach (['notifications-critical', 'notifications-normal'] as $name) {
            $queue->declareQueue($name, true, false, $arguments);
            $this->info("Queue [{$name}] ready.");
        }

        return self::SUCCESS;
    }
}
