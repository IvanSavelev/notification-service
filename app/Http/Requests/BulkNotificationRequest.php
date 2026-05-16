<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BulkNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::enum(NotificationChannel::class)],
            'message' => ['required', 'string', 'min:1', 'max:5000'],
            'recipient_ids' => ['required', 'array', 'min:1', 'max:10000'],
            'recipient_ids.*' => ['required', 'string', 'max:64'],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
        ];
    }

    public function priority(): NotificationPriority
    {
        $priority = $this->enum('priority', NotificationPriority::class);

        return $priority ?? NotificationPriority::Normal;
    }
}
