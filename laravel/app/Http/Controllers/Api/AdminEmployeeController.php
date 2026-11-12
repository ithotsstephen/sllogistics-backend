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

        $result = $provisioning->create($data);
        $employee = $result['employee'];

        return response()->json([
            'message' => 'Employee account created. Share the temporary password with the employee securely.',
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
            'temporary_password' => $result['temporary_password'],
        ], 201)->header('Cache-Control', 'no-store, private');
    }
}
