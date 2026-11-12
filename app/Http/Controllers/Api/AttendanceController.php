<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $records = $request->user()->attendances()
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('work_date', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('work_date', $request->integer('year')))
            ->orderByDesc('work_date')
            ->paginate(31);

        return response()->json($records);
    }

    public function timeIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'work_description' => ['required', 'string', 'max:10000'],
            'location' => ['required', 'string', 'max:255'],
        ]);

        if ($request->user()->attendances()->whereDate('work_date', today())->exists()) {
            throw ValidationException::withMessages(['attendance' => ['You already have an attendance record for today.']]);
        }

        $attendance = $request->user()->attendances()->create([
            ...$data,
            'work_date' => today(),
            'time_in' => now(),
        ]);

        return response()->json(['attendance' => $attendance], 201);
    }

    public function timeOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'work_description' => ['required', 'string', 'max:10000'],
        ]);

        $attendance = $request->user()->attendances()
            ->whereDate('work_date', today())
            ->whereNull('time_out')
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages(['attendance' => ['No open attendance record was found for today.']]);
        }

        $timeOut = now();
        $attendance->forceFill([
            'time_out' => $timeOut,
            'duration_minutes' => max(0, $attendance->time_in->diffInMinutes($timeOut)),
            'work_description' => $data['work_description'],
        ])->save();

        return response()->json(['attendance' => $attendance]);
    }
}
