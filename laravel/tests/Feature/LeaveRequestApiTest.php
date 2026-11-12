<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveRequestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_submission_notifies_active_admins(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        Sanctum::actingAs($staff);

        $this->postJson('/api/leave-requests', [
            'start_date' => today()->addDays(3)->toDateString(),
            'end_date' => today()->addDays(4)->toDateString(),
            'reason' => 'Personal leave',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);
    }
}
