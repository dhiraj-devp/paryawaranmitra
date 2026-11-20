# Testing strategy

Tests protect the foundation that later modules will sit on: who can sign in, which portal they reach, what they are allowed to see, and the audit trail.

## How to run

```bash
php artisan test --compact
```

PHPUnit uses an in-memory SQLite database, the array cache, the array session, and the sync queue. `ADMIN_TWO_FACTOR` is false. The timezone is `Asia/Kolkata`. Bcrypt cost is 4.

A passing local MySQL install is not required to run the suite.

## Layout

| Area | What it locks |
| --- | --- |
| `tests/Feature/AuthenticationTest.php` | Valid login, generic failure, wrong portal, inactive user, throttling, the two-factor flag, logout of one guard only, customer routes staying closed. |
| `tests/Feature/PortalAccessTest.php` | Staff cannot open the admin dashboard. Admin cannot open the staff dashboard. Each case uses its own application instance so a guard user does not leak into the next request. |
| `tests/Feature/LocalizationAndShellTest.php` | Language switch, Marathi labels, sidebar visibility, Quick Create permission, settings without secrets. |
| `tests/Feature/SearchAccessAndSecurityTest.php` | Admin search finds users. Staff search does not. Short queries are refused. Document disk is private. Audit rows and system roles cannot be removed. Catalog sync is idempotent. Portal mismatch on role assignment throws. |
| `tests/Feature/ClientIndustryTest.php` | Client and industry-unit records, permissions, portal isolation, duplicate detection, status changes, search, and audit rollback. |
| `tests/Feature/ServiceFeeTest.php` | Service catalogue, industry eligibility, fee schedules, effective dates, decimal amounts, permissions, and audit rollback. |
| `tests/Feature/EnquiryWorkOrderTest.php` | Enquiries, follow-up history, conversion, one work order per enquiry, eligibility, permissions, portal isolation, and audit rollback. |
| `tests/Feature/QuotationTest.php` | Quotation creation, fee snapshots, separate government and consultancy amounts, custom lines, discounts, decimal totals, revision immutability, accepted-revision preservation, status transitions, private PDFs, permissions, portal isolation, and audit rollback. |
| `tests/Unit/AuditLoggerTest.php` | Secret fields are redacted before insert. |

`tests/Concerns/InteractsWithAccess.php` syncs the catalog and builds a user with a role. Feature tests use `RefreshDatabase`.

## Rules for new tests

- Exercise the HTTP or action boundary a user hits. Do not assert a private method when the behaviour is visible from a response or a row.
- A new permission check needs a test for the role that has it and a role that does not.
- Financial actions, when added, must assert the transaction: a thrown exception leaves both the money row and the audit row unwritten.
- Document downloads must assert that an unauthorized user receives 403 and that the file URL is not a public storage path.
- Do not weaken a test to match a bug. Fix the behaviour.
- Do not use `Gate::before` to make a super-admin pass a policy that is specified to return false.

## What is not covered yet

Browser clicks are checked by hand for the shell and for the client, service, fee, enquiry, work order, and quotation screens. PHPUnit does not drive a real browser. Invoices have no tests because that module does not exist.
