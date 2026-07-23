<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('driver');
            $table->json('capabilities')->nullable();
            $table->text('credentials')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('inactive');
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'is_default']);
        });

        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('name');
            $table->string('capability')->index();
            $table->unsignedInteger('context_window')->nullable();
            $table->unsignedInteger('max_output_tokens')->nullable();
            $table->decimal('prompt_token_cost', 14, 8)->default(0);
            $table->decimal('completion_token_cost', 14, 8)->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['ai_provider_id', 'name']);
            $table->index(['ai_provider_id', 'capability', 'is_enabled']);
        });

        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('prompt_key')->unique();
            $table->string('category')->index();
            $table->string('title');
            $table->string('description')->nullable();
            $table->unsignedInteger('active_version_number')->default(1);
            $table->json('output_schema')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_prompt_id')->constrained('ai_prompts')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->longText('system_template');
            $table->longText('user_template')->nullable();
            $table->json('variables')->nullable();
            $table->json('output_schema')->nullable();
            $table->text('change_summary')->nullable();
            $table->timestamps();

            $table->unique(['ai_prompt_id', 'version_number']);
            $table->index(['ai_prompt_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_versions');
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('ai_providers');
    }
};
