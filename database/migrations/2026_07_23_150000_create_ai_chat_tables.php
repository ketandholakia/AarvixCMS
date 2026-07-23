<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('conversation_uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('scope')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('active');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->json('model_settings')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_message_at']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role');
            $table->longText('content');
            $table->json('citations')->nullable();
            $table->json('usage')->nullable();
            $table->json('tool_calls')->nullable();
            $table->string('moderation_state')->default('not_reviewed');
            $table->string('provider_request_id')->nullable();
            $table->uuid('request_uuid')->nullable();
            $table->unsignedInteger('message_order')->default(0);
            $table->timestamps();

            $table->index(['conversation_id', 'message_order']);
            $table->index(['conversation_id', 'role']);
            $table->index(['request_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
