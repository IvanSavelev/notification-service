<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Notification;

interface NotificationGatewayInterface
{
    /**
     * @return string Provider reference for tracking delivery
     *
     * @throws \App\Exceptions\TransientGatewayException
     * @throws \App\Exceptions\PermanentGatewayException
     */
    public function send(Notification $notification): string;

    /**
     * @return bool True when provider confirms delivery
     */
    public function isDelivered(string $providerReference): bool;
}
