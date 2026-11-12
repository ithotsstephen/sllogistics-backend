<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->leaveRequests()->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        $hasOverlap = $request->user()->leaveRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages(['start_date' => ['This request overlaps an existing pending or approved leave request.']]);
        }

        $leaveRequest = $request->user()->leaveRequests()->create($data);
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        Notification::send($admins, new LeaveRequestSubmitted($leaveRequest));

        return response()->json(['leave_request' => $leaveRequest], 201);
    }
}
