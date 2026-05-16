<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('bulk_dispatches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 128)->unique();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('bulk_dispatch_id')->constrained('bulk_dispatches')->cascadeOnDelete();
            $table->string('subscriber_id', 64);
            $table->string('channel', 16);
            $table->string('priority', 16);
            $table->text('message');
            $table->string('status', 16)->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('provider_reference')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('discarded_at')->nullable();
            $table->timestamps();

            $table->index(['subscriber_id', 'created_at']);
            $table->index('status');
            $table->unique(['bulk_dispatch_id', 'subscriber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('bulk_dispatches');
    }
};
