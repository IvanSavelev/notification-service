<?php

namespace Tests\Integration;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Support\Str;

class NotificationRabbitMqFlowTest extends IntegrationTestCase
{
    public function test_normal_sms_flows_through_rabbitmq_to_delivered(): void
    {
        $subscriberId = 'integration-sms-normal-'.Str::uuid()->toString();

        $response = $this->postBulk([
            'channel' => 'sms',
            'message' => 'Integration normal SMS',
            'recipient_ids' => [$subscriberId],
            'priority' => 'normal',
        ]);

        $response->assertAccepted();
        $notificationId = $response->json('data.0.id');

        $notification = $this->processQueuesUntilDelivered(
            $notificationId,
            'notifications-normal',
        );

        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertNotNull($notification->provider_reference);
        $this->assertNotNull($notification->sent_at);
        $this->assertNotNull($notification->delivered_at);
    }

    public function test_critical_email_uses_critical_queue(): void
    {
        $subscriberId = 'integration-email-critical-'.Str::uuid()->toString();

        $response = $this->postBulk([
            'channel' => 'email',
            'message' => 'Integration critical email',
            'recipient_ids' => [$subscriberId],
            'priority' => 'critical',
        ]);

        $response->assertAccepted();
        $notificationId = $response->json('data.0.id');

        $notification = $this->processQueuesUntilDelivered(
            $notificationId,
            'notifications-critical',
        );

        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertStringStartsWith('email-', $notification->provider_reference);
    }

    public function test_idempotency_with_redis_and_database(): void
    {
        $key = 'integration-idempotency-'.Str::uuid()->toString();
        $payload = [
            'channel' => 'sms',
            'message' => 'Idempotent integration',
            'recipient_ids' => ['integration-idempotent-user'],
            'priority' => 'normal',
        ];

        $firstId = $this->postBulk($payload, $key)
            ->assertAccepted()
            ->json('data.0.id');

        $secondId = $this->postBulk($payload, $key)
            ->assertAccepted()
            ->json('data.0.id');

        $this->assertSame($firstId, $secondId);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_invalid_subscriber_is_discarded_via_worker(): void
    {
        $subscriberId = 'invalid-integration-'.Str::uuid()->toString();

        $response = $this->postBulk([
            'channel' => 'sms',
            'message' => 'Should be discarded',
            'recipient_ids' => [$subscriberId],
            'priority' => 'normal',
        ]);

        $response->assertAccepted();
        $notificationId = $response->json('data.0.id');

        $notification = $this->processQueuesUntilDelivered(
            $notificationId,
            'notifications-normal',
        );

        $this->assertSame(NotificationStatus::Discarded, $notification->status);
        $this->assertNotNull($notification->discarded_at);
    }
}
