<?php

namespace Tests\Feature;

use App\Mail\NewEmployeeAccountMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_api_creates_verified_employee_and_emails_temporary_password_and_profile(): void
    {
        config(['mail.default' => 'array']);
        Mail::fake();

        $admin = User::factory()->create([
            'username' => 'admin@example.test',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $login = $this->postJson('/api/admin/auth/login', [
            'login' => 'admin@example.test',
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'token_type', 'admin']);

        $response = $this->withToken($login->json('token'))->postJson('/api/admin/employees', [
            'name' => 'Jordan Employee',
            'designation' => 'Delivery Driver',
            'email' => 'jordan@example.test',
            'phone' => '555-0199',
            'address' => '12 Depot Road',
        ])->assertCreated()
            ->assertJsonPath('employee.username', 'jordan@example.test')
            ->assertJsonPath('employee.email', 'jordan@example.test')
            ->assertJsonPath('employee.designation', 'Delivery Driver')
            ->assertJsonMissingPath('employee.password')
            ->assertJsonMissingPath('temporary_password');

        $employee = User::where('email', 'jordan@example.test')->firstOrFail();
        $this->assertSame('staff', $employee->role);
        $this->assertTrue($employee->must_change_password);
        $this->assertNotNull($employee->email_verified_at);
        $this->assertNull($employee->email_otp_hash);
        $this->assertNull($employee->email_otp_expires_at);
        $this->assertSame($response->json('employee.id'), $employee->id);

        Mail::assertSent(NewEmployeeAccountMail::class, function (NewEmployeeAccountMail $mail) use ($employee): bool {
            $this->assertSame($employee->email, $mail->employee->email);
            $this->assertNotEmpty($mail->temporaryPassword);
            $this->assertTrue(Hash::check($mail->temporaryPassword, $employee->password));
            $mail->assertSeeInHtml($mail->temporaryPassword);
            $mail->assertDontSeeInHtml('one-time code');
            $mail->assertSeeInHtml('Delivery Driver');

            return true;
        });
    }

    public function test_employee_creation_api_rejects_non_admin_tokens(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $token = $staff->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/admin/employees', [
            'name' => 'Unauthorized Employee',
            'email' => 'unauthorized@example.test',
        ])->assertForbidden();
    }

    public function test_employee_creation_does_not_claim_email_was_sent_with_log_mailer(): void
    {
        config(['mail.default' => 'log']);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $token = $admin->createToken('admin-api', ['admin'])->plainTextToken;

        $this->withToken($token)->postJson('/api/admin/employees', [
            'name' => 'Mail Not Configured',
            'email' => 'no-mail@example.test',
        ])->assertServiceUnavailable();

        $this->assertDatabaseMissing('users', ['email' => 'no-mail@example.test']);
    }
}
