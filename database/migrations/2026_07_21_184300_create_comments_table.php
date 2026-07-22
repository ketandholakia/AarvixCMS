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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable'); // connects to Post, Page, etc.
            
            // Nested reply structure
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            
            // User who made the comment
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); 
            
            // Guest comment details (if user_id is null)
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_url')->nullable();
            
            $table->text('body');
            
            // Moderation status
            $table->enum('status', ['pending', 'approved', 'spam', 'trash'])->default('pending');
            
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
