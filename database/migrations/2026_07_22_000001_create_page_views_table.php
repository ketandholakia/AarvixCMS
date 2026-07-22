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
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation to track views for both Posts and Pages
            $table->morphs('viewable');
            
            // Useful for tracking unique visitors vs total views
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            // Timestamp is crucial for time-series charts (30 day traffic, etc)
            $table->timestamp('created_at')->useCurrent();
            
            // Optional indexing for faster queries
            $table->index(['viewable_type', 'viewable_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
