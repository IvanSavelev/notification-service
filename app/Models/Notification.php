<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'bulk_dispatch_id',
        'subscriber_id',
        'channel',
        'priority',
        'message',
        'status',
        'attempts',
        'provider_reference',
        'error_message',
        'sent_at',
        'delivered_at',
        'discarded_at',
    ];

    public function bulkDispatch(): BelongsTo
    {
        return $this->belongsTo(BulkDispatch::class);
    }

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'discarded_at' => 'datetime',
        ];
    }
}
