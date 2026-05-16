<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\BulkDispatch;
use App\Models\Notification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BulkNotificationService
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
    ) {
    }

    /**
     * @param  list<string>  $recipientIds
     * @return Collection<int, Notification>
     */
    public function dispatch(
        string $idempotencyKey,
        NotificationChannel $channel,
        string $message,
        array $recipientIds,
        NotificationPriority $priority,
    ): Collection {
        $existing = $this->findExistingBulk($idempotencyKey);

        if ($existing !== null) {
            return $existing;
        }

        if (! $this->idempotency->acquire($idempotencyKey)) {
            $existing = $this->findExistingBulk($idempotencyKey);

            return $existing ?? collect();
        }

        try {
            return DB::transaction(function () use (
                $idempotencyKey,
                $channel,
                $message,
                $recipientIds,
                $priority,
            ) {
                $bulk = BulkDispatch::query()->create([
                    'idempotency_key' => $idempotencyKey,
                ]);

                $created = collect();

                foreach ($recipientIds as $subscriberId) {
                    $notification = Notification::query()->create([
                        'bulk_dispatch_id' => $bulk->id,
                        'subscriber_id' => $subscriberId,
                        'channel' => $channel,
                        'priority' => $priority,
                        'message' => $message,
                        'status' => NotificationStatus::Queued,
                    ]);

                    SendNotificationJob::dispatch($notification->id)
                        ->onQueue($priority->queueName());

                    $created->push($notification);
                }

                return $created;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->findExistingBulk($idempotencyKey) ?? collect();
        }
    }

    /**
     * @return Collection<int, Notification>|null
     */
    private function findExistingBulk(string $idempotencyKey): ?Collection
    {
        $bulk = BulkDispatch::query()
            ->where('idempotency_key', $idempotencyKey)
            ->with('notifications')
            ->first();

        return $bulk?->notifications;
    }
}
