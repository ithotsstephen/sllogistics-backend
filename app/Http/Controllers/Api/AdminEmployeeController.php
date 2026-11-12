<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmployeeProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEmployeeController extends Controller
{
    public function store(Request $request, EmployeeProvisioningService $provisioning): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = $provisioning->create($data);

        return response()->json([
            'message' => 'Employee account created. Onboarding credentials and email verification code were sent to the employee email address.',
            'employee' => $employee->only([
                'id',
                'name',
                'username',
                'email',
                'designation',
                'phone',
                'address',
                'is_active',
                'email_verified_at',
            ]),
        ], 201);
    }
}
