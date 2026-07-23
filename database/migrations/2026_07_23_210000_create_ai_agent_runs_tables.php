<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_uuid')->unique();
            $table->string('agent_key')->index();
            $table->unsignedInteger('agent_version')->default(1);
            $table->string('agent_name');
            $table->string('status')->index();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->uuid('request_uuid')->nullable()->index();
            $table->string('prompt_key')->nullable()->index();
            $table->json('context')->nullable();
            $table->json('plan')->nullable();
            $table->unsignedInteger('steps_planned')->default(0);
            $table->unsignedInteger('steps_completed')->default(0);
            $table->unsignedInteger('estimated_tokens')->default(0);
            $table->decimal('estimated_cost', 14, 8)->default(0);
            $table->json('result')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('halted_at')->nullable();
            $table->timestamps();

            $table->index(['agent_key', 'status']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('ai_agent_run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_run_id')->constrained('ai_agent_runs')->cascadeOnDelete();
            $table->unsignedInteger('step_index');
            $table->string('tool_key');
            $table->string('status')->index();
            $table->string('approval_state')->nullable();
            $table->foreignId('ai_tool_call_id')->nullable()->constrained('ai_tool_calls')->nullOnDelete();
            $table->json('input_payload')->nullable();
            $table->json('result_payload')->nullable();
            $table->unsignedInteger('estimated_tokens')->default(0);
            $table->decimal('estimated_cost', 14, 8)->default(0);
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['agent_run_id', 'step_index']);
            $table->index(['tool_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_run_steps');
        Schema::dropIfExists('ai_agent_runs');
    }
};
