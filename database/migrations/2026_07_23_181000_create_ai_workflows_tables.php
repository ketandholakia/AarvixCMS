<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_workflows', function (Blueprint $table) {
            $table->id();
            $table->uuid('workflow_uuid')->unique();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('trigger');
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('enabled');
            $table->json('conditions')->nullable();
            $table->json('steps')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['trigger', 'status']);
        });

        Schema::create('ai_workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('ai_workflows')->cascadeOnDelete();
            $table->uuid('run_uuid')->unique();
            $table->string('idempotency_key')->unique();
            $table->string('trigger');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('queued');
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->json('review_task')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['workflow_id', 'status']);
            $table->index(['trigger', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_workflow_runs');
        Schema::dropIfExists('ai_workflows');
    }
};
