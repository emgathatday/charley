# IAM Frontend UI Integration Plan

## Scope
Plan frontend-facing IAM screens for registration, login, verification submission, account security, and activity history. This phase uses static mock data only and does not call database records or live API data.

## Existing Backend Admin Mockups
- `resources/views/iam/users.blade.php`: admin user list, role/status/verification overview.
- `resources/views/iam/verification-queue.blade.php`: admin verification review queue and static decision form.
- `resources/views/iam/user-security.blade.php`: user detail security state and account status controls.

## Frontend Pages To Add Later
- Registration page: public SSR/SSG friendly page with form fields for username, first name, last name, email, password, and role defaults.
- Login page: public page with email/password form plus entry points for OTP or magic link.
- Verification submission page: authenticated client page for submission type, verification method, document media IDs, and notes.
- Account security page: authenticated client page for MFA state, recovery-code handling, account freeze, login attempt state, and locked-until state.
- Activity history page: authenticated client page for user activity feed and user meta preferences.

## Component Split
- `components/iam/RegisterForm.tsx`: client component for registration validation and submit state.
- `components/iam/LoginForm.tsx`: client component for password login and token-based login modes.
- `components/iam/VerificationSubmissionForm.tsx`: client component for verification submission.
- `components/iam/AccountSecurityPanel.tsx`: client component for MFA, status, and account security controls.
- `components/iam/ActivityHistoryList.tsx`: server or client component depending on final data loading strategy.

## Data Integration Notes
- Use `/api/v1/auth/register`, `/api/v1/auth/login`, `/api/v1/auth/login-tokens`, and `/api/v1/auth/login-tokens/consume` for auth flows.
- Use `/api/v1/verification-requests` for verification submission and review data.
- Use `/api/v1/account/security/*` for MFA, failed login state, and account freeze flows.
- Use `/api/v1/profile/activity` and `/api/v1/profile/metas` for authenticated profile history and settings.

## Gaps
- No concrete Next.js app directory exists in the Laravel workspace yet.
- No final frontend authentication/session strategy is defined for Next.js.
- No concrete upload UI exists yet for `document_media_ids`; it must integrate with the shared `media_files` flow later.
- Current TailAdmin Blade mockups are backend admin views, not end-user frontend screens.
