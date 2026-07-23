<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_media_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_request_id')->nullable()->constrained('ai_requests')->nullOnDelete();
            $table->string('analysis_type')->default('vision');
            $table->string('provider');
            $table->string('model');
            $table->text('summary')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->json('tags')->nullable();
            $table->longText('ocr_text')->nullable();
            $table->json('structured_data')->nullable();
            $table->string('prompt_hash', 64)->nullable();
            $table->decimal('estimated_cost', 14, 8)->default(0);
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['media_id', 'analysis_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_media_analyses');
    }
};
