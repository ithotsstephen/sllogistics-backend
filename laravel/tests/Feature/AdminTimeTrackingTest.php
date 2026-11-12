<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_daily_time_tracking_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Daily Driver']);
        $staff->attendances()->create([
            'work_date' => today(),
            'time_in' => today()->setTime(8, 15),
            'time_out' => today()->setTime(16, 45),
            'duration_minutes' => 510,
            'location' => 'Main warehouse',
            'work_description' => 'Completed scheduled deliveries',
        ]);

        $this->actingAs($admin, 'web')->get('/admin/time-tracking')
            ->assertOk()
            ->assertSee('Employee Management')
            ->assertSee('Time Tracking')
            ->assertSee('Daily Driver')
            ->assertSee('Main warehouse')
            ->assertSee('Completed scheduled deliveries')
            ->assertSee('8.50 hrs')
            ->assertSee('8:15 AM')
            ->assertSee('4:45 PM');
    }

    public function test_time_tracking_can_be_filtered_to_a_staff_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $selectedStaff = User::factory()->create(['role' => 'staff', 'name' => 'Selected Employee']);
        $otherStaff = User::factory()->create(['role' => 'staff', 'name' => 'Other Employee']);
        foreach ([$selectedStaff, $otherStaff] as $index => $staff) {
            $staff->attendances()->create([
                'work_date' => today(),
                'time_in' => now(),
                'work_description' => $index === 0 ? 'Selected work description' : 'Other work description',
                'location' => 'Depot',
            ]);
        }

        $this->actingAs($admin, 'web')->get('/admin/time-tracking?staff_id='.$selectedStaff->id)
            ->assertOk()
            ->assertSee('Selected Employee')
            ->assertSee('Selected work description')
            ->assertDontSee('Other work description');
    }
}
