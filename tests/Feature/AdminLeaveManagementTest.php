<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_leave_management_and_approve_a_pending_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff']);
        $leave = $staff->leaveRequests()->create([
            'start_date' => today()->addDays(3),
            'end_date' => today()->addDays(4),
            'reason' => 'Family event',
        ]);

        $this->actingAs($admin, 'web')->get('/admin/leave-management')
            ->assertOk()
            ->assertSee('Employee Management')
            ->assertSee('Leave management')
            ->assertSee($staff->name)
            ->assertSee('Family event');

        $this->from('/admin/leave-management')->patch(route('admin.leave.decide', $leave), [
            'status' => 'approved',
            'admin_comment' => 'Approved',
        ])->assertRedirect('/admin/leave-management');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'admin_comment' => 'Approved',
        ]);
    }

    public function test_admin_can_reject_a_pending_request_and_filter_reviewed_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff']);
        $leave = $staff->leaveRequests()->create([
            'start_date' => today()->addDays(5),
            'end_date' => today()->addDays(5),
            'reason' => 'Personal appointment',
        ]);

        $this->actingAs($admin, 'web')->from('/admin/leave-management')->patch(route('admin.leave.decide', $leave), [
            'status' => 'rejected',
        ])->assertRedirect('/admin/leave-management');

        $this->get('/admin/leave-management?status=rejected')
            ->assertOk()
            ->assertSee($staff->name)
            ->assertSee('rejected');
    }

    public function test_non_admin_cannot_access_leave_management(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);

        $this->actingAs($staff, 'web')->get('/admin/leave-management')->assertForbidden();
    }
}
