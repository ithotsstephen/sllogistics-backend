<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_sign_in_without_email_verification(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'token_type', 'must_change_password', 'user']);

        $this->postJson('/api/auth/verify-email', [
            'email' => $staff->email,
            'otp' => '123456',
        ])->assertNotFound();
    }

    public function test_temporary_password_staff_cannot_time_in(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
            'must_change_password' => true,
        ]);
        Sanctum::actingAs($staff);

        $this->postJson('/api/attendance/time-in', [
            'work_description' => 'Work',
            'location' => 'Depot',
        ])->assertForbidden();
    }
}
