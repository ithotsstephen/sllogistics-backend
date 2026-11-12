<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leave management · SL Logistics</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#0f172a;font:14px system-ui,sans-serif}
        header{background:#0f172a;color:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
        nav,.row{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}nav a{color:white;text-decoration:none;padding:.45rem .65rem;border-radius:6px}nav a:hover,nav a.active{background:#334155}
        main{max-width:1250px;margin:auto;padding:1.5rem}section{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.2rem;margin:1rem 0;overflow:auto}
        table{border-collapse:collapse;width:100%;min-width:760px}th,td{text-align:left;padding:.75rem;border-bottom:1px solid #e2e8f0;vertical-align:top}th{background:#f8fafc}
        select,input{font:inherit;padding:.55rem;border:1px solid #cbd5e1;border-radius:6px;max-width:100%}button{font:inherit;background:#0f766e;color:#fff;padding:.55rem .8rem;border:0;border-radius:6px;cursor:pointer}
        .secondary{background:#475569}.danger{background:#b91c1c}.muted{color:#64748b}.notice{padding:.75rem;background:#dcfce7;border-radius:6px;margin:.7rem 0}.error{padding:.75rem;background:#fee2e2;border-radius:6px;color:#991b1b;margin:.7rem 0}
        .badge{display:inline-block;background:#e2e8f0;padding:.2rem .55rem;border-radius:99px;text-transform:capitalize}.badge.pending{background:#fef3c7}.badge.approved{background:#dcfce7}.badge.rejected{background:#fee2e2}
        .profile-menu{position:relative}.profile-menu summary{list-style:none;cursor:pointer;border-radius:50%;width:40px;height:40px;display:grid;place-items:center;background:#334155}.profile-menu summary::-webkit-details-marker{display:none}.profile-menu summary:hover,.profile-menu[open] summary{background:#475569}.profile-menu svg{width:22px;height:22px;fill:currentColor}.profile-dropdown{position:absolute;right:0;top:calc(100% + .5rem);z-index:10;min-width:190px;padding:.4rem;background:white;color:#0f172a;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 20px #0f172a26}.profile-dropdown a,.profile-dropdown button{display:block;width:100%;padding:.65rem;text-align:left;color:#0f172a;background:transparent;text-decoration:none;border:0;border-radius:5px;font:inherit;cursor:pointer}.profile-dropdown a:hover,.profile-dropdown button:hover{background:#f1f5f9}
        @media(max-width:600px){header{padding:1rem}main{padding:.75rem}}
    </style>
</head>
<body>
<header>
    <nav>
        <strong>SL Logistics</strong>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.time-tracking') }}">Time Tracking</a>
        <a class="active" href="{{ route('admin.leave.index') }}">Leave management</a>
        <a href="{{ route('admin.employees') }}">Employee Management</a>
    </nav>
    @include('admin.partials.profile-menu')
</header>
<main>
    <h1>Leave management</h1>
    <p class="muted">Review staff leave requests, record a decision, and check previously reviewed requests.</p>
    @if (session('status')) <div class="notice">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif

    <section>
        <form class="row" method="get" action="{{ route('admin.leave.index') }}">
            <label for="status">Show
                <select id="status" name="status" onchange="this.form.submit()">
                    <option value="pending" @selected($status === 'pending')>Pending requests</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                    <option value="all" @selected($status === 'all')>All requests</option>
                </select>
            </label>
            <button class="secondary">Filter</button>
        </form>
        <table>
            <thead>
                <tr><th>Staff member</th><th>Leave dates</th><th>Days</th><th>Reason</th><th>Status / review</th><th>Decision</th></tr>
            </thead>
            <tbody>
                @forelse ($leaveRequests as $leave)
                    <tr>
                        <td><strong>{{ $leave->user->name }}</strong><br><span class="muted">{{ $leave->user->email }}</span></td>
                        <td>{{ $leave->start_date->format('M j, Y') }} – {{ $leave->end_date->format('M j, Y') }}</td>
                        <td>{{ $leave->leave_days }}</td>
                        <td>{{ $leave->reason }}</td>
                        <td>
                            <span class="badge {{ $leave->status }}">{{ $leave->status }}</span>
                            @if ($leave->reviewer)
                                <br><span class="muted">By {{ $leave->reviewer->name }} · {{ $leave->reviewed_at?->format('M j, Y H:i') }}</span>
                            @endif
                            @if ($leave->admin_comment)<br><span class="muted">{{ $leave->admin_comment }}</span>@endif
                        </td>
                        <td>
                            @if ($leave->status === 'pending')
                                <form method="post" action="{{ route('admin.leave.decide', $leave) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="admin_comment" maxlength="2000" placeholder="Optional comment" aria-label="Optional decision comment">
                                    <div class="row" style="margin-top:.4rem">
                                        <button name="status" value="approved">Approve</button>
                                        <button class="danger" name="status" value="rejected">Reject</button>
                                    </div>
                                </form>
                            @else
                                <span class="muted">Already reviewed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No {{ $status === 'all' ? '' : $status }} leave requests.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div>{{ $leaveRequests->links() }}</div>
    </section>
</main>
</body>
</html>
