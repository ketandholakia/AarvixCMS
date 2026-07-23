<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('chunk_index')->default(0);
            $table->string('chunk_hash', 64);
            $table->longText('content_text');
            $table->json('metadata')->nullable();
            $table->string('vector_store')->nullable();
            $table->string('vector_id')->nullable();
            $table->string('visibility')->default('private');
            $table->string('embedding_model')->nullable();
            $table->string('chunker_version')->default('1');
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'content_embeddings_source');
            $table->index(['visibility', 'vector_store']);
            $table->unique(['source_type', 'source_id', 'chunk_index', 'chunk_hash'], 'content_embeddings_chunk');
        });

        Schema::create('ai_embedding_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_uuid')->unique();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('source_hash', 64)->nullable();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error_class')->nullable();
            $table->text('last_error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['status', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_embedding_jobs');
        Schema::dropIfExists('content_embeddings');
    }
};
