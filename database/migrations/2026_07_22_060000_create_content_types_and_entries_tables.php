<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // also the URL prefix
            $table->enum('context', ['post', 'page'])->default('post');
            $table->string('icon')->nullable(); // e.g. 'briefcase', 'calendar'
            $table->string('description')->nullable();
            $table->json('fields_schema')->nullable(); // array of custom field definitions
            $table->boolean('is_system')->default(false); // system types cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['context', 'is_active']);
        });

        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_type_id')->constrained('content_types')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('body')->nullable(); // Editor.js JSON
            $table->json('custom_fields')->nullable(); // values for fields_schema
            $table->string('status', 20)->default('draft'); // draft, published, archived
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('template')->nullable(); // for page-context types
            $table->timestamps();
            $table->softDeletes();

            // Slugs are unique per type, not globally
            $table->unique(['content_type_id', 'slug']);
            $table->index(['content_type_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
        Schema::dropIfExists('content_types');
    }
};
