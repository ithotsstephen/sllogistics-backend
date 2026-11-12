<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin dashboard · SL Logistics</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#0f172a;font:14px system-ui,sans-serif}header{background:#0f172a;color:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
        nav{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}nav a{color:white;text-decoration:none;padding:.45rem .65rem;border-radius:6px}nav a:hover,nav a.active{background:#334155}
        main{max-width:1450px;margin:auto;padding:1.5rem}h1{font-size:1.6rem}h2{font-size:1.15rem;margin-top:0}section{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.2rem;margin:1rem 0;overflow:auto}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}.columns{display:grid;grid-template-columns:2fr 1fr;gap:1rem}label{font-weight:600;display:block;margin:.5rem 0 .25rem}
        input,select,textarea{font:inherit;padding:.55rem;border:1px solid #cbd5e1;border-radius:6px;max-width:100%}textarea{width:100%;min-height:65px}button,.button{font:inherit;background:#0f766e;color:#fff;padding:.55rem .8rem;border:0;border-radius:6px;cursor:pointer;text-decoration:none}
        .secondary{background:#475569}.danger{background:#b91c1c}.row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}.row>*{margin:.15rem 0}table{border-collapse:collapse;width:100%;min-width:680px}th,td{text-align:left;padding:.65rem;border-bottom:1px solid #e2e8f0;vertical-align:top}th{background:#f8fafc}.muted{color:#64748b}.notice{padding:.75rem;background:#dcfce7;border-radius:6px;margin:.7rem 0}.error{padding:.75rem;background:#fee2e2;border-radius:6px;color:#991b1b;margin:.7rem 0}
        .badge{display:inline-block;background:#e2e8f0;padding:.2rem .5rem;border-radius:99px}.badge.pending{background:#fef3c7}.badge.approved{background:#dcfce7}.badge.rejected{background:#fee2e2}
        .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1rem;margin:1.2rem 0}.summary-card{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.1rem}.summary-card span{display:block;color:#64748b}.summary-card strong{display:block;font-size:1.7rem;margin-top:.35rem}
        .chart{display:grid;grid-template-columns:repeat(12,minmax(38px,1fr));gap:.6rem;align-items:end;overflow-x:auto;padding:.75rem 0}.chart-month{text-align:center;min-width:38px}.chart-value{font-size:.72rem;color:#475569;min-height:1.2rem}.chart-track{height:150px;display:flex;align-items:flex-end;background:#f1f5f9;border-radius:6px;overflow:hidden}.chart-bar{width:100%;min-height:0;background:#0f766e;border-radius:6px 6px 0 0}.chart-label{font-size:.75rem;color:#475569;margin-top:.35rem}
        .profile-menu{position:relative}.profile-menu summary{list-style:none;cursor:pointer;border-radius:50%;width:40px;height:40px;display:grid;place-items:center;background:#334155}.profile-menu summary::-webkit-details-marker{display:none}.profile-menu summary:hover,.profile-menu[open] summary{background:#475569}.profile-menu svg{width:22px;height:22px;fill:currentColor}.profile-dropdown{position:absolute;right:0;top:calc(100% + .5rem);z-index:10;min-width:190px;padding:.4rem;background:white;color:#0f172a;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 20px #0f172a26}.profile-dropdown a,.profile-dropdown button{display:block;width:100%;padding:.65rem;text-align:left;color:#0f172a;background:transparent;text-decoration:none;border:0;border-radius:5px;font:inherit;cursor:pointer}.profile-dropdown a:hover,.profile-dropdown button:hover{background:#f1f5f9}
        @media(max-width:850px){.columns{grid-template-columns:1fr}header{padding:1rem}}
    </style>
</head>
<body>
<header>
    <nav>
        <strong>SL Logistics · Admin</strong>
        <a class="active" href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.time-tracking') }}">Time Tracking</a>
        <a href="{{ route('admin.leave.index') }}">Leave management</a>
        <a href="{{ route('admin.employees') }}">Employee Management</a>
    </nav>
    @include('admin.partials.profile-menu')
</header>
<main>
    <h1>Attendance and staff dashboard</h1>
    @if (session('status')) <div class="notice">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
    @if (session('temporary_password'))
        <div class="notice"><strong>Temporary password (shown once):</strong> <code>{{ session('temporary_password') }}</code></div>
    @endif

    <div class="summary-grid">
        <div class="summary-card"><span>Active employees</span><strong>{{ number_format($totalEmployees) }}</strong></div>
        <div class="summary-card"><span>Recorded hours today</span><strong>{{ number_format($hoursToday / 60, 2) }}</strong></div>
        <div class="summary-card"><span>Recorded hours this week</span><strong>{{ number_format($hoursThisWeek / 60, 2) }}</strong><span>{{ $weekStart->format('M j') }} – {{ $weekEnd->format('M j') }}</span></div>
    </div>

    <section>
        <h2>Monthly hours · {{ $year }}</h2>
        <p class="muted">Total completed attendance hours per month. Open shifts are excluded.</p>
        <div class="chart" role="img" aria-label="Monthly recorded work hours for {{ $year }}">
            @foreach ($monthlyHours as $monthly)
                <div class="chart-month" title="{{ $monthly['label'] }}: {{ number_format($monthly['hours'], 2) }} hours">
                    <div class="chart-value">{{ $monthly['hours'] > 0 ? number_format($monthly['hours'], 1) : '' }}</div>
                    <div class="chart-track"><div class="chart-bar" style="height: {{ $monthly['height'] }}%"></div></div>
                    <div class="chart-label">{{ $monthly['label'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($unreadNotifications->isNotEmpty())
    <section><h2>New leave notifications</h2>
        @foreach ($unreadNotifications as $notification)
            <p>{{ $notification->data['staff_name'] }} submitted leave for {{ $notification->data['start_date'] }} to {{ $notification->data['end_date'] }}.</p>
        @endforeach
    </section>
    @endif

    <div class="columns">
        <section>
            <h2>Attendance</h2>
            <form class="row" method="get" action="{{ route('admin.dashboard') }}">
                <label>Month <select name="month">@for ($m=1;$m<=12;$m++) <option value="{{ $m }}" @selected($month===$m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option> @endfor</select></label>
                <label>Year <input type="number" name="year" value="{{ $year }}" min="2000" max="2200"></label>
                <label>Staff <select name="staff_id"><option value="">All staff</option>@foreach ($staff as $person)<option value="{{ $person->id }}" @selected(request('staff_id')==$person->id)>{{ $person->name }}</option>@endforeach</select></label>
                <button>Filter</button>
            </form>
            <table>
                <thead><tr><th>Date / Staff</th><th>Time in</th><th>Time out</th><th>Hours</th><th>Location</th><th>Work description</th></tr></thead>
                <tbody>
                @forelse ($attendance as $record)
                    <tr><td>{{ $record->work_date->format('Y-m-d') }}<br><span class="muted">{{ $record->user->name }}</span></td>
                        <td>{{ $record->time_in->format('H:i') }}</td><td>{{ $record->time_out?->format('H:i') ?? 'In progress' }}</td>
                        <td>{{ $record->duration_minutes !== null ? number_format($record->duration_minutes / 60, 2).' h' : '—' }}</td>
                        <td>{{ $record->location }}</td><td>{{ $record->work_description }}</td></tr>
                @empty <tr><td colspan="6">No attendance records for this period.</td></tr> @endforelse
                </tbody>
            </table>
            <div>{{ $attendance->links() }}</div>
        </section>
        <section>
            <h2>Employees on approved leave</h2>
            <h3>Yesterday · {{ today()->subDay()->format('M j') }}</h3>
            @forelse ($onLeaveYesterday as $leave)<p><strong>{{ $leave->user->name }}</strong></p>@empty<p class="muted">No employees on leave.</p>@endforelse
            <h3>Today · {{ today()->format('M j') }}</h3>
            @forelse ($onLeaveToday as $leave)<p><strong>{{ $leave->user->name }}</strong></p>@empty<p class="muted">No employees on leave.</p>@endforelse
            <h3>Tomorrow · {{ today()->addDay()->format('M j') }}</h3>
            @forelse ($onLeaveTomorrow as $leave)<p><strong>{{ $leave->user->name }}</strong></p>@empty<p class="muted">No employees on leave.</p>@endforelse
        </section>
    </div>

    <div class="grid">
        <section>
            <h2>Employee management</h2>
            <p>Add employees, edit their profiles, manage approved leave totals, or block and reactivate accounts.</p>
            <a class="button" href="{{ route('admin.employees') }}">Open employee management</a>
        </section>
        <section>
            <h2>Leave management</h2>
            <p>Review and manage pending, approved, and rejected staff leave requests in the dedicated leave module.</p>
            <a class="button" href="{{ route('admin.leave.index') }}">Open leave management</a>
            <p class="muted">{{ $pendingLeaves->count() }} pending request(s).</p>
        </section>
    </div>
</main>
</body>
</html>
