<?php

namespace App\Services;

use App\Mail\NewEmployeeAccountMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class EmployeeProvisioningService
{
    /**
     * @param  array{name: string, designation?: ?string, email: string, phone?: ?string, address?: ?string}  $data
     */
    public function create(array $data): User
    {
        if (config('mail.default') === 'log') {
            throw new ServiceUnavailableHttpException(null, 'Employee onboarding email is disabled because MAIL_MAILER is set to log. Configure a real SMTP mailer and try again.');
        }

        return DB::transaction(function () use ($data): User {
            $temporaryPassword = Str::password(16);

            $employee = User::create([
                ...$data,
                'username' => $data['email'],
                'password' => Hash::make($temporaryPassword),
                'role' => 'staff',
                'is_active' => true,
                'must_change_password' => true,
            ]);
            $employee->forceFill(['email_verified_at' => now()])->save();

            Mail::to($employee->email)->send(
                new NewEmployeeAccountMail($employee, $temporaryPassword),
            );

            return $employee;
        });
    }
}
