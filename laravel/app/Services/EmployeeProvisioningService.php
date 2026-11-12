<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeProvisioningService
{
    /**
     * @param  array{name: string, designation?: ?string, email: string, phone?: ?string, address?: ?string}  $data
     * @return array{employee: User, temporary_password: string}
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data): array {
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

            return [
                'employee' => $employee,
                'temporary_password' => $temporaryPassword,
            ];
        });
    }
}
