<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_time_in_and_out_and_duration_is_saved(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'email_verified_at' => now()]);
        Sanctum::actingAs($staff);

        Carbon::setTestNow(now()->setTime(9, 0));
        $this->postJson('/api/attendance/time-in', [
            'work_description' => 'Deliveries planned',
            'location' => 'Warehouse A',
        ])->assertCreated();

        Carbon::setTestNow(now()->setTime(17, 30));
        $this->postJson('/api/attendance/time-out', [
            'work_description' => 'Deliveries completed',
        ])->assertOk()
            ->assertJsonPath('attendance.duration_minutes', 510)
            ->assertJsonPath('attendance.duration_hours', 8.5)
            ->assertJsonPath('attendance.work_description', 'Deliveries completed');

        Carbon::setTestNow();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $staff->id,
            'location' => 'Warehouse A',
            'duration_minutes' => 510,
        ]);
    }

    public function test_staff_cannot_use_protected_endpoints_until_temporary_password_is_changed(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
            'must_change_password' => true,
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('must_change_password', true);

        $token = $login->json('token');
        $this->withToken($token)->getJson('/api/profile')->assertForbidden();

        $this->withToken($token)->postJson('/api/auth/change-password', [
            'current_password' => 'password',
            'password' => 'a-strong-password-2026',
            'password_confirmation' => 'a-strong-password-2026',
        ])->assertOk();

        $this->withToken($token)->getJson('/api/profile')->assertOk();
    }

    public function test_staff_can_view_and_update_their_designation(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
            'designation' => 'Driver',
        ]);
        Sanctum::actingAs($staff);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('user.designation', 'Driver');

        $this->putJson('/api/profile', ['designation' => 'Senior Driver'])
            ->assertOk()
            ->assertJsonPath('user.designation', 'Senior Driver');
    }
}
