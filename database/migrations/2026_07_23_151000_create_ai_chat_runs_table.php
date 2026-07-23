<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('retry_of_id')->nullable();
            $table->uuid('request_uuid')->unique();
            $table->string('mode')->default('knowledge');
            $table->string('status')->default('pending');
            $table->longText('question');
            $table->json('options')->nullable();
            $table->json('context')->nullable();
            $table->longText('response_text')->nullable();
            $table->json('response_metadata')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['conversation_id', 'status']);
            $table->index(['conversation_id', 'created_at']);
            $table->index(['retry_of_id']);
            $table->index(['status', 'started_at']);
        });

        Schema::table('ai_chat_runs', function (Blueprint $table) {
            $table->foreign('retry_of_id')->references('id')->on('ai_chat_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_runs');
    }
};
