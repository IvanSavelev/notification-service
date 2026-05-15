<?php

namespace App\Jobs;

use App\Services\NotificationSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConfirmDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $notificationId,
    ) {}

    public function handle(NotificationSender $sender): void
    {
        $sender->confirmDelivery($this->notificationId);
    }
}
