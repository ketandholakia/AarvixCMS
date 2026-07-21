<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_user_can_have_roles_and_permissions(): void
    {
        $role = \App\Models\Role::create(['name' => 'Admin']);
        $permission = \App\Models\Permission::create(['name' => 'view_posts']);
        $role->permissions()->attach($permission);

        $user = \App\Models\User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole('Admin'));
        $this->assertFalse($user->hasRole('Editor'));
        
        $this->assertTrue($user->hasPermission('view_posts'));
        $this->assertFalse($user->hasPermission('delete_posts'));
    }

    public function test_authorize_admin_middleware(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => false]);

        $request = \Illuminate\Http\Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\AuthorizeAdmin();

        try {
            $middleware->handle($request, function () {});
            $this->fail('Inactive user should be aborted with 403');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }
}
