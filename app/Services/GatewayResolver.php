<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\NotificationGatewayInterface;
use App\Enums\NotificationChannel;
use App\Gateways\EmailGatewayMock;
use App\Gateways\SmsGatewayMock;
use InvalidArgumentException;

final class GatewayResolver
{
    public function resolve(NotificationChannel $channel): NotificationGatewayInterface
    {
        return match ($channel) {
            NotificationChannel::Sms => app(SmsGatewayMock::class),
            NotificationChannel::Email => app(EmailGatewayMock::class),
            default => throw new InvalidArgumentException("Unsupported channel: {$channel->value}"),
        };
    }
}
