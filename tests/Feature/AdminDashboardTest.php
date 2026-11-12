<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_and_leave_days_are_counted_across_year_boundary(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff']);
        $year = (int) now()->format('Y');
        $staff->leaveRequests()->create([
            'start_date' => "{$year}-12-30",
            'end_date' => ($year + 1).'-01-02',
            'reason' => 'Holiday',
            'status' => 'approved',
        ]);

        $this->actingAs($admin, 'web')->get('/admin')
            ->assertOk()
            ->assertSee($staff->name)
            ->assertSee('2', false);
    }

    public function test_dashboard_shows_active_employee_count_and_completed_hours_totals(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-13 12:00:00'));

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $todayStaff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $weekStaff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $inactiveStaff = User::factory()->create(['role' => 'staff', 'is_active' => false]);

        $todayStaff->attendances()->create([
            'work_date' => '2026-05-13',
            'time_in' => '2026-05-13 08:00:00',
            'time_out' => '2026-05-13 12:00:00',
            'duration_minutes' => 240,
            'location' => 'Office',
            'work_description' => 'Completed today',
        ]);
        $weekStaff->attendances()->create([
            'work_date' => '2026-05-11',
            'time_in' => '2026-05-11 08:00:00',
            'time_out' => '2026-05-11 10:00:00',
            'duration_minutes' => 120,
            'location' => 'Depot',
            'work_description' => 'Completed this week',
        ]);
        $todayStaff->attendances()->create([
            'work_date' => '2026-03-10',
            'time_in' => '2026-03-10 08:00:00',
            'time_out' => '2026-03-10 13:00:00',
            'duration_minutes' => 300,
            'location' => 'Depot',
            'work_description' => 'Completed in March',
        ]);
        $weekStaff->attendances()->create([
            'work_date' => '2026-05-08',
            'time_in' => '2026-05-08 08:00:00',
            'time_out' => '2026-05-08 11:00:00',
            'duration_minutes' => 180,
            'location' => 'Depot',
            'work_description' => 'Previous week',
        ]);
        $inactiveStaff->attendances()->create([
            'work_date' => '2026-05-13',
            'time_in' => '2026-05-13 08:00:00',
            'location' => 'Office',
            'work_description' => 'Open shift',
        ]);

        $this->actingAs($admin, 'web')->get('/admin?month=5&year=2026')
            ->assertOk()
            ->assertSee('Employee Management')
            ->assertSee('Active employees')
            ->assertSee('Recorded hours today')
            ->assertSee('Recorded hours this week')
            ->assertSee('Monthly hours · 2026')
            ->assertSee('2.00', false)
            ->assertSee('4.00', false)
            ->assertSee('6.00', false)
            ->assertSee('May: 9.00 hours')
            ->assertSee('Mar: 5.00 hours');

        Carbon::setTestNow();
    }

    public function test_dashboard_lists_approved_leave_for_yesterday_today_and_tomorrow_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-13 12:00:00'));

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $leavePeriods = [
            'Yesterday Employee' => ['2026-05-12', '2026-05-12'],
            'Today Employee' => ['2026-05-13', '2026-05-13'],
            'Tomorrow Employee' => ['2026-05-14', '2026-05-14'],
            'Later Employee' => ['2026-05-15', '2026-05-15'],
        ];

        foreach ($leavePeriods as $name => [$start, $end]) {
            $staff = User::factory()->create([
                'role' => $name === 'Later Employee' ? 'admin' : 'staff',
                'name' => $name,
            ]);
            $staff->leaveRequests()->create([
                'start_date' => $start,
                'end_date' => $end,
                'reason' => 'Test leave',
                'status' => 'approved',
            ]);
        }

        $this->actingAs($admin, 'web')->get('/admin?month=5&year=2026')
            ->assertOk()
            ->assertSee('Yesterday Employee')
            ->assertSee('Today Employee')
            ->assertSee('Tomorrow Employee')
            ->assertDontSee('Later Employee')
            ->assertSee('Yesterday · May 12')
            ->assertSee('Today · May 13')
            ->assertSee('Tomorrow · May 14');

        Carbon::setTestNow();
    }
}
