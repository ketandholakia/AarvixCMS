<?php

namespace Tests\Feature\Admin;

use App\Models\AiImageAsset;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_admin_can_see_ai_image_metadata_in_the_media_library(): void
    {
        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/generated.webp',
            'filename' => 'generated.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'alt_text' => 'Generated image',
            'caption' => 'Generated image caption',
        ]);

        AiImageAsset::create([
            'media_id' => $media->id,
            'provider' => 'fake',
            'model' => 'fake-image',
            'operation' => 'generate',
            'alt_text' => 'Generated image',
            'caption' => 'Generated image caption',
            'tags' => ['ai', 'generated'],
            'ocr_text' => 'Visible text from the generated image.',
            'prompt_hash' => hash('sha256', 'prompt body'),
            'moderation_status' => 'approved',
            'estimated_cost' => '0.00000000',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.media.index'));

        $response->assertStatus(200);
        $response->assertSee('AI image');
        $response->assertSee('generate');
        $response->assertSee('approved');
        $response->assertSee('ai, generated');
        $response->assertSee('Visible text from the generated image.');
    }

    public function test_admin_can_open_a_media_detail_page_with_ai_provenance(): void
    {
        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/generated-detail.webp',
            'filename' => 'generated-detail.webp',
            'mime_type' => 'image/webp',
            'size' => 2048,
            'alt_text' => 'Detail image',
            'caption' => 'Detail caption',
        ]);

        AiImageAsset::create([
            'media_id' => $media->id,
            'provider' => 'fake',
            'model' => 'fake-image',
            'operation' => 'edit',
            'alt_text' => 'Detail image',
            'caption' => 'Detail caption',
            'tags' => ['detail', 'ai'],
            'ocr_text' => 'Detail OCR text.',
            'prompt_hash' => hash('sha256', 'detail prompt'),
            'moderation_status' => 'pending',
            'estimated_cost' => '0.00000000',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.media.show', $media));

        $response->assertStatus(200);
        $response->assertSee('Media Details');
        $response->assertSee('AI Provenance');
        $response->assertSee('fake-image');
        $response->assertSee('edit');
        $response->assertSee('pending');
        $response->assertSee('detail, ai');
        $response->assertSee('Detail OCR text.');
        $response->assertSee(hash('sha256', 'detail prompt'));
    }
}
