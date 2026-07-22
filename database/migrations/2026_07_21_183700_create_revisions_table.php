<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable'); // Connects to post, page, etc.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // User who made the change
            $table->json('before_attributes')->nullable(); // State before
            $table->json('after_attributes')->nullable();  // State after
            $table->string('event')->default('updated'); // created, updated, restored
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
