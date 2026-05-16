<?php

namespace Tests\Integration;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('notifications:ensure-queues');
    }

    protected function postBulk(array $payload, ?string $idempotencyKey = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/notifications/bulk', $payload, [
            'Idempotency-Key' => $idempotencyKey ?? Str::uuid()->toString(),
        ]);
    }

    protected function processOneQueueJob(string $queue): void
    {
        Artisan::call('rabbitmq:consume', [
            'connection' => 'rabbitmq',
            '--queue' => $queue,
            '--once' => true,
        ]);
    }

    protected function processQueuesUntilDelivered(
        string $notificationId,
        string $queue,
        int $timeoutSeconds = 20,
    ): Notification {
        $deadline = time() + $timeoutSeconds;

        while (time() <= $deadline) {
            $this->processOneQueueJob($queue);
            $this->processOneQueueJob($queue);

            $notification = Notification::query()->findOrFail($notificationId);

            if ($notification->status === NotificationStatus::Delivered) {
                return $notification;
            }

            if ($notification->status === NotificationStatus::Discarded) {
                return $notification;
            }

            usleep(250_000);
        }

        $notification = Notification::query()->findOrFail($notificationId);

        $this->fail(sprintf(
            'Timed out waiting for terminal status; last status: %s',
            $notification->status->value,
        ));
    }
}
