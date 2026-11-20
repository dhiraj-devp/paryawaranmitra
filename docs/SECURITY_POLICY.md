# Security policy

Security controls in this phase are the ones the application actually enforces. Later modules must extend them. They must not bypass them.

## Accounts

- Super Admin and Staff are separate guards. A staff session cannot open `/admin`. An admin session cannot open `/staff`.
- Customer authentication is not implemented. `/customer/login` accepts GET only.
- Passwords are hashed by the `hashed` cast. The policy is 12 characters with upper case, lower case, and a number.
- New local accounts are created only when both email and password are set. Re-seeding does not reset a password, does not reactivate a user, and does not change their portal. A role is attached only when it is missing.
- Inactive users receive the same login error as a bad password. If a session already exists, portal middleware logs them out and returns 403.
- Login regenerates the session id. Logout regenerates the session id and the CSRF token, and signs out only the current guard.
- Remember-me uses Laravel's framework token. The token is hidden from serialization.

## Throttling

`AttemptPortalLogin` rate-limits before it reports success or failure.

- 5 attempts per portal, email, and IP.
- 30 attempts per portal and IP.
- Decay of 60 seconds, from `config/security.php`.

The counters are cleared after a successful sign-in for that email. A locked attempt is audited only while under the per-account cap, so a script cannot fill `audit_logs` by repeating one address.

## Two-factor authentication

Columns and encryption are in place for administrators. Enrollment, challenge, and recovery are not built. `ADMIN_TWO_FACTOR=true` blocks administrator login and writes `auth.two_factor_unavailable`. Leave the flag false until enrollment exists. Staff are not covered by that flag.

## Sessions and cookies

- Driver: database. Payload encrypted.
- Lifetime: 120 minutes.
- HTTP only, SameSite `lax`, path `/`.
- `SESSION_SECURE_COOKIE` is false for local HTTP. Set it true behind HTTPS in production.
- Production forces the HTTPS scheme only when `APP_URL` starts with `https://`.

## CSRF and headers

Web routes use Laravel's CSRF middleware. Logout and the language switch are POST forms.

Every response adds:

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` with camera, microphone, and geolocation disabled
- `X-Permitted-Cross-Domain-Policies: none`

HSTS is not set. Sending it from an HTTP local site would pin the browser. Add HSTS at the TLS terminator in production.

Pages send `noindex, nofollow`.

## Authorization

- Permissions are data, synced from `config/access.php`.
- Routes that need a permission use the `permission` middleware.
- User and audit controllers also call policies.
- Super-admin has every permission slug through `hasPermission()`. Policies still refuse create, update, and delete on users and audit logs. There is no gate that runs before policies.
- Role assignment checks the portal. A staff role cannot be attached to an administrator, and the reverse.

See [RBAC_MATRIX.md](RBAC_MATRIX.md).

## Audit

`audit_logs` is append-only. The model throws if code tries to update or delete a row. Secrets are stripped before insert. Failed logins record a reason and never record the password.

Financial actions, when they exist, must write their audit row in the same database transaction as the money movement.

## Files

Client documents use the `documents` disk:

- root `storage/app/private/documents`
- `serve` false, so Laravel will not expose the disk through a public route
- visibility private
- exceptions thrown on failure

Do not call `storage:link` for this disk. Do not store client files on the `public` disk. A later download controller must authorize the user, then stream the file.

Quotation PDFs use the `quotations` disk:

- root `storage/app/private/quotations`
- `serve` false
- visibility private

The download controller checks `quotations.view`, then streams the file. The path is not a public storage URL.

## Configuration

- `.env` is gitignored. `.env.example` has empty passwords.
- Quote any value that contains `#`. Unquoted `#` starts a comment and silently shortens the password.
- Do not commit `APP_KEY`, database passwords, or mail credentials.
- Settings screen prints runtime choices only. It does not print the application key.

## Backups

The database and `storage/app/private` must be backed up together. A database backup without the document files, or the reverse, is an incomplete restore. The procedure is in [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md).

## Dependencies

No extra authentication or permission package is installed. Add a package only when the application needs behaviour Laravel does not already provide, and record why in this document.
