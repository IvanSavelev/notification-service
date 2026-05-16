<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\NotificationSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 15, 30, 60, 120];

    public function __construct(
        public readonly string $notificationId,
    ) {
    }

    public function handle(NotificationSender $sender): void
    {
        $sender->send($this->notificationId);
    }
}
