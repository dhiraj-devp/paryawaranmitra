# Architecture

The ERP is a modular monolith. One Laravel application owns HTTP, authentication, persistence, and the user interface. Business modules will be added inside this application. They will not become separate services.

## Layers

| Layer | Location | Responsibility |
| --- | --- | --- |
| HTTP | `app/Http/Controllers`, `app/Http/Requests`, `app/Http/Middleware` | Read the request, authorize, call one action or service, return a view. |
| Livewire | `app/Livewire` | The same rule as controllers. The login form is the only Livewire component in this phase. |
| Actions | `app/Actions` | One user intention with a single `execute` method. Login, logout, and role assignment live here. |
| Services | `app/Services` | Coordination used by more than one entry point: audit, access catalog, navigation, search, dashboard. |
| Policies | `app/Policies` | Object-level authorization. |
| Models | `app/Models` | Persistence, relationships, and small permission helpers. No workflow logic. |
| Config | `config/access.php`, `config/navigation.php`, `config/security.php`, `config/paryavaran.php` | Catalogs. Fees, deadlines, and industry rules will also be data, not PHP constants. |

Controllers and Livewire components stay thin. A new business operation gets an action. Shared reads get a service. A model does not send mail, write audit rows, or decide fees.

`Model::preventLazyLoading()` and `Model::preventSilentlyDiscardingAttributes()` are on outside production so N+1 queries and dropped attributes fail during development.

## Route areas

`routes/web.php` defines three prefixes.

- `admin.*` uses the `admin` guard and the `portal:admin` middleware.
- `staff.*` uses the `staff` guard and the `portal:staff` middleware.
- `customer.*` is public and only renders the reserved page. There is no login POST and no registration route.

`/admin` and `/staff` redirect to that portal's dashboard when its guard is authenticated, and to its login otherwise. `/customer` redirects to `/customer/login`.

Guest redirects follow the URL prefix, so an admin page sends an anonymous visitor to the admin login. Authenticated redirects also follow the URL prefix first. A browser that has both sessions and opens the staff login returns to the staff dashboard.

## Authentication

`config/auth.php` defines `web`, `admin`, and `staff` session guards. All three use the `users` provider. Application routes do not use the `web` guard, so a generic `auth` middleware cannot accidentally accept an administrator.

`EnsurePortal` sets the default guard for the request, rejects a user whose `portal` does not match the area, and logs out an inactive user with HTTP 403.

The same browser may hold an admin session and a staff session. Logout calls `Auth::guard($portal)->logout()`, regenerates the session id and CSRF token, and does not call `session()->invalidate()`. The other portal stays signed in.

Login is `App\Actions\Auth\AttemptPortalLogin`, called from `App\Livewire\Auth\LoginForm`. Throttling lives in the action because Livewire posts to its own endpoint and would not pass route middleware on `/admin/login`. Limits are 5 attempts per portal, email, and IP, and 30 attempts per portal and IP, with a 60 second decay. A missing user is checked against a dummy bcrypt hash so the response time stays similar. Bad password, wrong portal, and inactive account return the same message.

On success the action regenerates the session and writes the user's locale. It uses the `session()` helper and starts the session when Livewire's test request has no session store.

Passwords require 12 characters, mixed case, and a number. The uncompromised-password check is not enabled, so local and test runs do not call an external breach service.

## Two-factor authentication

`users` stores an encrypted secret, encrypted recovery codes, and `two_factor_confirmed_at`. `User::hasConfirmedTwoFactor()` reports whether enrollment has been confirmed. Those columns are hidden and are not fillable.

`ADMIN_TWO_FACTOR` defaults to false. The value is read with `filter_var(..., FILTER_VALIDATE_BOOL)` because the string `"false"` would otherwise be treated as true. When the flag is true, administrator login is refused and audited as `auth.two_factor_unavailable`. Enrollment is not implemented. The flag must not be turned on until a real authenticator flow exists.

## Authorization

Permissions are rows, not a package. `config/access.php` is the catalog. `AccessCatalog` upserts roles and permissions inside a transaction and does not delete permissions that disappear from the file, so a renamed slug cannot silently orphan history.

`User::hasPermission()` returns false for an inactive user. An active super-admin resolves to every slug. There is no `Gate::before`. Policies for creating, updating, and deleting users and audit logs return false even for a super-admin. Deleting those records is not a permission this phase grants.

`permission` middleware and policies both call `hasPermission()`. A route that lists records also calls `authorize()` so the policy remains the object rule.

A role can be assigned only when its portal matches the user. `AssignRole` throws `PortalMismatchException` otherwise. System roles cannot be deleted; the model hook rejects it.

## Audit

`AuditLogger` writes one row per event. It redacts `password`, `password_confirmation`, `current_password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`, and `token`.

`AuditLog` has no `updated_at`. Updating or deleting a row throws. Actor deletion nulls `actor_id` and keeps the event.

Events recorded now: `auth.login`, `auth.login_failed`, `auth.logout`, `profile.locale_changed`, `access.role_assigned`. Failed login reasons are `invalid_credentials`, `wrong_portal`, `inactive`, and `two_factor_unavailable`.

## Localization

Supported locales are `en` and `mr` in `config/paryavaran.php`. Strings live in `lang/en` and `lang/mr`. `SetLocale` uses the session locale, then the signed-in admin or staff user's locale, then the application locale. It also sets Carbon's locale.

`POST /locale` validates the code and redirects to the previous URL only when that URL has the same host. A successful login replaces the session locale with the user's saved preference.

The document language switches the font: Inter for English, Noto Sans Devanagari for Marathi. The language control is a real form, so it works without JavaScript.

The application timezone defaults to `Asia/Kolkata`. Dates in the shell use `d-m-Y H:i`.

## Search and navigation

`GlobalSearch` asks every tagged `SearchSource`. A source returns nothing when the portal or permission does not match. Admin search can see users (`users.view`) and audit text (`audit.view`). Staff search cannot see users. Terms shorter than 2 characters are rejected. `LikeTerm` escapes `%`, `_`, and `\`.

`NavigationBuilder` reads `config/navigation.php`. An item with a route is a link only when that route exists and the user has the permission. An item with permission but no route is labelled later. An item the user cannot view is omitted. Quick Create reads `quick_actions`.

## Documents

The `documents` disk is local, rooted at `storage/app/private/documents`, with `serve` false, private visibility, and exceptions enabled. Later downloads go through an authorized controller. The public disk is not used for client files.

## Redis

`config/database.php` already contains the Redis connection. `CACHE_STORE`, `QUEUE_CONNECTION`, and `SESSION_DRIVER` stay on `database` until the `redis` PHP extension is installed. Do not point those drivers at Redis before that, and do not add Predis only to make the config look ready.

## Financial operations

Fee schedules exist. Each fee action runs inside a database transaction, writes an audit event, and stores the government fee and the consultancy fee as separate `decimal(14,2)` amounts. Enquiries, work orders, and quotations also commit their changes and audit rows together. Quotation PDFs are stored on the private `quotations` disk. Invoices, receipts, and expenses are not built yet. See [DATABASE_DESIGN.md](DATABASE_DESIGN.md).

## Frontend

Layouts include `partials.assets`, which loads self-hosted fonts from `public/fonts`, the sidebar rules in `public/css/app.css`, and the Tailwind CSS 4 browser build in `public/js/tailwind.js`. That script turns Blade utility classes into CSS in the browser, so there is no asset build step. Design tokens live in the `@theme` block of that partial. Alpine is started by Livewire. Do not import a second Alpine build. Livewire's component scan is limited to `resources/views/livewire` so Blade components in `resources/views/components` are not treated as Livewire components.

Layouts render `@livewireStyles` and `@livewireScripts` so Alpine exists on pages that have no Livewire component. Livewire skips a second injection when those stacks were already rendered.

Pages send `noindex, nofollow`.
