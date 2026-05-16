<?php

declare(strict_types=1);

namespace App\Gateways;

use App\Contracts\NotificationGatewayInterface;
use App\Exceptions\PermanentGatewayException;
use App\Exceptions\TransientGatewayException;
use App\Models\Notification;
use Illuminate\Support\Str;

final class SmsGatewayMock implements NotificationGatewayInterface
{
    public function send(Notification $notification): string
    {
        $subscriberId = $notification->subscriber_id;

        if (str_starts_with($subscriberId, 'invalid-')) {
            throw new PermanentGatewayException('Invalid phone number');
        }

        if (str_starts_with($subscriberId, 'transient-')) {
            throw new TransientGatewayException('SMS gateway temporarily unavailable');
        }

        return 'sms-' . Str::uuid()->toString();
    }

    public function isDelivered(string $providerReference): bool
    {
        return ! str_contains($providerReference, 'undelivered');
    }
}
