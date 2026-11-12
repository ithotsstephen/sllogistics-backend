<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\EmployeeProvisioningService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function loginPage(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('admin.dashboard') : view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $admin = User::where('role', 'admin')
            ->where('is_active', true)
            ->where(function ($query) use ($credentials): void {
                $query->where('username', $credentials['login'])
                    ->orWhere('email', $credentials['login']);
            })
            ->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            throw ValidationException::withMessages(['login' => ['These credentials do not match an active administrator account.']]);
        }

        Auth::login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function passwordPage(): View
    {
        return view('admin.password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'string', 'min:12', 'different:current_password'],
        ]);

        $request->user()->update(['password' => $data['password']]);
        $request->session()->regenerate();

        return back()->with('status', 'Your password has been changed.');
    }

    public function dashboard(Request $request): View
    {
        $month = $request->integer('month', (int) now()->format('n'));
        $year = $request->integer('year', (int) now()->format('Y'));
        abort_if($month < 1 || $month > 12 || $year < 2000 || $year > 2200, 422);

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $monthlyMinutes = array_fill(1, 12, 0);

        Attendance::query()
            ->whereYear('work_date', $year)
            ->whereNotNull('time_out')
            ->whereNotNull('duration_minutes')
            ->select(['id', 'work_date', 'duration_minutes'])
            ->chunkById(1000, function ($records) use (&$monthlyMinutes): void {
                foreach ($records as $record) {
                    $monthlyMinutes[$record->work_date->month] += $record->duration_minutes;
                }
            });

        $maxMonthlyMinutes = max($monthlyMinutes);
        $monthlyHours = collect($monthlyMinutes)->map(fn (int $minutes, int $monthNumber): array => [
            'month' => $monthNumber,
            'label' => Carbon::create()->month($monthNumber)->format('M'),
            'hours' => round($minutes / 60, 2),
            'height' => $maxMonthlyMinutes > 0 ? round(($minutes / $maxMonthlyMinutes) * 100, 2) : 0,
        ])->values();

        $attendance = Attendance::with('user')
            ->whereMonth('work_date', $month)
            ->whereYear('work_date', $year)
            ->when($request->filled('staff_id'), fn ($query) => $query->where('user_id', $request->integer('staff_id')))
            ->orderByDesc('work_date')
            ->orderByDesc('time_in')
            ->paginate(50)
            ->withQueryString();

        return view('admin.dashboard', [
            'attendance' => $attendance,
            'pendingLeaves' => LeaveRequest::with('user')->where('status', 'pending')->orderBy('created_at')->get(),
            'staff' => User::where('role', 'staff')->orderBy('name')->get(['id', 'name']),
            'onLeaveYesterday' => LeaveRequest::with('user')->where('status', 'approved')
                ->whereDate('start_date', '<=', today()->copy()->subDay())
                ->whereDate('end_date', '>=', today()->copy()->subDay())
                ->orderBy('start_date')->get(),
            'onLeaveToday' => LeaveRequest::with('user')->where('status', 'approved')
                ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())
                ->orderBy('start_date')->get(),
            'onLeaveTomorrow' => LeaveRequest::with('user')->where('status', 'approved')
                ->whereDate('start_date', '<=', today()->copy()->addDay())
                ->whereDate('end_date', '>=', today()->copy()->addDay())
                ->orderBy('start_date')->get(),
            'month' => $month,
            'year' => $year,
            'totalEmployees' => User::where('role', 'staff')->where('is_active', true)->count(),
            'hoursToday' => (int) Attendance::whereDate('work_date', today())
                ->whereNotNull('time_out')->whereNotNull('duration_minutes')->sum('duration_minutes'),
            'hoursThisWeek' => (int) Attendance::whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->whereNotNull('time_out')->whereNotNull('duration_minutes')->sum('duration_minutes'),
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'monthlyHours' => $monthlyHours,
            'maxMonthlyHours' => round($maxMonthlyMinutes / 60, 2),
            'unreadNotifications' => $this->consumeNotifications($request->user()),
        ]);
    }

    public function leaveManagement(Request $request): View
    {
        $status = $request->query('status', 'pending');
        abort_unless(in_array($status, ['pending', 'approved', 'rejected', 'all'], true), 422);

        $leaveRequests = LeaveRequest::with('user', 'reviewer')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.leave-management', [
            'leaveRequests' => $leaveRequests,
            'status' => $status,
        ]);
    }

    public function timeTracking(Request $request): View
    {
        $month = $request->integer('month', (int) now()->format('n'));
        $year = $request->integer('year', (int) now()->format('Y'));
        abort_if($month < 1 || $month > 12 || $year < 2000 || $year > 2200, 422);

        $staffId = $request->query('staff_id');
        if ($staffId !== null) {
            abort_unless(ctype_digit((string) $staffId) && User::where('role', 'staff')->whereKey($staffId)->exists(), 404);
        }

        $attendance = Attendance::with('user')
            ->whereMonth('work_date', $month)
            ->whereYear('work_date', $year)
            ->when($staffId !== null, fn ($query) => $query->where('user_id', $staffId))
            ->orderByDesc('work_date')
            ->orderBy('user_id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.time-tracking', [
            'attendance' => $attendance,
            'staff' => User::where('role', 'staff')->orderBy('name')->get(['id', 'name']),
            'month' => $month,
            'year' => $year,
            'staffId' => $staffId,
        ]);
    }

    public function employeeManagement(Request $request): View
    {
        $status = $request->query('status', 'active');
        abort_unless(in_array($status, ['active', 'blocked', 'all'], true), 422);
        $year = $request->integer('year', (int) now()->format('Y'));
        abort_if($year < 2000 || $year > 2200, 422);

        $employees = User::where('role', 'staff')
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'blocked', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.employee-management', [
            'employees' => $employees,
            'status' => $status,
            'year' => $year,
            'leaveDaysByStaff' => LeaveRequest::query()
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', "{$year}-12-31")
                ->whereDate('end_date', '>=', "{$year}-01-01")
                ->get(['user_id', 'start_date', 'end_date'])
                ->groupBy('user_id')
                ->map(fn ($leaves) => $leaves->sum(function (LeaveRequest $leave) use ($year): int {
                    $start = $leave->start_date->max("{$year}-01-01");
                    $end = $leave->end_date->min("{$year}-12-31");

                    return (int) $start->diffInDays($end) + 1;
                })),
        ]);
    }

    public function createStaff(Request $request, EmployeeProvisioningService $provisioning): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $provisioning->create($data);

        return redirect()->route('admin.employees')
            ->with('status', 'Employee account created. Temporary sign-in credentials were emailed to the employee.');
    }

    public function updateStaff(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'staff', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $user->update($data);
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return back()->with('status', $user->is_active ? 'Employee profile updated.' : 'Employee profile updated and account blocked.');
    }

    public function decideLeave(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['leave' => 'This leave request has already been reviewed.']);
        }

        if ($data['status'] === 'approved') {
            $overlap = LeaveRequest::where('user_id', $leaveRequest->user_id)
                ->where('status', 'approved')
                ->where('id', '!=', $leaveRequest->id)
                ->whereDate('start_date', '<=', $leaveRequest->end_date)
                ->whereDate('end_date', '>=', $leaveRequest->start_date)
                ->exists();
            if ($overlap) {
                return back()->withErrors(['leave' => 'This request overlaps an approved leave request.']);
            }
        }

        $leaveRequest->update([
            ...$data,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Leave request '.$data['status'].'.');
    }

    private function consumeNotifications(User $user)
    {
        $notifications = $user->unreadNotifications()->latest()->limit(8)->get();
        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        return $notifications;
    }
}
