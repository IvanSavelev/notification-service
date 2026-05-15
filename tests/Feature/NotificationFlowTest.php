<?php

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use App\Jobs\ConfirmDeliveryJob;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_dispatch_creates_queued_notifications(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Your code is 1234',
            'recipient_ids' => ['user-1', 'user-2'],
            'priority' => 'normal',
        ], [
            'Idempotency-Key' => Str::uuid()->toString(),
        ]);

        $response->assertAccepted()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'subscriber_id' => 'user-1',
            'status' => NotificationStatus::Queued->value,
        ]);

        Queue::assertPushed(SendNotificationJob::class, 2);
        Queue::assertPushed(SendNotificationJob::class, fn (SendNotificationJob $job) => $job->queue === 'notifications-normal');
    }

    public function test_critical_notifications_use_critical_queue(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'email',
            'message' => 'Urgent route change',
            'recipient_ids' => ['user-99'],
            'priority' => 'critical',
        ], [
            'Idempotency-Key' => Str::uuid()->toString(),
        ])->assertAccepted();

        Queue::assertPushed(SendNotificationJob::class, fn (SendNotificationJob $job) => $job->queue === 'notifications-critical');
    }

    public function test_full_delivery_chain_updates_statuses_and_calls_gateway(): void
    {
        Queue::fake();

        $notification = $this->createNotificationViaApi('subscriber-ok');

        $this->assertSame(NotificationStatus::Queued, $notification->status);

        (new SendNotificationJob($notification->id))->handle(app(\App\Services\NotificationSender::class));

        $notification->refresh();
        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertNotNull($notification->provider_reference);
        $this->assertStringStartsWith('sms-', $notification->provider_reference);

        (new ConfirmDeliveryJob($notification->id))->handle(app(\App\Services\NotificationSender::class));

        $notification->refresh();
        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertNotNull($notification->delivered_at);
    }

    public function test_invalid_subscriber_is_discarded(): void
    {
        Queue::fake();

        $notification = $this->createNotificationViaApi('invalid-phone');

        (new SendNotificationJob($notification->id))->handle(app(\App\Services\NotificationSender::class));

        $notification->refresh();
        $this->assertSame(NotificationStatus::Discarded, $notification->status);
        $this->assertNotNull($notification->discarded_at);
    }

    public function test_idempotency_prevents_duplicate_notifications(): void
    {
        Queue::fake();

        $key = Str::uuid()->toString();
        $payload = [
            'channel' => 'sms',
            'message' => 'Promo',
            'recipient_ids' => ['user-a'],
        ];

        $first = $this->postJson('/api/v1/notifications/bulk', $payload, ['Idempotency-Key' => $key])
            ->assertAccepted()
            ->json('data.0.id');

        $second = $this->postJson('/api/v1/notifications/bulk', $payload, ['Idempotency-Key' => $key])
            ->assertAccepted()
            ->json('data.0.id');

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('notifications', 1);
        Queue::assertPushed(SendNotificationJob::class, 1);
    }

    public function test_subscriber_can_list_notification_history(): void
    {
        $this->createNotificationViaApi('history-user');
        $this->createNotificationViaApi('history-user');

        $response = $this->getJson('/api/v1/subscribers/history-user/notifications');

        $response->assertOk()
            ->assertJsonPath('subscriber_id', 'history-user')
            ->assertJsonCount(2, 'data');
    }

    public function test_bulk_requires_idempotency_key(): void
    {
        $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Hi',
            'recipient_ids' => ['x'],
        ])->assertStatus(422);
    }

    private function createNotificationViaApi(string $subscriberId): Notification
    {
        $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Test message',
            'recipient_ids' => [$subscriberId],
            'priority' => 'normal',
        ], [
            'Idempotency-Key' => Str::uuid()->toString(),
        ])->assertAccepted();

        return Notification::query()->where('subscriber_id', $subscriberId)->firstOrFail();
    }
}
