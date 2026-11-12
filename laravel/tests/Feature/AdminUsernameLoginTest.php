<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsernameLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_using_username(): void
    {
        User::factory()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'login' => 'admin',
            'password' => 'correct-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_can_still_sign_in_using_email(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'correct-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'login' => 'admin@example.test',
            'password' => 'correct-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }
}
