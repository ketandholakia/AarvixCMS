<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberExportTest extends TestCase
{
    use RefreshDatabase;

    protected function getAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->first());

        return $admin;
    }

    public function test_admin_can_export_subscribers_as_csv(): void
    {
        Subscriber::create([
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'subscribed',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->getAdmin())
            ->get(route('admin.subscribers.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
