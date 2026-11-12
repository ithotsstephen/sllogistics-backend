<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in · SL Logistics</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#0f172a;font:16px system-ui,sans-serif;min-height:100vh;display:grid;place-items:center}
        main{background:white;padding:2rem;border-radius:14px;width:min(420px,calc(100% - 2rem));box-shadow:0 12px 40px #0f172a12}
        h1{margin-top:0}label{display:block;font-weight:600;margin:1rem 0 .35rem}input{width:100%;padding:.75rem;border:1px solid #cbd5e1;border-radius:7px;font:inherit}
        button{margin-top:1.2rem;width:100%;padding:.8rem;border:0;border-radius:7px;background:#0f766e;color:white;font:inherit;font-weight:700;cursor:pointer}
        .error{color:#b91c1c;margin:.5rem 0}
    </style>
</head>
<body>
<main>
    <h1>SL Logistics</h1>
    <p>Administrator sign in</p>
    @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
    <form method="post" action="{{ route('admin.login.submit') }}">
        @csrf
        <label for="login">Username or email</label><input id="login" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
        <label for="password">Password</label><input id="password" name="password" type="password" required>
        <button type="submit">Sign in</button>
    </form>
</main>
</body>
</html>
