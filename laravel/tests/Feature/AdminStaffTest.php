<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_staff_with_a_designation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin, 'web')->post(route('admin.staff.store'), [
            'name' => 'Taylor Driver',
            'designation' => 'Delivery Driver',
            'email' => 'taylor@example.test',
        ])->assertRedirect(route('admin.employees'))
            ->assertSessionHas('status')
            ->assertSessionHas('created_employee_email', 'taylor@example.test')
            ->assertSessionHas('temporary_password');

        $employee = User::where('email', 'taylor@example.test')->firstOrFail();
        $this->assertDatabaseHas('users', [
            'name' => 'Taylor Driver',
            'designation' => 'Delivery Driver',
            'email' => 'taylor@example.test',
            'username' => 'taylor@example.test',
            'role' => 'staff',
        ]);
        $temporaryPassword = session('temporary_password');
        $this->assertTrue(Hash::check($temporaryPassword, $employee->password));

        $this->get(route('admin.employees'))
            ->assertOk()
            ->assertSee($temporaryPassword)
            ->assertSee('shown once');
        $this->get(route('admin.employees'))
            ->assertOk()
            ->assertDontSee($temporaryPassword);
    }

    public function test_admin_can_update_staff_designation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($admin, 'web')->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'designation' => 'Operations Coordinator',
            'phone' => $staff->phone,
            'address' => $staff->address,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'designation' => 'Operations Coordinator',
        ]);
    }

    public function test_employee_management_can_block_and_reactivate_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $token = $staff->createToken('mobile')->accessToken;

        $this->actingAs($admin, 'web')->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'designation' => $staff->designation,
            'phone' => $staff->phone,
            'address' => $staff->address,
            'is_active' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'is_active' => false]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token]);

        $this->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'designation' => $staff->designation,
            'phone' => $staff->phone,
            'address' => $staff->address,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'is_active' => true]);
    }

    public function test_employee_management_is_available_from_the_admin_header(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin, 'web')->get('/admin/employees')
            ->assertOk()
            ->assertSee('Employee Management')
            ->assertSee('Add employee')
            ->assertSee('Blocked');
    }
}
