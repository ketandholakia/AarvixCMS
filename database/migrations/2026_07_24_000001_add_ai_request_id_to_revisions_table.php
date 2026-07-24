<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revisions', function (Blueprint $table): void {
            $table->foreignId('ai_request_id')
                ->nullable()
                ->after('user_id')
                ->constrained('ai_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('revisions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ai_request_id');
        });
    }
};
