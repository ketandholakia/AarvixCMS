<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_image_assets', function (Blueprint $table) {
            $table->string('moderation_status')->default('approved')->after('seed');
            $table->timestamp('moderation_reviewed_at')->nullable()->after('moderation_status');
            $table->timestamp('retention_expires_at')->nullable()->index()->after('moderation_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_image_assets', function (Blueprint $table) {
            $table->dropIndex(['retention_expires_at']);
            $table->dropColumn([
                'moderation_status',
                'moderation_reviewed_at',
                'retention_expires_at',
            ]);
        });
    }
};
