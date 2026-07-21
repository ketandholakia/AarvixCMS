<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('featured_image_id')
                ->nullable()
                ->after('category_id')
                ->constrained('media')
                ->nullOnDelete();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('featured_image_id')
                ->nullable()
                ->after('author_id')
                ->constrained('media')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['featured_image_id']);
            $table->dropColumn('featured_image_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['featured_image_id']);
            $table->dropColumn('featured_image_id');
        });
    }
};
