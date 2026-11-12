<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_verify_email_with_an_unexpired_one_time_code(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => null,
            'email_otp_hash' => Hash::make('123456'),
            'email_otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/auth/verify-email', [
            'email' => $staff->email,
            'otp' => '123456',
        ])->assertOk();

        $this->assertNotNull($staff->fresh()->email_verified_at);
        $this->assertNull($staff->fresh()->email_otp_hash);

        $this->postJson('/api/auth/login', [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'token_type', 'must_change_password', 'user']);
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
