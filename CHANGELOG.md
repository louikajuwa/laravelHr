# Changelog

## [Unreleased] — 2026-10-18 (follow-up)

### New features
- **Revoke approved leave (HR):** the balance is refunded, unworked leave days are reverted, and overlapping draft payrolls are discarded for regeneration. Revoking is blocked when a finalized or paid payroll covers the dates. There is a new `revoked` status.
- **Leave carry-over:** unused days carry into the new year up to the type's `max_carry_over_days` (0 = no limit), with no expiry.
- **Semi-monthly payroll:** `biweekly` salary structures are paid for 1–15 and 16–end, each with half the basic salary, allowances and bonus. Monthly staff still require a full month.
- **Password reset:** "Forgot password?" flow with a response that doesn't reveal whether the email exists, throttling, and invalidation of other sessions.
- **Company timezone:** `HR_TIMEZONE` (default `UTC`) sets the attendance date, lateness against shift times, overnight clock-out, HR corrections, and the nightly day close. Storage remains UTC.

### Build / infrastructure
- Tailwind Play CDN replaced by Vite 6 + Tailwind 3.4 (`package.json`, `vite.config.js`, `tailwind.config.js`). The output in `public/build` is not committed.
- The PHP container runs as non-root user `app` (uid/gid 1000, configurable via build args). The new one-shot `app-init` service sets ownership of `storage/`, `bootstrap/cache` and `vendor/`, and `chmod 777` was removed.
- New services: `mailpit` (dev mail at http://localhost:8025) and `node` (profile `tools`, for asset builds). `nginx` now has a healthcheck on `/up`.

### Changed
- Salary structure form: "Biweekly" is now labelled "Semi-monthly (1–15, 16–end)". The undefined "Fixed" absence mode is no longer offered.

### Database / configuration — manual steps
- **Migration:** `2026_10_18_000001_add_revocation_to_leave_requests` (additive: `revoked_at`, `revoked_by`). It has already been applied to the local dev DB.
- Build the assets: `docker compose run --rm node npm ci && docker compose run --rm node npm run build`.
- Rebuild the image: `docker compose up -d --build`.
- `.env`: set `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025` for Mailpit, and optionally set `HR_TIMEZONE`.

## [Unreleased] — 2026-10-18

### Security
- Login rate limiting (5 attempts/min per email+IP, plus route throttle).
- Demo seeder (known `password` accounts) runs only in `local`/`testing`.
- Employees can no longer view draft payslips; non-owned payslips return 404.
- Profile email/password changes require the current password; other sessions are invalidated on password change.
- Admins cannot delete, deactivate or demote themselves or the last active admin; cannot decide their own leave.
- CSV payroll export neutralises spreadsheet formula injection.
- New `SecurityHeaders` middleware (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy).
- Dependencies updated within existing constraints; `composer audit` reports 0 advisories (was 33).
- `docker-compose.yml`: MySQL and phpMyAdmin bound to `127.0.0.1`; passwords read from `DB_ROOT_PASSWORD` / `DB_PASSWORD` (same defaults).

### Bug fixes
- Leave types can be edited again (wrong route parameter in validation).
- Leave requests: start not in the past, max 365 days, active leave types only, reason ≤ 1000 chars.
- Leave approve/reject/cancel are row-locked; balances cannot be double-counted.
- Approved leave no longer overwrites days with a clock-in.
- Leave types in use cannot be deleted (deactivate instead).
- Night-shift clock-out after midnight works.
- HR attendance corrections keep the chosen status (holiday/absent/half-day/on-leave); unchanged fields no longer log corrections; times validated against the attendance date.
- `attendance:close-day` now closes **yesterday** and records approved leave as `on_leave`.
- Payroll: periods must be one full calendar month; holidays are not absences; half-days count 0.5; days before hire date and future days are ignored.
- Payroll status transitions enforced (draft → finalized → paid); overlapping periods rejected; batch generation tolerates duplicate races.
- Salary structures: new structure closes the previous one the day before it starts; historical payroll uses the structure effective at that date; `user_id` cannot be changed on update.
- Soft-deleted employees no longer break HR lists, payroll views or CSV export.
- Invalid `month` / `period_start` query parameters no longer cause 500 errors.
- Late-arrival reports: no future dates or duplicates; HR can review only pending reports.
- Shifts require different start/end times and at least one working day.
- Search treats `%` and `_` literally.

### UI/UX and accessibility
- Accessible, keyboard-operable modals with focus management; payroll generate modal reopens with errors.
- Confirmation dialogs for payroll generate/finalize/mark-paid and shift overwrite; double-submit protection on all forms.
- Inline error states with `aria-invalid`/`aria-describedby`; labelled filter controls; persistent error flashes.
- List filters and page persist in the URL; aborted stale requests; empty/error states with icons.
- Skip link, landmarks, mobile sidebar `inert`, focus rings, improved contrast, `th scope`, chart labels.
- Demo credentials hidden outside `local`. CDN libraries pinned with SRI.
- Payroll generate form explains the full-month rule.

### Refactoring / maintenance
- Pint applied project-wide.
- Removed unused `welcome.blade.php` and npm commands from `composer.json` scripts (no `package.json` exists).
- `.env.example` matches the Docker setup.
- Factories added: LeaveType, LeaveRequest, Attendance, Shift, SalaryStructure, Payroll; `UserFactory` gains HR fields and `inactive()` state.

### Tests
- PHPUnit now runs against a dedicated MySQL database `laravel_hr_testing`; `tests/TestCase.php` refuses to run against any database whose name does not contain `testing`.
- 77 feature/unit tests (was 2) covering security, leave, attendance and payroll.

### Database / configuration — manual steps
- **No schema migrations were added.**
- Create the test database once (already done on this machine):
  ```sql
  CREATE DATABASE laravel_hr_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  GRANT ALL ON laravel_hr_testing.* TO 'hr_user'@'%';
  ```
- Optional: set `DB_ROOT_PASSWORD` / `DB_PASSWORD` in the shell or a compose `.env` to override the defaults.
- Recreate containers (`docker compose up -d`) to apply the localhost port bindings.
