# SL Logistics Attendance Backend

Laravel 12 API and browser-based administrator dashboard for the SL Logistics attendance system. The React Native client is a separate project.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ for production (SQLite is configured for local development)

## Repository layout

The repository root is arranged for Hostinger deployment:

```text
laravel/       Laravel application, environment file, and Composer dependencies
public_html/   Domain document root; contains index.php and public assets
```

Keep `laravel/.env` private and outside `public_html`. The public front controller loads the application from the sibling `laravel/` directory.

## Local setup

```sh
cd laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

The local `.env.example` uses SMTP on `127.0.0.1:1025`, intended for a local mail catcher such as Mailpit. Start Mailpit before creating employees; its web inbox is usually available at `http://127.0.0.1:8025`. These messages are captured locally and are not delivered to real inboxes. Alternatively, configure your mailbox provider's SMTP settings in your local `.env` to send real email during development.

Set `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_EMAIL`, and a strong `ADMIN_PASSWORD` in `.env`, then create the first administrator. Admins can sign in using either username or email:

```sh
php artisan db:seed
php artisan serve
```

Open `/admin/login` for the web dashboard. The dedicated employee module is at `/admin/employees`; administrators can add employees, edit profile details, review leave-day totals, and block or reactivate accounts. Blocking an account also revokes its API tokens. The dashboard summarizes active employees, completed hours today and this week, and monthly completed hours for the selected year. Open shifts without a recorded time-out are excluded from hour totals. The dedicated time-tracking module is at `/admin/time-tracking` and lists each employee's daily time-in, time-out, hours, location, and work description with month/year and employee filters. The dedicated leave module is at `/admin/leave-management` and allows administrators to filter requests by status, review request details, and approve or reject pending requests. Set `APP_TIMEZONE` to the organization's timezone. Employee provisioning requires a working SMTP server: local development can use Mailpit, while production must use a real mailbox configured privately in Hostinger's `.env`. The app intentionally returns HTTP 503 when `MAIL_MAILER=log` so credentials and OTPs are not falsely reported as emailed or written to application logs.
Signed-in administrators can open the password-change page from the profile icon in the top-right of the admin pages. The form requires the current password and a confirmed new password of at least 12 characters.

## Production database and deployment

On Hostinger, create the database, user, and mailbox in hPanel and allowlist the deployment server IP if remote MySQL access requires it. Use [`laravel/.env.hostinger.example`](./laravel/.env.hostinger.example) as a checklist for production settings, but enter actual database and mailbox values only in the server's private `laravel/.env`; never put it in `public_html` or commit credentials. Confirm the SMTP host, port, and encryption mode in your Hostinger email settings.

For Hostinger Git deployment, configure the repository deployment directory as the account directory that contains both `laravel/` and `public_html/`—not `public_html/` itself. Keep the domain's document root set to `public_html/`. Configure the deployment hook to run `./deploy.sh` after each checkout or update. The script installs production Composer dependencies and refreshes Laravel's optimized caches; it does not change the database schema.

Create `laravel/.env` on the server before the first deployment hook runs. Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and the database and SMTP settings. Run these once from the `laravel/` directory when appropriate:

```sh
php artisan key:generate
php artisan config:clear
php artisan migrate --force
```

Back up the database before applying migrations. Do not automatically run migrations or seeders on every Git push. Seed the first administrator only after setting `ADMIN_EMAIL` and `ADMIN_PASSWORD` in the private `.env` and confirming the account does not already exist. Use a unique administrator password held outside source control. Rotate any credentials that have been shared in chat or another untrusted location.

## Mobile API

All API routes are under `/api`. Authenticate with a bearer token returned by `POST /api/auth/login`; send it as `Authorization: Bearer <token>`. JSON requests should set `Accept: application/json`.

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/admin/auth/login` | Admin username/email and password; returns an admin bearer token |
| POST | `/api/admin/employees` | Admin bearer token required; creates employee and emails username, temporary password, email OTP, and profile details |
| POST | `/api/admin/auth/logout` | Revoke current admin bearer token |
| POST | `/api/auth/login` | Staff email/password sign-in; unverified users receive an OTP challenge |
| POST | `/api/auth/verify-email` | Verify with `{ "email": "...", "otp": "000000" }` |
| POST | `/api/auth/change-password` | Change temporary password with `current_password`, `password`, and `password_confirmation` |
| POST | `/api/auth/logout` | Revoke current access token |
| GET / PUT | `/api/profile` | View or edit staff name, designation, phone, and address |
| GET | `/api/attendance?month=11&year=2026` | Staff attendance history |
| POST | `/api/attendance/time-in` | `{ "work_description": "...", "location": "..." }` |
| POST | `/api/attendance/time-out` | `{ "work_description": "..." }`; saves completion description and duration |
| GET / POST | `/api/leave-requests` | View or submit leave `{ "start_date": "YYYY-MM-DD", "end_date": "YYYY-MM-DD", "reason": "..." }` |

Admin API employee onboarding:

1. `POST /api/admin/auth/login` with `{ "login": "admin@example.com", "password": "..." }`; use the returned token as `Authorization: Bearer <token>`.
2. `POST /api/admin/employees` with `{ "name": "...", "designation": "...", "email": "...", "phone": "...", "address": "..." }`.
3. The `201` response contains the employee profile (`id`, `name`, `username`, `email`, designation, phone, address, active state, and verification state). The username is the email address. Password and OTP are never included in the API response; they are sent to the employee's email.
4. The employee verifies with `POST /api/auth/verify-email` using email and OTP, signs in with the emailed temporary password at `POST /api/auth/login`, then changes that password.

Attendance timestamps are generated by the server, one record per staff member per work date. Each attendance response includes `duration_minutes` and `duration_hours`. Leave ranges are inclusive; overlapping pending or approved requests are rejected. Admin decisions and profile management are available in the authenticated web dashboard.

## Administrator provisioning

Create the first administrator using the database seeder and environment variables described above. Administrators can create staff accounts in the dashboard or through the authenticated admin API. The employee username is their email address. A one-time email sends their temporary password, email OTP (valid for ten minutes), and employee profile details. The API response contains the employee profile but never returns the temporary password or OTP. Staff sign in with the emailed credentials, verify their email OTP, then must change the temporary password before using the attendance and leave APIs. Because both credentials are delivered in the same email, configure SMTP securely and encourage an immediate password change.

## Tests

```sh
cd laravel
php artisan test
```
