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
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            
            // Double opt-in status
            $table->enum('status', ['pending', 'subscribed', 'unsubscribed'])->default('subscribed');
            $table->string('token')->nullable(); // For opt-in/unsubscribe links
            
            // IP and source for compliance
            $table->ipAddress('ip_address')->nullable();
            $table->string('source')->nullable(); // e.g. "footer_form", "checkout"
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
