<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tools', function (Blueprint $table) {
            $table->id();
            $table->uuid('tool_uuid')->unique();
            $table->string('key')->unique();
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('handler')->nullable();
            $table->string('required_permission')->nullable();
            $table->string('confirmation_policy')->default('never');
            $table->string('risk_classification')->default('read');
            $table->json('input_schema')->nullable();
            $table->json('output_schema')->nullable();
            $table->json('configuration')->nullable();
            $table->unsignedInteger('timeout_seconds')->default(30);
            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->string('audit_redaction_policy')->default('minimal');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_enabled']);
            $table->index(['required_permission', 'is_enabled']);
        });

        Schema::create('ai_tool_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('ai_tools')->cascadeOnDelete();
            $table->uuid('call_uuid')->unique();
            $table->string('request_uuid')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('approval_state')->default('not_required');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('input_payload')->nullable();
            $table->json('result_summary')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['request_uuid', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index(['tool_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_calls');
        Schema::dropIfExists('ai_tools');
    }
};
