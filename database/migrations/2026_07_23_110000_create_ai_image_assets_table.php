<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_image_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->unique()->constrained('media')->cascadeOnDelete();
            $table->foreignId('source_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('ai_request_id')->nullable()->constrained('ai_requests')->nullOnDelete();
            $table->string('provider')->index();
            $table->string('model')->index();
            $table->string('operation')->index();
            $table->string('prompt_hash', 64)->index();
            $table->string('resolution')->nullable()->index();
            $table->unsignedBigInteger('seed')->nullable()->index();
            $table->decimal('estimated_cost', 14, 8)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'model', 'operation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_image_assets');
    }
};
