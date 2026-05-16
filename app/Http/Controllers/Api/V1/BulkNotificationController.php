<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\BulkNotificationService;
use Illuminate\Http\JsonResponse;

final class BulkNotificationController extends Controller
{
    public function __construct(
        private readonly BulkNotificationService $bulkNotificationService,
    ) {
    }

    public function store(BulkNotificationRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey === null || $idempotencyKey === '') {
            return response()->json([
                'message' => 'Idempotency-Key header is required',
            ], 422);
        }

        $notifications = $this->bulkNotificationService->dispatch(
            idempotencyKey: $idempotencyKey,
            channel: $request->enum('channel', NotificationChannel::class),
            message: $request->string('message')->toString(),
            recipientIds: $request->input('recipient_ids'),
            priority: $request->priority(),
        );

        return response()->json([
            'data' => NotificationResource::collection($notifications),
        ], 202);
    }
}
