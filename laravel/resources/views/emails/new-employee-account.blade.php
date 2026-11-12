<h1>Welcome to SL Logistics, {{ $employee->name }}</h1>

<p>Your employee account has been created. Use these details to sign in to the SL Logistics mobile app:</p>

<ul>
    <li><strong>Username:</strong> {{ $employee->email }}</li>
    <li><strong>Temporary password:</strong> {{ $temporaryPassword }}</li>
    @if ($employee->designation)
        <li><strong>Designation:</strong> {{ $employee->designation }}</li>
    @endif
    @if ($employee->phone)
        <li><strong>Phone:</strong> {{ $employee->phone }}</li>
    @endif
    @if ($employee->address)
        <li><strong>Address:</strong> {{ $employee->address }}</li>
    @endif
</ul>

<p>Verify your email in the app using this one-time code:</p>
<p style="font-size: 24px; font-weight: bold; letter-spacing: 4px">{{ $emailOtp }}</p>
<p>This code expires in 10 minutes. After verification, sign in and change your temporary password before using the app.</p>
<p>If you did not expect this account, contact your SL Logistics administrator.</p>
