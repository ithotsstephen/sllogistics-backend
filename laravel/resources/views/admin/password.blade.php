<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change password · SL Logistics</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#0f172a;font:14px system-ui,sans-serif}
        header{background:#0f172a;color:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
        nav{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}nav a{color:white;text-decoration:none;padding:.45rem .65rem;border-radius:6px}nav a:hover{background:#334155}
        main{max-width:800px;margin:auto;padding:1.5rem}section{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.2rem;margin:1rem 0}
        label{display:block;font-weight:600;margin:1rem 0 .35rem}input{display:block;width:100%;font:inherit;padding:.65rem;border:1px solid #cbd5e1;border-radius:6px}
        button{font:inherit;background:#0f766e;color:#fff;padding:.65rem .9rem;border:0;border-radius:6px;cursor:pointer;margin-top:1rem}
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
        <a href="{{ route('admin.employees') }}">Employee Management</a>
    </nav>
    @include('admin.partials.profile-menu')
</header>
<main>
    <h1>Change administrator password</h1>
    <p class="muted">Enter your current password and choose a new password with at least 12 characters.</p>
    @if (session('status')) <div class="notice">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
    <section>
        <form method="post" action="{{ route('admin.password.update') }}">
            @csrf
            @method('PUT')
            <label for="current_password">Current password</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" required minlength="12" autocomplete="new-password">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
            <button type="submit">Change password</button>
        </form>
    </section>
</main>
</body>
</html>
