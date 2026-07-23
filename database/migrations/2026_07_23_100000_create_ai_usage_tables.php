<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature')->index();
            $table->string('status')->index();
            $table->string('provider')->index();
            $table->string('model')->index();
            $table->string('prompt_key')->nullable()->index();
            $table->json('scope')->nullable();
            $table->json('request_metadata')->nullable();
            $table->json('response_metadata')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('estimated_cost', 14, 8)->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'feature', 'status']);
            $table->index(['provider', 'model', 'status']);
        });

        Schema::create('ai_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->date('usage_date');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature')->nullable()->index();
            $table->string('provider')->nullable()->index();
            $table->string('model')->nullable()->index();
            $table->unsignedInteger('requests_count')->default(0);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('estimated_cost', 14, 8)->default(0);
            $table->timestamps();

            $table->unique(['usage_date', 'user_id', 'feature', 'provider', 'model'], 'ai_usage_daily_bucket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_daily');
        Schema::dropIfExists('ai_requests');
    }
};
