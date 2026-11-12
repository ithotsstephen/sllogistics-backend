<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Time Tracking · SL Logistics</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#0f172a;font:14px system-ui,sans-serif}
        header{background:#0f172a;color:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
        nav,.row{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}nav a{color:white;text-decoration:none;padding:.45rem .65rem;border-radius:6px}nav a:hover,nav a.active{background:#334155}
        main{max-width:1450px;margin:auto;padding:1.5rem}section{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.2rem;margin:1rem 0;overflow:auto}
        table{border-collapse:collapse;width:100%;min-width:950px}th,td{text-align:left;padding:.75rem;border-bottom:1px solid #e2e8f0;vertical-align:top}th{background:#f8fafc}
        select,input{font:inherit;padding:.55rem;border:1px solid #cbd5e1;border-radius:6px;max-width:100%}button{font:inherit;background:#0f766e;color:#fff;padding:.55rem .8rem;border:0;border-radius:6px;cursor:pointer}
        .secondary{background:#475569}.muted{color:#64748b}.description{white-space:pre-wrap;min-width:220px;max-width:440px}
        .profile-menu{position:relative}.profile-menu summary{list-style:none;cursor:pointer;border-radius:50%;width:40px;height:40px;display:grid;place-items:center;background:#334155}.profile-menu summary::-webkit-details-marker{display:none}.profile-menu summary:hover,.profile-menu[open] summary{background:#475569}.profile-menu svg{width:22px;height:22px;fill:currentColor}.profile-dropdown{position:absolute;right:0;top:calc(100% + .5rem);z-index:10;min-width:190px;padding:.4rem;background:white;color:#0f172a;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 20px #0f172a26}.profile-dropdown a,.profile-dropdown button{display:block;width:100%;padding:.65rem;text-align:left;color:#0f172a;background:transparent;text-decoration:none;border:0;border-radius:5px;font:inherit;cursor:pointer}.profile-dropdown a:hover,.profile-dropdown button:hover{background:#f1f5f9}
        @media(max-width:600px){header{padding:1rem}main{padding:.75rem}}
    </style>
</head>
<body>
<header>
    <nav>
        <strong>SL Logistics</strong>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a class="active" href="{{ route('admin.time-tracking') }}">Time Tracking</a>
        <a href="{{ route('admin.leave.index') }}">Leave management</a>
        <a href="{{ route('admin.employees') }}">Employee Management</a>
    </nav>
    @include('admin.partials.profile-menu')
</header>
<main>
    <h1>Time Tracking</h1>
    <p class="muted">Daily employee attendance, recorded work times, duration, location, and work description.</p>
    <section>
        <form class="row" method="get" action="{{ route('admin.time-tracking') }}">
            <label for="month">Month
                <select id="month" name="month">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endfor
                </select>
            </label>
            <label for="year">Year <input id="year" type="number" name="year" value="{{ $year }}" min="2000" max="2200"></label>
            <label for="staff_id">Employee
                <select id="staff_id" name="staff_id">
                    <option value="">All employees</option>
                    @foreach ($staff as $person)
                        <option value="{{ $person->id }}" @selected((string) $staffId === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </select>
            </label>
            <button>Apply filters</button>
            <a href="{{ route('admin.time-tracking') }}" style="color:#334155">Clear</a>
        </form>
        <table>
            <thead>
                <tr><th>Work date</th><th>Employee</th><th>Time in</th><th>Time out</th><th>Total hours</th><th>Location</th><th>Work description</th></tr>
            </thead>
            <tbody>
                @forelse ($attendance as $record)
                    <tr>
                        <td>{{ $record->work_date->format('D, M j, Y') }}</td>
                        <td>{{ $record->user->name }}</td>
                        <td>{{ $record->time_in->format('g:i A') }}</td>
                        <td>{{ $record->time_out?->format('g:i A') ?? 'Not timed out' }}</td>
                        <td>{{ $record->duration_minutes !== null ? number_format($record->duration_minutes / 60, 2).' hrs' : 'In progress' }}</td>
                        <td>{{ $record->location }}</td>
                        <td class="description">{{ $record->work_description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No time-tracking records found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div>{{ $attendance->links() }}</div>
    </section>
</main>
</body>
</html>
