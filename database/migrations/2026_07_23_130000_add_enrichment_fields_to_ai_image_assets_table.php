<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_image_assets', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->after('operation');
            $table->text('caption')->nullable()->after('alt_text');
            $table->json('tags')->nullable()->after('caption');
            $table->longText('ocr_text')->nullable()->after('tags');
        });
    }

    public function down(): void
    {
        Schema::table('ai_image_assets', function (Blueprint $table) {
            $table->dropColumn(['alt_text', 'caption', 'tags', 'ocr_text']);
        });
    }
};
