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
        Schema::table('media', function (Blueprint $table) {
            $table->string('original_filename')->nullable();
            
            // Image specific metadata
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            
            $table->string('caption')->nullable();
            
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['original_filename', 'width', 'height', 'caption', 'uploaded_by']);
        });
    }
};
