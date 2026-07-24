<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('name', 'Admin')->first();
        $admin->roles()->attach($adminRole);

        return $admin;
    }

    public function test_admin_can_upload_image_file_for_editor_js(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->getAdmin())->post(route('admin.upload.image'), [
            'file' => UploadedFile::fake()->image('hero-banner.jpg', 1600, 900),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonPath('file.media_id', 1);

        $this->assertDatabaseHas('media', [
            'id' => 1,
            'disk' => 'public',
            'filename' => 'hero-banner.jpg',
        ]);
    }

    public function test_admin_can_import_image_by_url_for_editor_js(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://example.com/remote-image.png' => Http::response('fake-image-content', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $response = $this->actingAs($this->getAdmin())
            ->postJson(route('admin.upload.image'), [
                'url' => 'https://example.com/remote-image.png',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonPath('file.media_id', 1);

        $this->assertDatabaseHas('media', [
            'id' => 1,
            'disk' => 'public',
            'mime_type' => 'image/png',
        ]);
    }
}
