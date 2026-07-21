<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Form;

class FormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUpAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        return $admin;
    }

    public function test_admin_can_create_form_with_json_schema(): void
    {
        $admin = $this->setUpAdmin();

        $fields = [
            ['label' => 'Your Name', 'name' => 'your_name', 'type' => 'text', 'required' => true],
            ['label' => 'Email Address', 'name' => 'email', 'type' => 'email', 'required' => true]
        ];

        // The form builder passes fields as a JSON string
        $response = $this->actingAs($admin)->post(route('admin.forms.store'), [
            'name' => 'Contact Us',
            'slug' => 'contact-us',
            'description' => 'Drop us a line',
            'is_active' => 1,
            'fields' => json_encode($fields)
        ]);

        $response->assertRedirect(route('admin.forms.index'));
        
        $this->assertDatabaseHas('forms', [
            'slug' => 'contact-us'
        ]);

        $form = Form::where('slug', 'contact-us')->first();
        $this->assertIsArray($form->fields);
        $this->assertCount(2, $form->fields);
    }

    public function test_public_can_submit_active_form_and_validate(): void
    {
        $form = Form::create([
            'name' => 'Support',
            'slug' => 'support',
            'is_active' => true,
            'fields' => [
                ['label' => 'Email Address', 'name' => 'email', 'type' => 'email', 'required' => true],
                ['label' => 'Message', 'name' => 'message', 'type' => 'textarea', 'required' => false]
            ]
        ]);

        // Hit page to render
        $renderResponse = $this->get(route('forms.show', 'support'));
        $renderResponse->assertStatus(200);
        $renderResponse->assertSee('Email Address');
        
        // Submit empty - should fail required
        $invalidResponse = $this->post(route('forms.submit', 'support'), [
            'email' => '',
            'message' => 'Hello'
        ]);
        $invalidResponse->assertSessionHasErrors(['email']);

        // Submit valid
        $validResponse = $this->post(route('forms.submit', 'support'), [
            'email' => 'test@example.com',
            'message' => 'Hello World'
        ]);
        
        $validResponse->assertSessionHas('success');
        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id
        ]);
        
        $submission = \App\Models\FormSubmission::first();
        $this->assertEquals('test@example.com', $submission->data['email']);
    }
}
