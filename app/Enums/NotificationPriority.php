<?php

namespace App\Enums;

enum NotificationPriority: string
{
    case Critical = 'critical';
    case Normal = 'normal';

    public function queueName(): string
    {
        return match ($this) {
            self::Critical => 'notifications-critical',
            self::Normal => 'notifications-normal',
        };
    }
}
