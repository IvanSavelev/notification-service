<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Exceptions\PermanentGatewayException;
use App\Exceptions\TransientGatewayException;
use App\Jobs\ConfirmDeliveryJob;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

final class NotificationSender
{
    public function __construct(
        private readonly GatewayResolver $gatewayResolver,
    ) {
    }

    public function send(string $notificationId): void
    {
        $notification = Notification::query()->find($notificationId);

        if ($notification === null) {
            return;
        }

        if ($notification->status !== NotificationStatus::Queued) {
            return;
        }

        $claimed = DB::table('notifications')
            ->where('id', $notificationId)
            ->where('status', NotificationStatus::Queued->value)
            ->update([
                'attempts' => DB::raw('attempts + 1'),
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            return;
        }

        $notification->refresh();

        try {
            $gateway = $this->gatewayResolver->resolve($notification->channel);
            $reference = $gateway->send($notification);

            $notification->update([
                'status' => NotificationStatus::Sent,
                'provider_reference' => $reference,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            ConfirmDeliveryJob::dispatch($notificationId)
                ->onQueue($notification->priority->queueName())
                ->delay(now()->addSecond());
        } catch (TransientGatewayException $e) {
            $notification->update([
                'status' => NotificationStatus::Queued,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        } catch (PermanentGatewayException $e) {
            $notification->update([
                'status' => NotificationStatus::Discarded,
                'error_message' => $e->getMessage(),
                'discarded_at' => now(),
            ]);
        }
    }

    public function confirmDelivery(string $notificationId): void
    {
        $notification = Notification::query()->find($notificationId);

        if ($notification === null || $notification->status !== NotificationStatus::Sent) {
            return;
        }

        $gateway = $this->gatewayResolver->resolve($notification->channel);
        $reference = $notification->provider_reference;

        if ($reference === null) {
            return;
        }

        if ($gateway->isDelivered($reference)) {
            $notification->update([
                'status' => NotificationStatus::Delivered,
                'delivered_at' => now(),
            ]);

            return;
        }

        $notification->update([
            'status' => NotificationStatus::Discarded,
            'error_message' => 'Delivery not confirmed by provider',
            'discarded_at' => now(),
        ]);
    }
}
