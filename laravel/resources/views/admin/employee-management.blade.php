<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Management · SL Logistics</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#0f172a;font:14px system-ui,sans-serif}
        header{background:#0f172a;color:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
        nav,.row{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}nav a{color:white;text-decoration:none;padding:.45rem .65rem;border-radius:6px}nav a:hover,nav a.active{background:#334155}
        main{max-width:1450px;margin:auto;padding:1.5rem}section{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.2rem;margin:1rem 0;overflow:auto}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}table{border-collapse:collapse;width:100%;min-width:1050px}th,td{text-align:left;padding:.7rem;border-bottom:1px solid #e2e8f0;vertical-align:top}th{background:#f8fafc}
        label{display:block;font-weight:600;margin:.55rem 0 .25rem}input,select,textarea{font:inherit;padding:.55rem;border:1px solid #cbd5e1;border-radius:6px;max-width:100%}textarea{width:100%;min-height:60px}
        button,.button{font:inherit;background:#0f766e;color:#fff;padding:.55rem .8rem;border:0;border-radius:6px;cursor:pointer;text-decoration:none}.secondary{background:#475569}.danger{background:#b91c1c}
        .muted{color:#64748b}.notice{padding:.75rem;background:#dcfce7;border-radius:6px;margin:.7rem 0}.error{padding:.75rem;background:#fee2e2;border-radius:6px;color:#991b1b;margin:.7rem 0}
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
        <a href="{{ route('admin.leave.index') }}">Leave management</a>
        <a class="active" href="{{ route('admin.employees') }}">Employee Management</a>
    </nav>
    @include('admin.partials.profile-menu')
</header>
<main>
    <h1>Employee Management</h1>
    <p class="muted">Create employee accounts, edit staff profiles, and block or reactivate access.</p>
    @if (session('status')) <div class="notice">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif

    <div class="grid">
        <section>
            <h2>Add employee</h2>
            <form method="post" action="{{ route('admin.staff.store') }}">
                @csrf
                <label>Name <input name="name" value="{{ old('name') }}" required maxlength="120"></label>
                <label>Designation <input name="designation" value="{{ old('designation') }}" maxlength="120" placeholder="e.g. Driver, Warehouse Supervisor"></label>
                <label>Email <input type="email" name="email" value="{{ old('email') }}" required maxlength="255"></label>
                <label>Phone <input name="phone" value="{{ old('phone') }}" maxlength="40"></label>
                <label>Address <textarea name="address" maxlength="2000">{{ old('address') }}</textarea></label>
                <button>Create employee account</button>
            </form>
        </section>
        <section>
            <h2>Employee access</h2>
            <p>Blocking an account prevents future sign-in and revokes existing API tokens.</p>
            <p>Approved leave totals are shown for the selected year.</p>
        </section>
    </div>

    <section>
        <h2>Employee accounts</h2>
        <form class="row" method="get" action="{{ route('admin.employees') }}">
            <label for="status">Status
                <select id="status" name="status">
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="blocked" @selected($status === 'blocked')>Blocked</option>
                    <option value="all" @selected($status === 'all')>All employees</option>
                </select>
            </label>
            <label for="year">Leave year <input id="year" type="number" name="year" value="{{ $year }}" min="2000" max="2200"></label>
            <button class="secondary">Filter</button>
        </form>
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Account</th><th>Email verification</th><th>Approved leave days · {{ $year }}</th><th>Edit employee</th></tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr>
                        <td>{{ $employee->name }}<br><span class="muted">{{ $employee->designation ?: 'No designation' }}</span></td>
                        <td>{{ $employee->email }}<br><span class="muted">{{ $employee->phone ?: 'No phone' }}</span><br><span class="muted">{{ $employee->address ?: 'No address' }}</span></td>
                        <td>{{ $employee->is_active ? 'Active' : 'Blocked' }}</td>
                        <td>{{ $employee->email_verified_at ? 'Verified' : 'Not verified' }}</td>
                        <td>{{ (int) ($leaveDaysByStaff[$employee->id] ?? 0) }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.staff.update', $employee) }}">
                                @csrf
                                @method('PUT')
                                <label>Name <input name="name" value="{{ $employee->name }}" required maxlength="120"></label>
                                <label>Designation <input name="designation" value="{{ $employee->designation }}" maxlength="120"></label>
                                <label>Phone <input name="phone" value="{{ $employee->phone }}" maxlength="40"></label>
                                <label>Address <input name="address" value="{{ $employee->address }}" maxlength="2000"></label>
                                <label>Account status
                                    <select name="is_active">
                                        <option value="1" @selected($employee->is_active)>Active</option>
                                        <option value="0" @selected(!$employee->is_active)>Blocked</option>
                                    </select>
                                </label>
                                <button class="{{ $employee->is_active ? 'danger' : 'secondary' }}">
                                    {{ $employee->is_active ? 'Save / block' : 'Save / reactivate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No employees in this status.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div>{{ $employees->links() }}</div>
    </section>
</main>
</body>
</html>
