<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationPriority: string
{
    public function queueName(): string
    {
        return match ($this) {
            self::Critical => 'notifications-critical',
            self::Normal => 'notifications-normal',
        };
    }
    case Critical = 'critical';
    case Normal = 'normal';
}
